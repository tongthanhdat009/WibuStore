<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductReview;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function index(string $slug)
    {
        $product = Product::where('slug', $slug)->firstOrFail();
        $base = ProductReview::approved()->where('product_id', $product->id);
        $paginator = $base->with('user:id,name')->orderByDesc('created_at')->paginate(10);
        $paginator->getCollection()->transform(fn ($r) => [
            'id' => $r->id, 'rating' => $r->rating, 'title' => $r->title, 'body' => $r->body,
            'author' => $r->user?->name ?? 'Khách', 'created_at' => $r->created_at?->toIso8601String(),
        ]);
        $stats = [
            'count' => (clone $base)->count(),
            'average' => round((clone $base)->avg('rating') ?? 0, 1),
        ];

        return response()->json(['data' => $paginator->items(), 'meta' => ['stats' => $stats, 'pagination' => [
            'page' => $paginator->currentPage(), 'last_page' => $paginator->lastPage(), 'total' => $paginator->total(),
        ]]]);
    }

    public function eligibility(string $slug, Request $request)
    {
        $product = Product::where('slug', $slug)->firstOrFail();

        return response()->json(['data' => ['eligible' => $this->purchasedOrder($product->id, $request->user()->id) !== null]]);
    }

    public function store(string $slug, Request $request)
    {
        $product = Product::where('slug', $slug)->firstOrFail();
        $data = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'title' => ['nullable', 'string', 'max:255'],
            'body' => ['required', 'string', 'min:10', 'max:2000'],
        ]);
        if (ProductReview::where('product_id', $product->id)->where('user_id', $request->user()->id)->exists()) {
            return response()->json(['error' => ['code' => 'INVALID_STATE', 'message' => 'Bạn đã đánh giá sản phẩm này']], 409);
        }
        $order = $this->purchasedOrder($product->id, $request->user()->id);
        if (! $order) {
            return response()->json(['error' => ['code' => 'FORBIDDEN', 'message' => 'Chỉ khách đã mua hàng mới được đánh giá']], 403);
        }
        $review = ProductReview::create([
            'product_id' => $product->id, 'user_id' => $request->user()->id, 'order_id' => $order->id,
            'rating' => $data['rating'], 'title' => $data['title'] ?? null, 'body' => $data['body'],
            'status' => 'pending',
        ]);

        return response()->json(['data' => ['id' => $review->id, 'status' => $review->status]], 201);
    }

    protected function purchasedOrder(int $productId, int $userId): ?Order
    {
        $variantIds = Product::find($productId)?->variants()->pluck('id') ?? collect();

        return Order::where('user_id', $userId)
            ->whereNotIn('order_status', ['cancelled', 'expired'])
            ->where(function ($q) {
                $q->whereIn('payment_status', ['paid', 'partially_paid'])->orWhereIn('order_status', ['delivered', 'completed']);
            })
            ->whereHas('items', fn ($q) => $q->whereIn('variant_id', $variantIds))
            ->latest('id')
            ->first();
    }
}
