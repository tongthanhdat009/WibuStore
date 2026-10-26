<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductDetailResource;
use App\Http\Resources\ProductResource;
use App\Models\AnimeSeries;
use App\Models\Category;
use App\Models\CmsPage;
use App\Models\Manufacturer;
use App\Models\PreorderCampaign;
use App\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class CatalogController extends Controller
{
    public function health()
    {
        return response()->json(['data' => ['status' => 'ok', 'service' => 'wibu-store-api'], 'meta' => ['request_id' => request()->header('X-Request-ID', uniqid('req_'))]]);
    }

    public function index(Request $request)
    {
        $q = Product::published()->with(['series', 'manufacturer', 'activeVariants.inventoryLevels', 'activeVariants.openPreorderCampaigns', 'primaryMedia.asset']);
        $relevanceBindings = [];
        $relevanceExpr = null;
        if ($s = $request->query('q')) {
            // Tìm theo từng từ (OR) trên tên + series + hãng, xếp hạng theo số từ khớp.
            // Khớp nguyên cụm (AND ngầm qua 1 LIKE) làm "rengoku kyojuro kimetsu"
            // trắng trơn dù shop có figure Rengoku.
            $words = array_slice(array_values(array_unique(preg_split('/\s+/', mb_strtolower(trim($s)), -1, PREG_SPLIT_NO_EMPTY))), 0, 8);
            // Tên gọi tắt/tên Nhật phổ biến -> tên series trong DB.
            $aliases = [
                'kimetsu' => ['demon slayer', 'yaiba'], 'kny' => ['demon slayer'],
                'jjk' => ['jujutsu kaisen'], 'shingeki' => ['attack on titan'], 'aot' => ['attack on titan'],
                'bnha' => ['my hero academia', 'hero academia'], 'hxh' => ['hunter x hunter'],
                'sao' => ['sword art online'], 'snk' => ['attack on titan'],
                'op' => ['one piece'], 'ds' => ['demon slayer'], 'mha' => ['my hero academia'],
            ];
            $terms = $words;
            foreach ($words as $w) {
                foreach ($aliases[$w] ?? [] as $alt) {
                    $terms[] = $alt;
                }
            }
            $terms = array_values(array_unique($terms));
            $q->where(function ($w) use ($terms) {
                foreach ($terms as $t) {
                    $w->orWhere('name', 'ilike', '%'.$t.'%')
                        ->orWhereHas('series', fn ($sr) => $sr->where('name', 'ilike', '%'.$t.'%')->orWhere('slug', 'ilike', '%'.str_replace(' ', '-', $t).'%'))
                        ->orWhereHas('manufacturer', fn ($m) => $m->where('name', 'ilike', '%'.$t.'%'));
                }
            });
            // Điểm relevance: mỗi từ gốc khớp tên sản phẩm = 1 điểm.
            $parts = [];
            foreach ($words as $word) {
                $like = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $word).'%';
                $parts[] = '(CASE WHEN products.name ILIKE ? THEN 2 WHEN products.name ILIKE ? THEN 1 ELSE 0 END)';
                $relevanceBindings[] = $word.'%';
                $relevanceBindings[] = $like;
            }
            if ($parts !== []) {
                $relevanceExpr = '('.implode(' + ', $parts).')';
                $q->orderByRaw($relevanceExpr.' DESC', $relevanceBindings);
            }
        }
        if ($cat = $request->query('category')) {
            $q->whereHas('categories', fn ($w) => $w->where('slug', $cat));
        }
        if ($s = $request->query('anime_series')) {
            $q->whereHas('series', fn ($w) => $w->where('slug', $s));
        }
        if ($s = $request->query('manufacturer')) {
            $q->whereHas('manufacturer', fn ($w) => $w->where('slug', $s));
        }
        if ($min = $request->query('min_price')) {
            $q->whereHas('activeVariants', fn ($w) => $w->where('price_amount', '>=', (int) $min));
        }
        if ($max = $request->query('max_price')) {
            $q->whereHas('activeVariants', fn ($w) => $w->where('price_amount', '<=', (int) $max));
        }
        $sort = $request->query('sort', 'newest');
        match ($sort) {
            'price_asc' => $q->orderByRaw('(SELECT MIN(price_amount) FROM product_variants WHERE product_id = products.id AND status = \'active\') ASC'),
            'price_desc' => $q->orderByRaw('(SELECT MIN(price_amount) FROM product_variants WHERE product_id = products.id AND status = \'active\') DESC'),
            'name_asc' => $q->orderBy('name'),
            default => $q->orderByDesc('published_at'),
        };
        $perPage = min(48, max(1, (int) $request->query('per_page', 12)));
        $p = $q->paginate($perPage);

        return ProductResource::collection($p);
    }

    public function show(string $slug)
    {
        $p = Product::published()->with(['series', 'manufacturer', 'categories', 'activeVariants.inventoryLevels', 'activeVariants.openPreorderCampaigns', 'media.asset'])->where('slug', $slug)->firstOrFail();

        return new ProductDetailResource($p);
    }

    public function categories()
    {
        return response()->json(['data' => Category::active()->orderBy('sort_order')->get(['id', 'parent_id', 'name', 'slug'])]);
    }

    public function series()
    {
        return response()->json(['data' => AnimeSeries::active()->orderBy('name')->get(['id', 'name', 'slug'])]);
    }

    public function manufacturers()
    {
        return response()->json(['data' => Manufacturer::orderBy('name')->get(['id', 'name', 'slug'])]);
    }

    public function preorders()
    {
        $c = PreorderCampaign::open()->with('variant.product')->paginate(12);
        $items = collect($c->items())->map(fn ($camp) => array_merge(
            $camp instanceof Model ? $camp->toArray() : (array) $camp,
            ['available' => max(0, (int) ($camp->quota ?? 0) - (int) ($camp->reserved_quantity ?? 0))]
        ));

        return response()->json(['data' => $items, 'meta' => ['total' => $c->total(), 'page' => $c->currentPage()]]);
    }

    public function preorderShow(int $id)
    {
        $c = PreorderCampaign::with('variant.product')->findOrFail($id);

        return response()->json(['data' => $c->append('available')]);
    }

    public function home()
    {
        $page = CmsPage::where('slug', 'home')->with('publishedRevision')->first();

        return response()->json(['data' => $page?->publishedRevision?->content ?? ['sections' => []]]);
    }

    public function collections()
    {
        $pages = CmsPage::where('slug', 'like', 'collection-%')
            ->whereNotNull('current_published_revision_id')
            ->orderBy('slug')->get(['slug', 'title']);

        return response()->json(['data' => $pages]);
    }

    public function collection(string $slug)
    {
        $page = CmsPage::where('slug', 'collection-'.$slug)->with('publishedRevision')->firstOrFail();

        return response()->json(['data' => $page->publishedRevision?->content ?? []]);
    }
}
