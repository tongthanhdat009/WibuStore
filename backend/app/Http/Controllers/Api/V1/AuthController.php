<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
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

    public static function ensureHash(string $password): string
    {
        return Hash::needsRehash($password) ? Hash::make($password) : $password;
    }
}
