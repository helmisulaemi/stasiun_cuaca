<?php

namespace Tests\Feature;

use App\Models\Device;
use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DeviceManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Location $location;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'id' => Str::uuid()->toString(),
            'name' => 'Admin',
            'email' => 'admin@test.com',
            'password_hash' => bcrypt('password'),
        ]);

        $this->location = Location::create([
            'id' => Str::uuid()->toString(),
            'name' => 'Gardu Pantai',
            'latitude' => -6.1234,
            'longitude' => 106.1234,
            'altitude' => 10,
        ]);

        Sanctum::actingAs($this->user);
    }

    protected function headers(array $extra = []): array
    {
        return array_merge([
            'Content-Type' => 'application/json',
        ], $extra);
    }

    public function test_list_devices_with_pagination(): void
    {
        Device::create([
            'id' => Str::uuid()->toString(),
            'name' => 'WS-GRT-001',
            'location_id' => $this->location->id,
            'status' => 'active',
            'secret_hash' => hash('sha256', 'secret1'),
        ]);

        Device::create([
            'id' => Str::uuid()->toString(),
            'name' => 'WS-GRT-002',
            'location_id' => $this->location->id,
            'status' => 'active',
            'secret_hash' => hash('sha256', 'secret2'),
        ]);

        $response = $this->getJson('/api/v1/devices?per_page=15', $this->headers());

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [
                    'data' => [
                        ['id', 'name', 'status'],
                    ],
                    'current_page',
                    'last_page',
                    'per_page',
                    'total',
                ],
            ]);
    }

    public function test_filter_by_status(): void
    {
        Device::create([
            'id' => Str::uuid()->toString(),
            'name' => 'WS-GRT-001',
            'location_id' => $this->location->id,
            'status' => 'active',
            'secret_hash' => hash('sha256', 'secret1'),
        ]);

        Device::create([
            'id' => Str::uuid()->toString(),
            'name' => 'WS-GRT-002',
            'location_id' => $this->location->id,
            'status' => 'maintenance',
            'secret_hash' => hash('sha256', 'secret2'),
        ]);

        $response = $this->getJson('/api/v1/devices?status=active', $this->headers());

        $response->assertStatus(200)
            ->assertJsonPath('data.total', 1);
    }

    public function test_create_device(): void
    {
        $response = $this->postJson('/api/v1/devices', [
            'name' => 'WS-GRT-001',
            'location_id' => $this->location->id,
        ], $this->headers());

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [
                    'device' => ['id', 'name', 'status'],
                    'secret',
                ],
            ]);

        $this->assertDatabaseHas('devices', [
            'name' => 'WS-GRT-001',
            'status' => 'provisioned',
        ]);

        $this->assertDatabaseCount('device_status_history', 1);
    }

    public function test_create_device_returns_secret_once(): void
    {
        $response = $this->postJson('/api/v1/devices', [
            'name' => 'WS-GRT-001',
            'location_id' => $this->location->id,
        ], $this->headers());

        $response->assertStatus(201);

        $secret = $response->json('data.secret');
        $this->assertNotEmpty($secret);
        $this->assertIsString($secret);

        // Secret should not be in database (only hash)
        $this->assertDatabaseMissing('devices', [
            'secret_hash' => $secret,
        ]);
    }

    public function test_show_device(): void
    {
        $device = Device::create([
            'id' => Str::uuid()->toString(),
            'name' => 'WS-GRT-001',
            'location_id' => $this->location->id,
            'status' => 'active',
            'secret_hash' => hash('sha256', 'secret1'),
        ]);

        $response = $this->getJson("/api/v1/devices/{$device->id}", $this->headers());

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'WS-GRT-001');
    }

    public function test_update_device(): void
    {
        $device = Device::create([
            'id' => Str::uuid()->toString(),
            'name' => 'WS-GRT-001',
            'location_id' => $this->location->id,
            'status' => 'active',
            'secret_hash' => hash('sha256', 'secret1'),
        ]);

        $response = $this->patchJson("/api/v1/devices/{$device->id}", [
            'name' => 'WS-GRT-001-UPDATED',
        ], $this->headers());

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'WS-GRT-001-UPDATED');
    }

    public function test_soft_delete_device(): void
    {
        $device = Device::create([
            'id' => Str::uuid()->toString(),
            'name' => 'WS-GRT-001',
            'location_id' => $this->location->id,
            'status' => 'active',
            'secret_hash' => hash('sha256', 'secret1'),
        ]);

        $response = $this->deleteJson("/api/v1/devices/{$device->id}", [], $this->headers());

        $response->assertStatus(204);

        $this->assertSoftDeleted('devices', ['id' => $device->id]);
    }

    public function test_health_endpoint(): void
    {
        $device = Device::create([
            'id' => Str::uuid()->toString(),
            'name' => 'WS-GRT-001',
            'location_id' => $this->location->id,
            'status' => 'active',
            'secret_hash' => hash('sha256', 'secret1'),
            'last_seen_at' => now()->subMinutes(5),
            'last_battery_v' => 3.92,
            'last_rssi' => -71,
            'fw_version' => '1.4.2',
        ]);

        $response = $this->getJson("/api/v1/devices/{$device->id}/health", $this->headers());

        $response->assertStatus(200)
            ->assertJsonPath('data.is_offline', false)
            ->assertJsonPath('data.last_battery_v', 3.92)
            ->assertJsonPath('data.fw_version', '1.4.2');
    }

    public function test_health_offline_detection(): void
    {
        $device = Device::create([
            'id' => Str::uuid()->toString(),
            'name' => 'WS-GRT-001',
            'location_id' => $this->location->id,
            'status' => 'active',
            'secret_hash' => hash('sha256', 'secret1'),
            'last_seen_at' => now()->subMinutes(20),
        ]);

        $response = $this->getJson("/api/v1/devices/{$device->id}/health", $this->headers());

        $response->assertStatus(200)
            ->assertJsonPath('data.is_offline', true);
    }

    public function test_rotate_credentials(): void
    {
        $device = Device::create([
            'id' => Str::uuid()->toString(),
            'name' => 'WS-GRT-001',
            'location_id' => $this->location->id,
            'status' => 'active',
            'secret_hash' => hash('sha256', 'old-secret'),
        ]);

        $response = $this->postJson("/api/v1/devices/{$device->id}/credentials/rotate", [], $this->headers());

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'device_id',
                    'name',
                    'secret',
                ],
            ]);

        $newSecret = $response->json('data.secret');
        $this->assertNotEmpty($newSecret);
        $this->assertNotEquals('old-secret', $newSecret);
    }

    public function test_unauthenticated_returns_401(): void
    {
        // Reset auth
        $this->app['auth']->forgetGuards();

        $response = $this->getJson('/api/v1/devices', $this->headers());

        $response->assertStatus(401);
    }
}
