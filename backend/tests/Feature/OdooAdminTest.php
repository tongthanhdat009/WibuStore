<?php

namespace Tests\Feature;

use App\Models\OdooSyncLog;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OdooAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        config([
            'services.odoo.connector_url' => 'http://odoo.test',
            'services.odoo.webhook_secret' => 'test-secret',
            'odoo.url' => 'http://odoo-direct.test',
            'odoo.channel_key' => 'test-key',
            'odoo.channel_code' => 'wibu',
        ]);
        Http::fake([
            'odoo-direct.test/*' => Http::response(['success' => true, 'data' => ['items' => []]], 200),
            'odoo.test/*' => Http::response(['ok' => true], 200),
        ]);
    }

    private ?User $adminUser = null;

    private function superAdmin(): User
    {
        if (! $this->adminUser) {
            $this->adminUser = User::factory()->create(['email' => 'boss@wibu.vn']);
            $this->adminUser->roles()->attach(Role::where('slug', 'super_admin')->first());
        }

        return $this->adminUser;
    }

    public function test_guest_and_customer_cannot_access_odoo_admin(): void
    {
        $this->getJson('/api/v1/admin/odoo/status')->assertUnauthorized();
        $this->getJson('/api/v1/admin/odoo/logs')->assertUnauthorized();

        $customer = User::factory()->create();
        $customer->roles()->attach(Role::where('slug', 'customer')->first());
        $this->actingAs($customer)->getJson('/api/v1/admin/odoo/status')->assertForbidden();
        $this->actingAs($customer)->getJson('/api/v1/admin/odoo/logs')->assertForbidden();
    }

    public function test_status_masks_secrets_and_reports_counts(): void
    {
        OdooSyncLog::record([
            'direction' => 'push', 'event_id' => 'WB-1',
            'endpoint' => 'http://odoo.test/ingest/wibu', 'status' => 'failed',
            'error' => 'boom',
        ]);

        $res = $this->actingAs($this->superAdmin())->getJson('/api/v1/admin/odoo/status')->assertOk();
        $data = $res->json('data');
        $this->assertSame('http://odoo.test', $data['configured']['connector_url']);
        $this->assertTrue($data['configured']['webhook_secret_set']);
        $this->assertTrue($data['configured']['channel_key_set']);
        $this->assertArrayNotHasKey('webhook_secret', $data['configured']);
        $this->assertArrayNotHasKey('channel_key', $data['configured']);
        $this->assertSame(1, $data['last_24h']['failed']);
        $this->assertSame('failed', $data['last_push']['status']);
        $this->assertSame('boom', $data['last_error']['error']);
    }

    public function test_test_endpoint_pings_direct_catalog_and_logs(): void
    {
        $res = $this->actingAs($this->superAdmin())->postJson('/api/v1/admin/odoo/test')->assertOk();
        $res->assertJsonPath('data.ok', true);
        $this->assertSame(1, OdooSyncLog::where('direction', 'test')->where('status', 'success')->count());
        Http::assertSent(fn ($r) => str_contains($r->url(), 'odoo-direct.test/api/v1/channel/catalog')
            && $r->hasHeader('X-Channel-Key'));
    }

    public function test_retry_resends_stored_payload_and_records_retry_log(): void
    {
        $log = OdooSyncLog::record([
            'direction' => 'push', 'event_id' => 'WB-9',
            'endpoint' => 'http://odoo-direct.test/api/channel/order_push',
            'status' => 'failed', 'http_status' => 500,
            'payload' => ['channel_code' => 'wibu', 'external_id' => 'WB-9', 'items' => []],
            'error' => 'Odoo push rejected: 500',
        ]);

        $res = $this->actingAs($this->superAdmin())
            ->postJson('/api/v1/admin/odoo/retry', ['log_id' => $log->id])
            ->assertOk();
        $res->assertJsonPath('data.ok', true);
        $retry = OdooSyncLog::where('direction', 'retry')->first();
        $this->assertNotNull($retry);
        $this->assertSame('success', $retry->status);
        $this->assertSame(2, $retry->attempts);

        $this->actingAs($this->superAdmin())
            ->postJson('/api/v1/admin/odoo/retry', ['log_id' => $retry->id])
            ->assertStatus(409);
    }
}
