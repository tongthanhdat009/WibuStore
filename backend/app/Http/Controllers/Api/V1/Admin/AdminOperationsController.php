<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Domain\Inventory\InventoryService;
use App\Domain\Orders\OrderService;
use App\Domain\Payments\PaymentService;
use App\Domain\Preorders\PreorderService;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\InventoryLevel;
use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PreorderCampaign;
use App\Models\PreorderReservation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AdminOperationsController extends Controller
{
    public function overview()
    {
        $revenue = (int) Payment::where('status', 'confirmed')->whereIn('kind', ['full', 'deposit', 'balance'])->sum('amount');

        return response()->json(['data' => [
            'revenue_collected' => $revenue,
            'orders_pending' => Order::where('order_status', 'pending')->count(),
            'orders_processing' => Order::where('order_status', 'processing')->count(),
            'preorders_open' => PreorderCampaign::where('status', 'open')->count(),
            'low_stock_skus' => InventoryLevel::whereRaw('on_hand - reserved <= 3')->count(),
        ]]);
    }

    public function orders(Request $request)
    {
        $q = Order::withCount('items')->orderByDesc('created_at');
        foreach (['order_status', 'payment_status', 'checkout_type'] as $f) {
            if ($v = $request->query($f)) {
                $q->where($f, $v);
            }
        }
        if ($s = $request->query('q')) {
            $q->where(fn ($w) => $w->where('order_number', 'ilike', "%{$s}%")->orWhere('customer_name', 'ilike', "%{$s}%")->orWhere('phone', 'ilike', "%{$s}%"));
        }

        return response()->json($q->paginate(min(100, max(1, (int) request()->query('per_page', 20)))));
    }

    public function showOrder(int $id)
    {
        return response()->json(['data' => Order::with(['items', 'payments', 'addresses', 'history'])->findOrFail($id)]);
    }

    public function transition(Request $request, int $id, OrderService $orders)
    {
        $data = $request->validate(['to' => ['required', 'string'], 'reason' => ['nullable', 'string']]);
        $order = $orders->transition(Order::findOrFail($id), $data['to'], $request->user()->id, $data['reason'] ?? null);

        return response()->json(['data' => $order]);
    }

    public function inventory(Request $request)
    {
        $q = InventoryLevel::with(['variant:id,sku,label,product_id', 'variant.product:id,name'])->orderBy('updated_at', 'desc');
        if ($v = $request->query('variant_id')) {
            $q->where('variant_id', $v);
        }
        $paginator = $q->paginate(min(100, max(1, (int) request()->query('per_page', 50))));
        $paginator->getCollection()->transform(fn ($l) => [
            'id' => $l->id, 'warehouse_id' => $l->warehouse_id, 'variant_id' => $l->variant_id,
            'sku' => $l->variant?->sku, 'label' => $l->variant?->label,
            'product_name' => $l->variant?->product?->name,
            'on_hand' => $l->on_hand, 'reserved' => $l->reserved,
            'available' => max(0, $l->on_hand - $l->reserved),
            'updated_at' => $l->updated_at?->toIso8601String(),
        ]);

        return response()->json($paginator);
    }

    public function adjust(Request $request, InventoryService $inventory)
    {
        $data = $request->validate([
            'warehouse_id' => ['nullable', 'integer'], 'variant_id' => ['required', 'integer', 'exists:product_variants,id'],
            'delta_on_hand' => ['required', 'integer', 'not_in:0'], 'reason' => ['required', 'string'],
        ]);
        $wh = $data['warehouse_id'] ?? $inventory->defaultWarehouseId();
        $inventory->adjust($wh, $data['variant_id'], $data['delta_on_hand'], $data['reason'], Str::uuid(), 'adj:'.Str::uuid(), $request->user()->id);
        ActivityLog::record('inventory.adjusted', 'variant', (string) $data['variant_id'], [], $data);

        return response()->json(['data' => ['ok' => true]], 201);
    }

    public function movements()
    {
        $paginator = InventoryMovement::with(['variant:id,sku,product_id', 'variant.product:id,name'])->orderByDesc('created_at')->paginate(min(100, max(1, (int) request()->query('per_page', 50))));
        $paginator->getCollection()->transform(fn ($m) => [
            'id' => $m->id, 'type' => $m->type, 'reason' => $m->reason,
            'delta_on_hand' => $m->delta_on_hand, 'delta_reserved' => $m->delta_reserved,
            'reference_type' => $m->reference_type, 'reference_id' => $m->reference_id,
            'sku' => $m->variant?->sku, 'product_name' => $m->variant?->product?->name,
            'created_at' => $m->created_at?->toIso8601String(),
        ]);

        return response()->json($paginator);
    }

    public function campaigns(Request $request)
    {
        if ($request->isMethod('post')) {
            $c = PreorderCampaign::create($request->validate([
                'variant_id' => ['required', 'integer', 'exists:product_variants,id'],
                'quota' => ['required', 'integer', 'min:0'],
                'deposit_per_unit_amount' => ['required', 'integer', 'min:0'],
                'sale_price_per_unit_amount' => ['required', 'integer', 'min:0'],
                'opens_at' => ['nullable', 'date'], 'closes_at' => ['nullable', 'date'],
                'expected_release_at' => ['nullable', 'date'], 'expected_arrival_at' => ['nullable', 'date'],
            ]));

            return response()->json(['data' => $c], 201);
        }

        $paginator = PreorderCampaign::with(['variant:id,sku,label,product_id', 'variant.product:id,name'])->orderByDesc('created_at')->paginate(min(100, max(1, (int) request()->query('per_page', 20))));
        $paginator->getCollection()->transform(fn ($c) => array_merge($c->toArray(), [
            'available' => max(0, $c->quota - $c->reserved_quantity),
            'sku' => $c->variant?->sku, 'variant_label' => $c->variant?->label,
            'product_name' => $c->variant?->product?->name,
        ]));

        return response()->json($paginator);
    }

    public function showCampaign(int $id)
    {
        $c = PreorderCampaign::with(['variant:id,sku,label,product_id', 'variant.product:id,name'])->findOrFail($id);
        $reservations = PreorderReservation::with(['orderItem.order:id,order_number,customer_name,phone'])
            ->where('campaign_id', $id)->orderByDesc('created_at')->limit(100)->get()
            ->map(fn ($r) => [
                'id' => $r->id, 'quantity' => $r->quantity, 'status' => $r->status,
                'deposit_required_amount' => $r->deposit_required_amount,
                'balance_due_amount' => $r->balance_due_amount,
                'order_id' => $r->orderItem?->order?->id,
                'order_number' => $r->orderItem?->order?->order_number,
                'customer_name' => $r->orderItem?->order?->customer_name,
                'customer_phone' => $r->orderItem?->order?->phone,
            ]);

        return response()->json(['data' => array_merge($c->toArray(), [
            'available' => max(0, $c->quota - $c->reserved_quantity),
            'sku' => $c->variant?->sku, 'variant_label' => $c->variant?->label,
            'product_name' => $c->variant?->product?->name,
            'reservations' => $reservations,
        ])]);
    }

    public function openCampaign(int $id)
    {
        $c = PreorderCampaign::findOrFail($id);
        $c->update(['status' => 'open', 'opens_at' => $c->opens_at ?? now()]);

        return response()->json(['data' => $c->fresh()]);
    }

    public function closeCampaign(int $id)
    {
        $c = PreorderCampaign::findOrFail($id);
        $c->update(['status' => 'closed', 'closes_at' => now()]);

        return response()->json(['data' => $c->fresh()]);
    }

    public function reservations()
    {
        return response()->json(PreorderReservation::orderByDesc('created_at')->paginate(min(100, max(1, (int) request()->query('per_page', 20)))));
    }

    public function confirmPayment(Request $request, int $id, PaymentService $payments)
    {
        $p = $payments->confirm($id, $request->user()->id, $request->input('provider_reference'));

        return response()->json(['data' => $p]);
    }

    public function rejectPayment(Request $request, int $id, PaymentService $payments)
    {
        $p = $payments->reject($id, $request->user()->id, $request->validate(['reason' => ['required', 'string']])['reason']);

        return response()->json(['data' => $p]);
    }

    public function customers(Request $request)
    {
        $q = User::query()->orderByDesc('created_at');
        if ($s = $request->query('q')) {
            $q->where(fn ($w) => $w->where('name', 'ilike', "%{$s}%")->orWhere('email', 'ilike', "%{$s}%"));
        }

        return response()->json($q->paginate(min(100, max(1, (int) request()->query('per_page', 20))))->through(fn ($u) => $u->only(['id', 'public_id', 'name', 'email', 'phone', 'status'])));
    }

    public function showCustomer(int $id)
    {
        $user = User::with(['roles:id,name,slug', 'addresses'])->findOrFail($id);
        $orders = Order::where('user_id', $id)->orderByDesc('created_at')->limit(20)->get();
        $spent = (int) Order::where('user_id', $id)->whereNotIn('order_status', ['cancelled', 'expired'])->sum('total_amount');

        return response()->json(['data' => [
            'user' => $user->only(['id', 'public_id', 'name', 'email', 'phone', 'status']) + ['roles' => $user->roles, 'addresses' => $user->addresses],
            'stats' => ['orders_count' => $orders->count(), 'total_spent' => $spent],
            'orders' => $orders,
        ]]);
    }

    public function salesReport()
    {
        $rows = Order::selectRaw("date_trunc('day', placed_at) as day, COUNT(*) as orders, SUM(total_amount) as gmv, SUM(paid_amount) as collected")
            ->where('order_status', '!=', 'cancelled')->groupBy('day')->orderBy('day', 'desc')->limit(30)->get();

        return response()->json(['data' => $rows]);
    }

    public function webhook(Request $request, string $provider, PaymentService $payments)
    {
        $data = $request->validate(['event_id' => ['required', 'string'], 'payment_id' => ['nullable', 'integer'], 'status' => ['nullable', 'string'], 'amount' => ['nullable', 'integer']]);

        return response()->json(['data' => $payments->handleWebhook($provider, $data['event_id'], $data)]);
    }

    public function __construct(protected PreorderService $preorders) {}
}
