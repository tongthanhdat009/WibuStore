<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'products.read' => 'Xem catalog', 'products.write' => 'Sua catalog',
            'products.publish' => 'Publish/archive', 'inventory.adjust' => 'Dieu chinh kho',
            'orders.transition' => 'Xu ly don', 'payments.confirm' => 'Xac nhan thanh toan',
            'preorders.manage' => 'Quan ly pre-order', 'cms.publish' => 'Publish CMS',
            'users.manage' => 'Quan ly nhan su', 'reports.read' => 'Xem bao cao',
            'settings.manage' => 'Cau hinh cua hang',
        ];
        foreach ($permissions as $slug => $name) {
            Permission::firstOrCreate(['slug' => $slug], ['name' => $name]);
        }

        $matrix = [
            'super_admin' => array_keys($permissions),
            'store_manager' => ['products.read', 'products.write', 'products.publish', 'inventory.adjust', 'orders.transition', 'payments.confirm', 'preorders.manage', 'cms.publish', 'reports.read'],
            'catalog_editor' => ['products.read', 'products.write'],
            'order_staff' => ['orders.transition'],
            'inventory_staff' => ['inventory.adjust'],
            'content_editor' => ['cms.publish'],
            'customer' => [],
        ];
        foreach ($matrix as $slug => $perms) {
            $role = Role::firstOrCreate(['slug' => $slug], ['name' => ucfirst(str_replace('_', ' ', $slug))]);
            $role->permissions()->sync(Permission::whereIn('slug', $perms)->pluck('id'));
        }

        $admin = User::firstOrCreate(['email' => 'admin@wibustore.vn'], [
            'name' => 'Store Admin', 'password' => Hash::make('Admin@123'), 'email_verified_at' => now(),
        ]);
        $admin->roles()->syncWithoutDetaching(Role::whereIn('slug', ['super_admin'])->pluck('id'));
    }
}
