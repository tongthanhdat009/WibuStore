<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Orders\CheckoutService;
use App\Domain\Orders\OrderService;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCheckoutRequest;
use App\Http\Resources\OrderResource;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class CartController extends Controller
{
    protected function currentCart(Request $request, string $type = 'regular'): Cart
    {
        if ($request->user()) {
            return Cart::firstOrCreate(['user_id' => $request->user()->id, 'cart_type' => $type], ['expires_at' => now()->addDays(7)]);
        }
        $token = $request->header('X-Guest-Token', '');
        if (! $token) {
            abort(response()->json(['error' => ['code' => 'VALIDATION_ERROR', 'message' => 'Thieu X-Guest-Token']], 422));
        }

        return Cart::firstOrCreate(['guest_token_hash' => hash('sha256', $token), 'cart_type' => $type], ['expires_at' => now()->addDays(7)]);
    }

    public function show(Request $request)
    {
        $cart = $this->currentCart($request, $request->query('cart_type', 'regular'));
        $cart->load('items.variant.product');

        return response()->json(['data' => $cart]);
    }

    public function addItem(Request $request)
    {
        $data = $request->validate([
            'variant_id' => ['required', 'integer', 'exists:product_variants,id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:99'],
            'preorder_campaign_id' => ['nullable', 'integer', 'exists:preorder_campaigns,id'],
            'cart_type' => ['nullable', 'in:regular,preorder'],
        ]);
        $cart = $this->currentCart($request, $data['cart_type'] ?? 'regular');
        $item = CartItem::updateOrCreate(
            ['cart_id' => $cart->id, 'variant_id' => $data['variant_id'], 'preorder_campaign_id' => $data['preorder_campaign_id'] ?? null],
            ['quantity' => $data['quantity']]
        );

        return response()->json(['data' => $item], 201);
    }

    public function updateItem(Request $request, int $id)
    {
        $cart = $this->currentCart($request);
        $item = CartItem::where('cart_id', $cart->id)->findOrFail($id);
        $item->update($request->validate(['quantity' => ['required', 'integer', 'min:1', 'max:99']]));

        return response()->json(['data' => $item]);
    }

    public function removeItem(Request $request, int $id)
    {
        $cart = $this->currentCart($request);
        CartItem::where('cart_id', $cart->id)->where('id', $id)->delete();

        return response()->json(['data' => ['ok' => true]]);
    }

    public function preview(Request $request, CheckoutService $checkout)
    {
        $data = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.variant_id' => ['required', 'integer'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.preorder_campaign_id' => ['nullable', 'integer'],
            'checkout_type' => ['nullable', 'in:regular,preorder'],
            'coupon_code' => ['nullable', 'string'],
        ]);
        $calc = $checkout->preview($data, $request->user()?->id);
        unset($calc['coupon'], $calc['lines']);

        return response()->json(['data' => $calc]);
    }

    public function checkout(StoreCheckoutRequest $request, CheckoutService $checkout)
    {
        $order = $checkout->checkout($request->validated(), $request->user()?->id, $request->header('Idempotency-Key', Hash::make(uniqid())));

        return (new OrderResource($order->load(['items', 'payments', 'addresses'])))->response()->setStatusCode(201);
    }

    public function showOrder(Request $request, string $publicId)
    {
        $order = Order::with(['items', 'payments', 'addresses'])->where('public_id', $publicId)->firstOrFail();
        if ($request->user()) {
            if ($order->user_id && $order->user_id !== $request->user()->id) {
                abort(403);
            }
        } elseif (! hash_equals((string) $order->lookup_token_hash, hash('sha256', (string) $request->header('X-Order-Token', '')))) {
            abort(403);
        }

        return new OrderResource($order);
    }

    public function myOrders(Request $request)
    {
        $orders = Order::where('user_id', $request->user()->id)->orderByDesc('created_at')->paginate(12);

        return OrderResource::collection($orders);
    }

    public function invoice(Request $request, string $publicId)
    {
        $order = Order::with(['items', 'addresses'])->where('public_id', $publicId)->firstOrFail();
        $owned = $request->user() && $order->user_id && (int) $order->user_id === (int) $request->user()->id;
        $guestProof = $order->lookup_token_hash && hash_equals(
            (string) $order->lookup_token_hash,
            hash('sha256', (string) $request->header('X-Order-Token', ''))
        );
        if (! $owned && ! $guestProof) {
            abort(response()->json(['error' => ['code' => 'FORBIDDEN', 'message' => 'Khong co quyen xem hoa don']], 403));
        }
        $addr = $order->addresses->firstWhere('type', 'shipping');
        $address = $addr ? trim("{$addr->line1}, {$addr->district}, {$addr->province}", ', ') : '—';

        return \Barryvdh\DomPDF\Facade\Pdf::setOption(['defaultFont' => 'DejaVu Sans'])
            ->loadView('pdf.invoice', ['order' => $order, 'address' => $address])
            ->download("hoa-don-{$order->order_number}.pdf");
    }

    public function cancel(Request $request, string $publicId, CheckoutService $checkout)
    {
        $order = Order::where('public_id', $publicId)->firstOrFail();
        if ($request->user() && $order->user_id !== $request->user()->id) {
            abort(403);
        }
        app(OrderService::class)->cancel($order, $request->user()?->id);

        return response()->json(['data' => ['ok' => true]]);
    }

    public function lookup(Request $request)
    {
        $data = $request->validate(['order_number' => ['required', 'string'], 'email' => ['required', 'email']]);
        $order = Order::where('order_number', $data['order_number'])->where('email', $data['email'])->first();
        if (! $order) {
            return response()->json(['error' => ['code' => 'NOT_FOUND', 'message' => 'Khong tim thay don']], 404);
        }

        return response()->json(['data' => ['public_id' => $order->public_id, 'order_status' => $order->order_status, 'payment_status' => $order->payment_status]]);
    }
}
