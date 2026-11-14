<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\OdooSyncLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class OdooAdminController extends Controller
{
    public function status()
    {
        $cutoff = now()->subDay();
        $recent = OdooSyncLog::where('created_at', '>=', $cutoff);
        $last = OdooSyncLog::orderByDesc('id')->first();
        $lastError = OdooSyncLog::whereIn('status', ['rejected', 'failed'])
            ->orderByDesc('id')->first();

        return response()->json(['data' => [
            'configured' => [
                'connector_url' => (string) config('services.odoo.connector_url'),
                'webhook_secret_set' => (string) config('services.odoo.webhook_secret') !== '',
                'direct_url' => (string) config('odoo.url'),
                'channel_code' => (string) config('odoo.channel_code'),
                'channel_key_set' => (string) config('odoo.channel_key') !== '',
            ],
            'last_24h' => [
                'success' => (clone $recent)->where('status', 'success')->count(),
                'rejected' => (clone $recent)->where('status', 'rejected')->count(),
                'failed' => (clone $recent)->where('status', 'failed')->count(),
            ],
            'last_push' => $last ? [
                'direction' => $last->direction,
                'event_id' => $last->event_id,
                'status' => $last->status,
                'http_status' => $last->http_status,
                'error' => $last->error,
                'created_at' => $last->created_at,
            ] : null,
            'last_error' => $lastError ? [
                'direction' => $lastError->direction,
                'event_id' => $lastError->event_id,
                'status' => $lastError->status,
                'error' => $lastError->error,
                'created_at' => $lastError->created_at,
            ] : null,
        ]]);
    }

    public function logs()
    {
        $q = OdooSyncLog::with(['order:id,order_number'])->orderByDesc('id');
        if ($status = request()->query('status')) {
            $q->where('status', $status);
        }
        if ($direction = request()->query('direction')) {
            $q->where('direction', $direction);
        }
        if ($search = request()->query('q')) {
            $q->where(function ($w) use ($search) {
                $w->where('event_id', 'ilike', "%{$search}%")
                    ->orWhere('endpoint', 'ilike', "%{$search}%")
                    ->orWhere('error', 'ilike', "%{$search}%");
            });
        }
        $paginator = $q->paginate(min(100, max(1, (int) request()->query('per_page', 20))));
        $paginator->getCollection()->transform(fn ($l) => [
            'id' => $l->id,
            'direction' => $l->direction,
            'event_id' => $l->event_id,
            'order_number' => $l->order?->order_number,
            'endpoint' => $l->endpoint,
            'status' => $l->status,
            'http_status' => $l->http_status,
            'error' => $l->error,
            'attempts' => $l->attempts,
            'duration_ms' => $l->duration_ms,
            'created_at' => $l->created_at,
        ]);

        return response()->json($paginator);
    }

    public function test()
    {
        $started = (int) (microtime(true) * 1000);
        $endpoint = rtrim((string) config('odoo.url'), '/')
            .'/api/v1/channel/catalog?channel_code='.urlencode((string) config('odoo.channel_code')).'&limit=1';
        try {
            $res = Http::withHeaders(['X-Channel-Key' => (string) config('odoo.channel_key')])
                ->timeout((int) config('odoo.timeout', 10))
                ->get($endpoint);
            $ok = $res->successful();
            $log = OdooSyncLog::record([
                'direction' => 'test',
                'endpoint' => $endpoint,
                'status' => $ok ? 'success' : 'rejected',
                'http_status' => $res->status(),
                'response_body' => mb_substr((string) $res->body(), 0, 2000),
                'error' => $ok ? null : 'Odoo test rejected: HTTP '.$res->status(),
                'duration_ms' => (int) (microtime(true) * 1000) - $started,
                'actor_user_id' => auth()->id(),
            ]);
            ActivityLog::record('odoo.test', 'odoo_sync_log', (string) $log->id, [], ['status' => $log->status]);

            return response()->json(['data' => [
                'ok' => $ok,
                'http_status' => $res->status(),
                'duration_ms' => $log->duration_ms,
                'request_id' => $res->header('X-Request-Id'),
            ]], $ok ? 200 : 502);
        } catch (\Throwable $e) {
            $log = OdooSyncLog::record([
                'direction' => 'test',
                'endpoint' => $endpoint,
                'status' => 'failed',
                'error' => mb_substr($e->getMessage(), 0, 2000),
                'duration_ms' => (int) (microtime(true) * 1000) - $started,
                'actor_user_id' => auth()->id(),
            ]);
            ActivityLog::record('odoo.test', 'odoo_sync_log', (string) $log->id, [], ['status' => 'failed']);

            return response()->json(['error' => ['code' => 'ODOO_UNREACHABLE', 'message' => 'Khong ket noi duoc Odoo master']], 502);
        }
    }

    public function retry(Request $request)
    {
        $data = $request->validate(['log_id' => ['required', 'integer', 'exists:odoo_sync_logs,id']]);
        $log = OdooSyncLog::findOrFail($data['log_id']);
        if ($log->status === 'success') {
            return response()->json(['error' => ['code' => 'INVALID_STATE', 'message' => 'Log da thanh cong, khong can retry']], 409);
        }
        $started = (int) (microtime(true) * 1000);
        try {
            $payload = $log->payload ?? [];
            if (str_contains($log->endpoint, '/ingest/')) {
                // Legacy connector: ky lai HMAC tren raw JSON se gui.
                $json = json_encode($payload, JSON_UNESCAPED_UNICODE);
                $sig = hash_hmac('sha256', $json, (string) config('services.odoo.webhook_secret'));
                $res = Http::withBody($json, 'application/json')
                    ->withHeaders(['X-Signature' => $sig])
                    ->timeout(5)
                    ->post($log->endpoint);
            } else {
                $res = Http::withHeaders(['X-Channel-Key' => (string) config('odoo.channel_key')])
                    ->timeout((int) config('odoo.timeout', 10))
                    ->post($log->endpoint, $payload);
            }
            $new = OdooSyncLog::record([
                'direction' => 'retry',
                'event_id' => $log->event_id,
                'order_id' => $log->order_id,
                'endpoint' => $log->endpoint,
                'status' => $res->successful() ? 'success' : 'rejected',
                'http_status' => $res->status(),
                'payload' => $payload,
                'response_body' => mb_substr((string) $res->body(), 0, 2000),
                'error' => $res->successful() ? null : 'Odoo retry rejected: HTTP '.$res->status(),
                'attempts' => $log->attempts + 1,
                'duration_ms' => (int) (microtime(true) * 1000) - $started,
                'actor_user_id' => auth()->id(),
            ]);
            ActivityLog::record('odoo.retry', 'odoo_sync_log', (string) $new->id, ['from_log' => $log->id], ['status' => $new->status]);

            return response()->json(['data' => [
                'ok' => $res->successful(),
                'http_status' => $res->status(),
                'log_id' => $new->id,
            ]], $res->successful() ? 200 : 502);
        } catch (\Throwable $e) {
            $new = OdooSyncLog::record([
                'direction' => 'retry',
                'event_id' => $log->event_id,
                'order_id' => $log->order_id,
                'endpoint' => $log->endpoint,
                'status' => 'failed',
                'payload' => $log->payload ?? [],
                'error' => mb_substr($e->getMessage(), 0, 2000),
                'attempts' => $log->attempts + 1,
                'duration_ms' => (int) (microtime(true) * 1000) - $started,
                'actor_user_id' => auth()->id(),
            ]);
            ActivityLog::record('odoo.retry', 'odoo_sync_log', (string) $new->id, ['from_log' => $log->id], ['status' => 'failed']);

            return response()->json(['error' => ['code' => 'ODOO_UNREACHABLE', 'message' => 'Khong ket noi duoc Odoo master']], 502);
        }
    }
}
