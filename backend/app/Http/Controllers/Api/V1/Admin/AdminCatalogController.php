<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Models\ActivityLog;
use App\Models\AnimeSeries;
use App\Models\Category;
use App\Models\InventoryLevel;
use App\Models\Manufacturer;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AdminCatalogController extends Controller
{
    public function products(Request $request)
    {
        $q = Product::with(['series:id,name,slug', 'manufacturer:id,name,slug', 'activeVariants:id,product_id,sku,price_amount', 'primaryMedia.asset'])->orderByDesc('created_at');
        if ($s = $request->query('status')) {
            $q->where('status', $s);
        }
        if ($v = $request->query('anime_series_id')) {
            $q->where('anime_series_id', $v);
        }
        if ($v = $request->query('manufacturer_id')) {
            $q->where('manufacturer_id', $v);
        }
        if ($s = $request->query('q')) {
            $q->where(fn ($w) => $w->where('name', 'ilike', '%'.$s.'%')
                ->orWhere('slug', 'ilike', '%'.$s.'%')
                ->orWhereHas('activeVariants', fn ($v) => $v->where('sku', 'ilike', '%'.$s.'%'))
                ->orWhereHas('series', fn ($sr) => $sr->where('name', 'ilike', '%'.$s.'%')));
        }
        $paginator = $q->paginate(min(100, max(1, (int) request()->query('per_page', 20))));
        $variantIds = $paginator->getCollection()->flatMap(fn ($p) => $p->activeVariants->pluck('id'))->unique();
        $stock = InventoryLevel::whereIn('variant_id', $variantIds)
            ->selectRaw('variant_id, SUM(on_hand) AS on_hand, SUM(reserved) AS reserved')
            ->groupBy('variant_id')->get()->keyBy('variant_id');
        $paginator->getCollection()->transform(function ($p) use ($stock) {
            $onHand = 0;
            $reserved = 0;
            foreach ($p->activeVariants as $v) {
                $onHand += (int) ($stock[$v->id]->on_hand ?? 0);
                $reserved += (int) ($stock[$v->id]->reserved ?? 0);
            }

            return [
                'id' => $p->id, 'public_id' => $p->public_id, 'name' => $p->name, 'slug' => $p->slug,
                'status' => $p->status, 'series' => $p->series?->name, 'manufacturer' => $p->manufacturer?->name,
                'min_price' => $p->activeVariants->min('price_amount'),
                'image_url' => $p->primaryMedia?->asset?->url(),
                'stock' => ['on_hand' => $onHand, 'reserved' => $reserved, 'available' => max(0, $onHand - $reserved)],
                'created_at' => $p->created_at?->toIso8601String(),
            ];
        });

        return response()->json($paginator);
    }

    public function storeProduct(StoreProductRequest $request)
    {
        $data = $request->validated();
        $product = Product::create([
            'name' => $data['name'], 'slug' => $data['slug'] ?? Str::slug($data['name']).'-'.substr((string) Str::uuid(), 0, 8),
            'anime_series_id' => $data['anime_series_id'] ?? null, 'manufacturer_id' => $data['manufacturer_id'] ?? null,
            'short_description' => $data['short_description'] ?? null, 'description' => $data['description'] ?? null,
            'attributes' => $data['attributes'] ?? [], 'status' => $data['status'] ?? 'draft',
        ]);
        if (! empty($data['category_ids'])) {
            $product->categories()->sync($data['category_ids']);
        }
        foreach ($data['variants'] ?? [] as $v) {
            ProductVariant::create(['product_id' => $product->id] + $v);
        }
        ActivityLog::record('product.created', 'product', (string) $product->id, [], ['name' => $product->name]);

        return response()->json(['data' => $product->load(['variants', 'categories'])], 201);
    }

    public function showProduct(int $id)
    {
        $p = Product::with(['series:id,name', 'manufacturer:id,name', 'variants', 'categories:id,name', 'media.asset'])->findOrFail($id);
        $data = $p->toArray();
        $data['media'] = $p->media->map(fn ($m) => [
            'id' => $m->id, 'sort_order' => $m->sort_order, 'is_primary' => $m->is_primary,
            'url' => $m->asset?->url(), 'alt' => $m->asset?->alt_text,
        ]);

        return response()->json(['data' => $data]);
    }

    public function updateProduct(Request $request, int $id)
    {
        $product = Product::findOrFail($id);
        $before = $product->only(['name', 'status']);
        $product->update($request->validate([
            'name' => ['sometimes', 'string'], 'short_description' => ['nullable', 'string'],
            'description' => ['nullable', 'string'], 'attributes' => ['nullable', 'array'],
            'anime_series_id' => ['nullable', 'integer'], 'manufacturer_id' => ['nullable', 'integer'],
        ]));
        ActivityLog::record('product.updated', 'product', (string) $id, $before, $product->only(['name', 'status']));

        return response()->json(['data' => $product->fresh()]);
    }

    public function publish(int $id)
    {
        $product = Product::with(['activeVariants', 'media'])->findOrFail($id);
        if ($product->activeVariants->isEmpty()) {
            return response()->json(['error' => ['code' => 'VALIDATION_ERROR', 'message' => 'Can it nhat 1 SKU active']], 422);
        }
        if ($product->media->isEmpty()) {
            return response()->json(['error' => ['code' => 'VALIDATION_ERROR', 'message' => 'Can it nhat 1 anh']], 422);
        }
        $product->update(['status' => 'active', 'published_at' => now()]);
        ActivityLog::record('product.published', 'product', (string) $id, [], []);

        return response()->json(['data' => $product->fresh()]);
    }

    public function archive(int $id)
    {
        $product = Product::findOrFail($id);
        $product->update(['status' => 'archived']);
        ActivityLog::record('product.archived', 'product', (string) $id, [], []);

        return response()->json(['data' => $product->fresh()]);
    }

    public function storeVariant(Request $request, int $productId)
    {
        $data = $request->validate([
            'sku' => ['required', 'string', 'unique:product_variants,sku'], 'label' => ['required', 'string'],
            'price_amount' => ['required', 'integer', 'min:0'], 'compare_at_amount' => ['nullable', 'integer', 'min:0'],
            'attributes' => ['nullable', 'array'],
        ]);
        $v = ProductVariant::create(['product_id' => $productId] + $data);

        return response()->json(['data' => $v], 201);
    }

    public function updateVariant(Request $request, int $id)
    {
        $v = ProductVariant::findOrFail($id);
        $v->update($request->validate([
            'label' => ['sometimes', 'string'], 'price_amount' => ['sometimes', 'integer', 'min:0'],
            'status' => ['sometimes', 'in:active,inactive'], 'attributes' => ['nullable', 'array'],
        ]));

        return response()->json(['data' => $v->fresh()]);
    }

    public function categories(Request $request)
    {
        if ($request->isMethod('post')) {
            $c = Category::create($request->validate(['name' => ['required', 'string'], 'slug' => ['required', 'string', 'unique:categories,slug'], 'parent_id' => ['nullable', 'integer']]));

            return response()->json(['data' => $c], 201);
        }

        return response()->json(['data' => Category::withCount('products')->orderBy('sort_order')->get()]);
    }

    public function series(Request $request)
    {
        if ($request->isMethod('post')) {
            $s = AnimeSeries::create($request->validate(['name' => ['required', 'string'], 'slug' => ['required', 'string', 'unique:anime_series,slug'], 'description' => ['nullable', 'string']]));

            return response()->json(['data' => $s], 201);
        }

        return response()->json(['data' => AnimeSeries::withCount('products')->orderBy('name')->get()]);
    }

    public function reviews(Request $request)
    {
        $q = \App\Models\ProductReview::with(['product:id,name,slug', 'user:id,name'])->orderByDesc('created_at');
        if ($s = $request->query('status')) {
            $q->where('status', $s);
        }
        $paginator = $q->paginate(min(100, max(1, (int) $request->query('per_page', 20))));

        return response()->json($paginator);
    }

    public function moderateReview(Request $request, int $id)
    {
        $review = \App\Models\ProductReview::findOrFail($id);
        $to = $request->validate(['status' => ['required', 'in:approved,rejected']])['status'];
        $review->update(['status' => $to]);
        ActivityLog::record('review.moderated', 'product_review', (string) $id, [], ['status' => $to]);

        return response()->json(['data' => $review->fresh()]);
    }

    public function manufacturers(Request $request)
    {
        if ($request->isMethod('post')) {
            $m = Manufacturer::create($request->validate(['name' => ['required', 'string'], 'slug' => ['required', 'string', 'unique:manufacturers,slug']]));

            return response()->json(['data' => $m], 201);
        }

        return response()->json(['data' => Manufacturer::withCount('products')->orderBy('name')->get()]);
    }
}
