<?php

namespace App\Http\Controllers\Api\V1\Internal;

use App\Domain\Odoo\OdooOrderPayload;
use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Export read-only cho odoo-connector-service (batch reconcile).
 * GET /api/v1/internal/export/orders?since=<iso>
 * Header: X-Source-Token (middleware 'source').
 * Response: { "data": [unified order...], "cursor": "<iso>" }
 */
class ExportController extends Controller
{
    public function orders(Request $request): JsonResponse
    {
        $query = Order::query()
            ->where('payment_status', 'paid')
            ->with(['items', 'payments', 'addresses'])
            ->orderBy('updated_at');

        $rawSince = (string) $request->query('since', '');
        $sinceAt = $rawSince === '' ? null : $this->parseDate($rawSince);
        if ($sinceAt) {
            $query->where('updated_at', '>', $sinceAt);
        }

        $orders = $query->get();
        $data = $orders->map(fn (Order $o) => OdooOrderPayload::build($o))->values()->all();

        $maxUpdated = $orders->max('updated_at');
        $cursor = $maxUpdated instanceof \DateTimeInterface
            ? $maxUpdated->format(DATE_ATOM)
            : $rawSince;

        return response()->json(['data' => $data, 'cursor' => $cursor]);
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
