<?php

namespace App\Http\Controllers\Api\V1\Internal;

use App\Domain\Odoo\OdooOrderPayload;
use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Export read-only cho odoo-connector-service (batch reconcile).
 * GET /api/v1/internal/export/orders?since=<cursor>&limit=<1-500, default 200>
 * Header: X-Source-Token (middleware 'source').
 * Response: { "data": [unified order...], "cursor": "<iso>|<id>" }
 * Cursor la composite (updated_at, id): chong lap/thieu khi nhieu row
 * chung timestamp. Cursor cu (iso thuan) van chay: since_id mac dinh 0.
 */
class ExportController extends Controller
{
    public function orders(Request $request): JsonResponse
    {
        $limit = max(1, min(500, $request->integer('limit', 200)));

        $query = Order::query()
            ->where('payment_status', 'paid')
            ->with(['items', 'payments', 'addresses'])
            ->orderBy('updated_at')
            ->orderBy('id');

        $rawSince = (string) $request->query('since', '');
        [$sinceAt, $sinceId] = $this->parseCursor($rawSince);
        if ($sinceAt) {
            $query->where(function ($q) use ($sinceAt, $sinceId): void {
                $q->where('updated_at', '>', $sinceAt)
                    ->orWhere(fn ($qq) => $qq->where('updated_at', '=', $sinceAt)->where('id', '>', $sinceId));
            });
        }

        $orders = $query->take($limit)->get();
        $data = $orders->map(fn (Order $o) => OdooOrderPayload::build($o))->values()->all();

        $last = $orders->last();
        $cursor = $last?->updated_at instanceof \DateTimeInterface
            ? $last->updated_at->format(DATE_ATOM).'|'.$last->id
            : $rawSince;

        return response()->json(['data' => $data, 'cursor' => $cursor]);
    }

    /** Cursor "iso|id"; iso thuan (cu) -> since_id 0. Parse loi -> null (full export). */
    private function parseCursor(string $value): array
    {
        if ($value === '') {
            return [null, 0];
        }
        $sinceId = 0;
        if (str_contains($value, '|')) {
            [$value, $idPart] = explode('|', $value, 2);
            $sinceId = is_numeric($idPart) ? (int) $idPart : 0;
        }

        return [$this->parseDate($value), $sinceId];
    }

    private function parseDate(string $value): ?\DateTimeInterface
    {
        try {
            return new \DateTimeImmutable($value);
        } catch (\Exception) {
            return null;
        }
    }
}
