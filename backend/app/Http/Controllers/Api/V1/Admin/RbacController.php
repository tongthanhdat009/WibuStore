<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class RbacController extends Controller
{
    public function permissions()
    {
        return response()->json(['data' => Permission::orderBy('slug')->get(['id', 'name', 'slug', 'description'])]);
    }

    public function roles()
    {
        return response()->json(['data' => Role::with('permissions:id,slug,name')->orderBy('name')->get()]);
    }

    public function storeRole(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:roles,slug'],
            'description' => ['nullable', 'string'],
            'permission_ids' => ['nullable', 'array'],
            'permission_ids.*' => ['integer', 'exists:permissions,id'],
        ]);
        $role = Role::create([
            'name' => $data['name'], 'slug' => $data['slug'],
            'description' => $data['description'] ?? null,
        ]);
        $role->permissions()->sync($data['permission_ids'] ?? []);
        ActivityLog::record('role.created', 'role', (string) $role->id, [], ['slug' => $role->slug]);

        return response()->json(['data' => $role->load('permissions')], 201);
    }

    public function updateRole(Request $request, int $id)
    {
        $role = Role::findOrFail($id);
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'permission_ids' => ['nullable', 'array'],
            'permission_ids.*' => ['integer', 'exists:permissions,id'],
        ]);
        $before = $role->only(['name', 'description']);
        $role->update(array_filter($data, fn ($k) => in_array($k, ['name', 'description']), ARRAY_FILTER_USE_KEY));
        if (array_key_exists('permission_ids', $data)) {
            $role->permissions()->sync($data['permission_ids']);
        }
        ActivityLog::record('role.updated', 'role', (string) $role->id, $before, $role->only(['name', 'description']));

        return response()->json(['data' => $role->load('permissions')]);
    }

    public function destroyRole(int $id)
    {
        $role = Role::findOrFail($id);
        if (in_array($role->slug, ['super_admin', 'customer'], true)) {
            return response()->json(['error' => ['code' => 'FORBIDDEN', 'message' => 'Khong the xoa vai tro he thong']], 403);
        }
        if ($role->users()->exists()) {
            return response()->json(['error' => ['code' => 'INVALID_STATE', 'message' => 'Vai tro dang gan cho nhan su, go gan truoc khi xoa']], 409);
        }
        $role->permissions()->detach();
        $role->delete();
        ActivityLog::record('role.deleted', 'role', (string) $id, ['slug' => $role->slug], []);

        return response()->json(['data' => ['ok' => true]]);
    }

    public function staff(Request $request)
    {
        $q = User::with('roles:id,name,slug')->orderByDesc('created_at')
            ->where(fn ($w) => $w->whereDoesntHave('roles')
                ->orWhereHas('roles', fn ($r) => $r->where('slug', '!=', 'customer')));
        if ($s = $request->query('q')) {
            $q->where(fn ($w) => $w->where('name', 'ilike', "%{$s}%")->orWhere('email', 'ilike', "%{$s}%"));
        }

        return response()->json($q->paginate(min(100, max(1, (int) request()->query('per_page', 20)))));
    }

    public function storeStaff(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', Password::min(8)],
            'phone' => ['nullable', 'string', 'max:32'],
            'role_ids' => ['nullable', 'array'],
            'role_ids.*' => ['integer', 'exists:roles,id'],
        ]);
        $user = User::create([
            'name' => $data['name'], 'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'phone' => $data['phone'] ?? null, 'status' => 'active',
        ]);
        $user->roles()->sync($data['role_ids'] ?? []);
        ActivityLog::record('user.created', 'user', (string) $user->id, [], ['email' => $user->email]);

        return response()->json(['data' => $user->load('roles')], 201);
    }

    public function updateStaff(Request $request, int $id)
    {
        $user = User::findOrFail($id);
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:32'],
            'status' => ['sometimes', 'in:active,suspended'],
            'role_ids' => ['nullable', 'array'],
            'role_ids.*' => ['integer', 'exists:roles,id'],
        ]);
        if (array_key_exists('role_ids', $data)) {
            if ($user->id === $request->user()->id) {
                return response()->json(['error' => ['code' => 'FORBIDDEN', 'message' => 'Khong tu sua vai tro cua chinh minh']], 403);
            }
            $user->roles()->sync($data['role_ids']);
        }
        $user->update(array_filter($data, fn ($k) => in_array($k, ['name', 'phone', 'status']), ARRAY_FILTER_USE_KEY));
        ActivityLog::record('user.updated', 'user', (string) $user->id, [], ['name' => $user->name]);

        return response()->json(['data' => $user->load('roles')]);
    }

    public function destroyStaff(Request $request, int $id)
    {
        $user = User::findOrFail($id);
        if ($user->id === $request->user()->id) {
            return response()->json(['error' => ['code' => 'FORBIDDEN', 'message' => 'Khong the xoa chinh minh']], 403);
        }
        if ($user->roles()->where('slug', 'super_admin')->exists()
            && User::whereHas('roles', fn ($q) => $q->where('slug', 'super_admin'))->count() <= 1) {
            return response()->json(['error' => ['code' => 'FORBIDDEN', 'message' => 'Can it nhat 1 Super Admin']], 403);
        }
        $user->roles()->detach();
        $user->delete();
        ActivityLog::record('user.deleted', 'user', (string) $id, ['email' => $user->email], []);

        return response()->json(['data' => ['ok' => true]]);
    }
}
