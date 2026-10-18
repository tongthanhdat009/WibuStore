<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RbacTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_admin_routes_require_auth_and_permission(): void
    {
        $this->getJson('/api/v1/admin/dashboard/overview')->assertUnauthorized();

        $customer = User::factory()->create();
        $customer->roles()->attach(Role::where('slug', 'customer')->first());
        $this->actingAs($customer)->getJson('/api/v1/admin/dashboard/overview')->assertForbidden();

        $admin = User::factory()->create(['email' => 'boss@wibu.vn']);
        $admin->roles()->attach(Role::where('slug', 'super_admin')->first());
        $this->actingAs($admin)->getJson('/api/v1/admin/dashboard/overview')->assertOk();
    }

    public function test_guest_lookup_without_proof_leaks_nothing(): void
    {
        // Lookup without matching email proof is 404, never leaks data.
        $this->postJson('/api/v1/orders/lookup', ['order_number' => 'WB-20000101-000000', 'email' => 'nobody@x.vn'])
            ->assertNotFound();
    }
}
