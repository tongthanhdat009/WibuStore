<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
            'phone' => ['nullable', 'string', 'max:32'],
        ]);
        $user = User::create(['name' => $data['name'], 'email' => $data['email'], 'password' => $data['password'], 'phone' => $data['phone'] ?? null]);
        if ($role = Role::where('slug', 'customer')->first()) {
            $user->roles()->attach($role);
        }
        Auth::login($user);
        $request->session()->regenerate();

        return response()->json(['data' => $this->profile($user)], 201);
    }

    public function login(Request $request)
    {
        $data = $request->validate(['email' => ['required', 'email'], 'password' => ['required', 'string']]);
        if (! Auth::attempt(['email' => $data['email'], 'password' => $data['password']], $request->boolean('remember'))) {
            return response()->json(['error' => ['code' => 'UNAUTHENTICATED', 'message' => 'Thong tin dang nhap sai']], 401);
        }
        $request->session()->regenerate();
        $user = $request->user();
        $user->update(['last_login_at' => now()]);

        return response()->json(['data' => $this->profile($user)]);
    }

    public function logout(Request $request)
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['data' => ['ok' => true]]);
    }

    public function me(Request $request)
    {
        return response()->json(['data' => $this->profile($request->user())]);
    }

    public function updateMe(Request $request)
    {
        $user = $request->user();
        $data = $request->validate(['name' => ['sometimes', 'string', 'max:255'], 'phone' => ['nullable', 'string', 'max:32']]);
        $user->update($data);

        return response()->json(['data' => $this->profile($user->fresh())]);
    }

    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => ['required', 'email']]);
        $status = \Illuminate\Support\Facades\Password::sendResetLink($request->only('email'));

        return $status === \Illuminate\Support\Facades\Password::RESET_LINK_SENT
            ? response()->json(['data' => ['ok' => true]])
            : response()->json(['error' => ['code' => 'VALIDATION_ERROR', 'message' => __($status)]], 422);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);
        $status = \Illuminate\Support\Facades\Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill(['password' => $password, 'remember_token' => Str::random(60)])->save();
            }
        );

        return $status === \Illuminate\Support\Facades\Password::PASSWORD_RESET
            ? response()->json(['data' => ['ok' => true]])
            : response()->json(['error' => ['code' => 'VALIDATION_ERROR', 'message' => __($status)]], 422);
    }

    public function wishlist(Request $request)
    {
        $items = DB::table('wishlists')
            ->join('products', 'products.id', '=', 'wishlists.product_id')
            ->leftJoin('product_variants', function ($j) {
                $j->on('product_variants.product_id', '=', 'products.id')->where('product_variants.status', 'active');
            })
            ->leftJoin('product_media', function ($j) {
                $j->on('product_media.product_id', '=', 'products.id')->where('product_media.is_primary', true);
            })
            ->leftJoin('media_assets', 'media_assets.id', '=', 'product_media.media_asset_id')
            ->where('wishlists.user_id', $request->user()->id)
            ->groupBy('wishlists.id', 'products.public_id', 'products.name', 'products.slug', 'media_assets.object_key')
            ->orderByDesc('wishlists.created_at')
            ->selectRaw('products.public_id as id, products.name, products.slug, MIN(product_variants.price_amount) as price_amount, media_assets.object_key as image_key')
            ->limit(100)
            ->get();

        $base = rtrim((string) config('filesystems.disks.s3.url'), '/');
        $root = trim((string) config('filesystems.disks.s3.root'), '/');
        $items->transform(function ($i) use ($base, $root) {
            $i->image = $i->image_key && $base !== ''
                ? $base.'/'.($root !== '' ? $root.'/' : '').implode('/', array_map('rawurlencode', explode('/', $i->image_key)))
                : null;
            unset($i->image_key);

            return $i;
        });

        return response()->json(['data' => $items]);
    }

    public function storeWishlist(Request $request)
    {
        $data = $request->validate(['product_id' => ['required', 'string']]);
        $product = Product::where('public_id', $data['product_id'])->orWhere('slug', $data['product_id'])->first();
        if (! $product) {
            return response()->json(['error' => ['code' => 'NOT_FOUND', 'message' => 'Khong tim thay san pham']], 404);
        }
        DB::table('wishlists')->updateOrInsert(
            ['user_id' => $request->user()->id, 'product_id' => $product->id],
            ['created_at' => now(), 'updated_at' => now()]
        );

        return response()->json(['data' => ['ok' => true]], 201);
    }

    public function destroyWishlist(Request $request, string $product)
    {
        $productId = Product::where('public_id', $product)->orWhere('slug', $product)->value('id');
        if ($productId) {
            DB::table('wishlists')
                ->where('user_id', $request->user()->id)->where('product_id', $productId)->delete();
        }

        return response()->json(['data' => ['ok' => true]]);
    }

    public function addresses(Request $request)
    {
        return response()->json(['data' => $request->user()->addresses()->orderByDesc('is_default')->get()]);
    }

    public function storeAddress(Request $request)
    {
        $data = $request->validate([
            'recipient_name' => ['required', 'string'], 'phone' => ['required', 'string'],
            'line1' => ['required', 'string'], 'ward' => ['nullable', 'string'],
            'district' => ['nullable', 'string'], 'province' => ['nullable', 'string'],
        ]);
        $addr = $request->user()->addresses()->create($data);

        return response()->json(['data' => $addr], 201);
    }

    protected function profile(User $user): array
    {
        $user->loadMissing('roles.permissions');

        return [
            'id' => $user->public_id, 'name' => $user->name, 'email' => $user->email,
            'roles' => $user->roles->pluck('slug'),
            'permissions' => $user->roles->flatMap->permissions->pluck('slug')->unique()->values(),
        ];
    }
}
