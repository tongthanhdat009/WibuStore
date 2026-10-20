<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\CmsPage;
use App\Models\CmsPageRevision;
use App\Models\Coupon;
use App\Models\MediaAsset;
use App\Models\ProductMedia;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AdminCmsController extends Controller
{
    public function media(Request $request)
    {
        if ($request->isMethod('post')) {
            $request->validate(['file' => ['required', 'file', 'max:10240', 'mimetypes:image/jpeg,image/png,image/webp,image/avif']]);
            $file = $request->file('file');
            $visibility = $request->input('visibility', 'public');
            $key = $visibility.'/products/'.now()->format('Y/m').'/'.Str::uuid().'.'.$file->getClientOriginalExtension();
            Storage::disk(config('filesystems.default'))->put($key, file_get_contents($file->getRealPath()));
            [$w, $h] = @getimagesize($file->getRealPath()) ?: [null, null];
            $asset = MediaAsset::create([
                'object_key' => $key, 'disk' => config('filesystems.default'), 'mime_type' => $file->getMimeType(),
                'file_size_bytes' => $file->getSize(), 'width' => $w, 'height' => $h,
                'checksum' => hash_file('sha256', $file->getRealPath()), 'visibility' => $visibility,
                'status' => 'ready', 'uploaded_by' => $request->user()->id,
            ]);

            return response()->json(['data' => $asset], 201);
        }

        $mq = MediaAsset::orderByDesc('created_at');
        if ($s = $request->query('q')) {
            $mq->where('object_key', 'ilike', "%{$s}%");
        }
        if ($prefix = $request->query('prefix')) {
            $mq->where('object_key', 'ilike', '%'.ltrim($prefix, '/').'%');
        }
        $paginator = $mq->paginate(min(100, max(1, (int) request()->query('per_page', 24))));
        $paginator->getCollection()->transform(fn ($a) => array_merge($a->toArray(), [
            'file_name' => basename($a->object_key),
            'url' => $a->url(),
        ]));

        return response()->json($paginator);
    }

    public function completeMedia(int $id)
    {
        $asset = MediaAsset::findOrFail($id);
        $asset->update(['status' => 'ready']);

        return response()->json(['data' => $asset->fresh()]);
    }

    public function deleteMedia(int $id)
    {
        $asset = MediaAsset::findOrFail($id);
        if (ProductMedia::where('media_asset_id', $id)->exists()) {
            return response()->json(['error' => ['code' => 'INVALID_STATE', 'message' => 'Media dang duoc tham chieu']], 409);
        }
        $asset->delete();

        return response()->json(['data' => ['ok' => true]]);
    }

    public function pages(Request $request)
    {
        if ($request->isMethod('post')) {
            $page = CmsPage::firstOrCreate(
                ['slug' => $request->validate(['slug' => ['required', 'string', 'max:255']])['slug']],
                ['title' => $request->input('title', $request->input('slug'))]
            );

            return response()->json(['data' => $page], 201);
        }

        return response()->json(['data' => CmsPage::with('publishedRevision:id,page_id,version_number,status')->orderBy('slug')->get()]);
    }

    public function revisions(string $slug)
    {
        $page = CmsPage::where('slug', $slug)->firstOrFail();

        return response()->json(['data' => $page->revisions()->orderByDesc('version_number')->get()]);
    }

    public function storeRevision(Request $request, string $slug)
    {
        $page = CmsPage::firstOrCreate(['slug' => $slug], ['title' => $slug]);
        $version = ($page->revisions()->max('version_number') ?? 0) + 1;
        $rev = CmsPageRevision::create([
            'page_id' => $page->id, 'version_number' => $version, 'status' => 'draft',
            'content' => $request->validate(['content' => ['required', 'array']])['content'],
            'created_by' => $request->user()->id,
        ]);

        return response()->json(['data' => $rev], 201);
    }

    public function publishRevision(string $slug, int $id)
    {
        $page = CmsPage::where('slug', $slug)->firstOrFail();
        $rev = CmsPageRevision::where('page_id', $page->id)->findOrFail($id);
        $rev->update(['status' => 'published', 'published_by' => request()->user()->id, 'published_at' => now()]);
        $page->update(['current_published_revision_id' => $rev->id]);
        ActivityLog::record('cms.published', 'cms_page', (string) $page->id, [], ['revision' => $rev->version_number]);

        return response()->json(['data' => $rev->fresh()]);
    }

    public function coupons(Request $request)
    {
        if ($request->isMethod('post')) {
            $c = Coupon::create($request->validate([
                'code' => ['required', 'string', 'unique:coupons,code'], 'discount_type' => ['required', 'in:fixed,percent'],
                'discount_value' => ['required', 'integer', 'min:0'],
            ]));

            return response()->json(['data' => $c], 201);
        }

        return response()->json(Coupon::orderByDesc('created_at')->paginate(min(100, max(1, (int) request()->query('per_page', 20)))));
    }

    public function logs()
    {
        $paginator = ActivityLog::with(['actor:id,name'])->orderByDesc('created_at')
            ->paginate(min(100, max(1, (int) request()->query('per_page', 20))));
        $paginator->getCollection()->transform(fn ($l) => [
            'id' => $l->id, 'action' => $l->action,
            'subject_type' => $l->subject_type, 'subject_id' => $l->subject_id,
            'actor_name' => $l->actor?->name,
            'request_id' => $l->request_id, 'created_at' => $l->created_at,
        ]);

        return response()->json($paginator);
    }

    public function users()
    {
        return response()->json(User::with('roles:id,name,slug')->orderByDesc('created_at')->paginate(min(100, max(1, (int) request()->query('per_page', 20)))));
    }

    public function roles()
    {
        return response()->json(['data' => Role::with('permissions:id,slug,name')->orderBy('name')->get()]);
    }

    public function updateRoles(Request $request, int $id)
    {
        $actor = $request->user();
        $target = User::findOrFail($id);
        $roleIds = $request->validate(['role_ids' => ['required', 'array'], 'role_ids.*' => ['integer', 'exists:roles,id']])['role_ids'];
        $superAdmin = Role::where('slug', 'super_admin')->first();
        if ($superAdmin && in_array($superAdmin->id, $roleIds) && ! $actor->hasPermission('users.manage')) {
            abort(403);
        }
        if ($target->id === $actor->id) {
            return response()->json(['error' => ['code' => 'FORBIDDEN', 'message' => 'Khong tu sua vai tro cua chinh minh']], 403);
        }
        $target->roles()->sync($roleIds);
        ActivityLog::record('user.roles_updated', 'user', (string) $id, [], ['roles' => $roleIds]);

        return response()->json(['data' => $target->load('roles')]);
    }

    public function settings(Request $request)
    {
        if ($request->isMethod('patch')) {
            foreach ($request->validate(['settings' => ['required', 'array']])['settings'] as $k => $v) {
                Setting::updateOrCreate(['key' => $k], ['value' => $v]);
            }

            return response()->json(['data' => ['ok' => true]]);
        }

        return response()->json(['data' => Setting::all()->pluck('value', 'key')]);
    }
}
