<?php

namespace App\Domain\Inventory;

use App\Models\InventoryLevel;
use App\Models\InventoryMovement;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    public function defaultWarehouseId(): int
    {
        return (int) DB::table('warehouses')->where('code', 'default')->value('id')
            ?? (int) DB::table('warehouses')->insertGetId([
                'code' => 'default', 'name' => 'Kho chinh', 'is_active' => true,
                'created_at' => now(), 'updated_at' => now(),
            ]);
    }

    /** Reserve stock inside caller's transaction. Rows must already be locked in stable order. */
    public function reserve(int $warehouseId, int $variantId, int $qty, string $referenceType, string $referenceId, string $idempotencyKey, ?int $actorId = null): void
    {
        if (InventoryMovement::where('idempotency_key', $idempotencyKey)->exists()) {
            return;
        }
        $level = InventoryLevel::where('warehouse_id', $warehouseId)->where('variant_id', $variantId)->lockForUpdate()->first();
        if (! $level || ($level->on_hand - $level->reserved) < $qty) {
            abort(response()->json(['error' => ['code' => 'INSUFFICIENT_STOCK', 'message' => 'Khong du ton kho']], 409));
        }
        $level->increment('reserved', $qty);
        InventoryMovement::create([
            'warehouse_id' => $warehouseId, 'variant_id' => $variantId,
            'delta_on_hand' => 0, 'delta_reserved' => $qty,
            'type' => 'reserve', 'reason' => 'checkout reserve',
            'reference_type' => $referenceType, 'reference_id' => $referenceId,
            'idempotency_key' => $idempotencyKey, 'actor_user_id' => $actorId, 'created_at' => now(),
        ]);
    }

    public function release(int $warehouseId, int $variantId, int $qty, string $referenceType, string $referenceId, string $idempotencyKey, ?int $actorId = null): void
    {
        if (InventoryMovement::where('idempotency_key', $idempotencyKey)->exists()) {
            return;
        }
        $level = InventoryLevel::where('warehouse_id', $warehouseId)->where('variant_id', $variantId)->lockForUpdate()->first();
        if (! $level) {
            return;
        }
        $level->decrement('reserved', min($qty, $level->reserved));
        InventoryMovement::create([
            'warehouse_id' => $warehouseId, 'variant_id' => $variantId,
            'delta_on_hand' => 0, 'delta_reserved' => -$qty,
            'type' => 'release', 'reason' => 'cancel/expire release',
            'reference_type' => $referenceType, 'reference_id' => $referenceId,
            'idempotency_key' => $idempotencyKey, 'actor_user_id' => $actorId, 'created_at' => now(),
        ]);
    }

    public function ship(int $warehouseId, int $variantId, int $qty, string $referenceId, string $idempotencyKey, ?int $actorId = null): void
    {
        if (InventoryMovement::where('idempotency_key', $idempotencyKey)->exists()) {
            return;
        }
        $level = InventoryLevel::where('warehouse_id', $warehouseId)->where('variant_id', $variantId)->lockForUpdate()->first();
        if (! $level) {
            abort(409, 'Missing inventory level');
        }
        $level->decrement('reserved', min($qty, $level->reserved));
        $level->decrement('on_hand', min($qty, $level->on_hand));
        InventoryMovement::create([
            'warehouse_id' => $warehouseId, 'variant_id' => $variantId,
            'delta_on_hand' => -$qty, 'delta_reserved' => -$qty,
            'type' => 'ship', 'reason' => 'fulfillment ship',
            'reference_type' => 'order', 'reference_id' => $referenceId,
            'idempotency_key' => $idempotencyKey, 'actor_user_id' => $actorId, 'created_at' => now(),
        ]);
    }

    public function adjust(int $warehouseId, int $variantId, int $deltaOnHand, string $reason, string $referenceId, string $idempotencyKey, ?int $actorId = null): void
    {
        if (InventoryMovement::where('idempotency_key', $idempotencyKey)->exists()) {
            return;
        }
        $level = InventoryLevel::firstOrCreate(['warehouse_id' => $warehouseId, 'variant_id' => $variantId], ['on_hand' => 0, 'reserved' => 0]);
        $level = InventoryLevel::where('id', $level->id)->lockForUpdate()->first();
        if ($level->on_hand + $deltaOnHand < 0 || $level->reserved > $level->on_hand + $deltaOnHand) {
            abort(response()->json(['error' => ['code' => 'INVALID_STATE', 'message' => 'Dieu chinh kho khong hop le']], 409));
        }
        $level->increment('on_hand', $deltaOnHand);
        InventoryMovement::create([
            'warehouse_id' => $warehouseId, 'variant_id' => $variantId,
            'delta_on_hand' => $deltaOnHand, 'delta_reserved' => 0,
            'type' => 'adjust', 'reason' => $reason,
            'reference_type' => 'adjustment', 'reference_id' => $referenceId,
            'idempotency_key' => $idempotencyKey, 'actor_user_id' => $actorId, 'created_at' => now(),
        ]);
    }
}
