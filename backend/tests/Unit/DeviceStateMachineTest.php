<?php

namespace Tests\Unit;

use App\Models\Device;
use App\Models\DeviceStatusHistory;
use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DeviceStateMachineTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'id' => Str::uuid()->toString(),
            'name' => 'Admin',
            'email' => 'admin@test.com',
            'password_hash' => bcrypt('password'),
        ]);

        Sanctum::actingAs($this->user);
    }

    protected function headers(array $extra = []): array
    {
        return array_merge([
            'Content-Type' => 'application/json',
        ], $extra);
    }

    public function test_provisioned_to_active(): void
    {
        $location = Location::create([
            'id' => Str::uuid()->toString(),
            'name' => 'Gardu Pantai',
            'latitude' => -6.1234,
            'longitude' => 106.1234,
            'altitude' => 10,
        ]);

        $device = Device::create([
            'id' => Str::uuid()->toString(),
            'name' => 'WS-GRT-001',
            'location_id' => $location->id,
            'status' => 'provisioned',
            'secret_hash' => hash('sha256', 'test-secret'),
        ]);

        $response = $this->postJson("/api/v1/devices/{$device->id}/status", [
            'status' => 'active',
        ], $this->headers());

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'active');

        $this->assertDatabaseHas('device_status_history', [
            'device_id' => $device->id,
            'from_status' => 'provisioned',
            'status' => 'active',
        ]);
    }

    public function test_active_to_maintenance(): void
    {
        $location = Location::create([
            'id' => Str::uuid()->toString(),
            'name' => 'Gardu Pantai',
            'latitude' => -6.1234,
            'longitude' => 106.1234,
            'altitude' => 10,
        ]);

        $device = Device::create([
            'id' => Str::uuid()->toString(),
            'name' => 'WS-GRT-001',
            'location_id' => $location->id,
            'status' => 'active',
            'secret_hash' => hash('sha256', 'test-secret'),
        ]);

        $response = $this->postJson("/api/v1/devices/{$device->id}/status", [
            'status' => 'maintenance',
            'reason' => 'Scheduled maintenance',
        ], $this->headers());

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'maintenance');

        $this->assertDatabaseHas('device_status_history', [
            'device_id' => $device->id,
            'from_status' => 'active',
            'status' => 'maintenance',
            'reason' => 'Scheduled maintenance',
        ]);
    }

    public function test_maintenance_to_active(): void
    {
        $location = Location::create([
            'id' => Str::uuid()->toString(),
            'name' => 'Gardu Pantai',
            'latitude' => -6.1234,
            'longitude' => 106.1234,
            'altitude' => 10,
        ]);

        $device = Device::create([
            'id' => Str::uuid()->toString(),
            'name' => 'WS-GRT-001',
            'location_id' => $location->id,
            'status' => 'maintenance',
            'secret_hash' => hash('sha256', 'test-secret'),
        ]);

        $response = $this->postJson("/api/v1/devices/{$device->id}/status", [
            'status' => 'active',
        ], $this->headers());

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'active');
    }

    public function test_maintenance_to_decommissioned(): void
    {
        $location = Location::create([
            'id' => Str::uuid()->toString(),
            'name' => 'Gardu Pantai',
            'latitude' => -6.1234,
            'longitude' => 106.1234,
            'altitude' => 10,
        ]);

        $device = Device::create([
            'id' => Str::uuid()->toString(),
            'name' => 'WS-GRT-001',
            'location_id' => $location->id,
            'status' => 'maintenance',
            'secret_hash' => hash('sha256', 'test-secret'),
        ]);

        $response = $this->postJson("/api/v1/devices/{$device->id}/status", [
            'status' => 'decommissioned',
        ], $this->headers());

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'decommissioned');
    }

    public function test_provisioned_to_maintenance_is_invalid(): void
    {
        $location = Location::create([
            'id' => Str::uuid()->toString(),
            'name' => 'Gardu Pantai',
            'latitude' => -6.1234,
            'longitude' => 106.1234,
            'altitude' => 10,
        ]);

        $device = Device::create([
            'id' => Str::uuid()->toString(),
            'name' => 'WS-GRT-001',
            'location_id' => $location->id,
            'status' => 'provisioned',
            'secret_hash' => hash('sha256', 'test-secret'),
        ]);

        $response = $this->postJson("/api/v1/devices/{$device->id}/status", [
            'status' => 'maintenance',
        ], $this->headers());

        $response->assertStatus(409)
            ->assertJsonPath('error.code', 'INVALID_TRANSITION');
    }

    public function test_decommissioned_is_terminal(): void
    {
        $location = Location::create([
            'id' => Str::uuid()->toString(),
            'name' => 'Gardu Pantai',
            'latitude' => -6.1234,
            'longitude' => 106.1234,
            'altitude' => 10,
        ]);

        $device = Device::create([
            'id' => Str::uuid()->toString(),
            'name' => 'WS-GRT-001',
            'location_id' => $location->id,
            'status' => 'decommissioned',
            'secret_hash' => hash('sha256', 'test-secret'),
        ]);

        $response = $this->postJson("/api/v1/devices/{$device->id}/status", [
            'status' => 'active',
        ], $this->headers());

        $response->assertStatus(409)
            ->assertJsonPath('error.code', 'INVALID_TRANSITION');
    }

    public function test_status_change_records_history(): void
    {
        $location = Location::create([
            'id' => Str::uuid()->toString(),
            'name' => 'Gardu Pantai',
            'latitude' => -6.1234,
            'longitude' => 106.1234,
            'altitude' => 10,
        ]);

        $device = Device::create([
            'id' => Str::uuid()->toString(),
            'name' => 'WS-GRT-001',
            'location_id' => $location->id,
            'status' => 'provisioned',
            'secret_hash' => hash('sha256', 'test-secret'),
        ]);

        $this->postJson("/api/v1/devices/{$device->id}/status", [
            'status' => 'active',
            'reason' => 'Ready for deployment',
        ], $this->headers());

        $this->assertDatabaseCount('device_status_history', 1);

        $history = DeviceStatusHistory::first();
        $this->assertEquals($device->id, $history->device_id);
        $this->assertEquals('provisioned', $history->from_status);
        $this->assertEquals('active', $history->status);
        $this->assertEquals('Ready for deployment', $history->reason);
        $this->assertEquals($this->user->id, $history->changed_by_user_id);
    }
}
