<?php

namespace Tests\Feature;

use App\Models\Device;
use App\Models\Location;
use App\Models\Sensor;
use App\Models\SensorInstallation;
use App\Models\SensorType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SensorInstallationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Device $device;
    protected Sensor $sensor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'id' => Str::uuid()->toString(),
            'name' => 'Admin',
            'email' => 'admin@test.com',
            'password_hash' => bcrypt('password'),
        ]);

        $location = Location::create([
            'id' => Str::uuid()->toString(),
            'name' => 'Gardu Pantai',
            'latitude' => -6.1234,
            'longitude' => 106.1234,
            'altitude' => 10,
        ]);

        $this->device = Device::create([
            'id' => Str::uuid()->toString(),
            'name' => 'WS-GRT-001',
            'location_id' => $location->id,
            'status' => 'active',
            'secret_hash' => hash('sha256', 'secret1'),
        ]);

        $sensorType = SensorType::create([
            'id' => Str::uuid()->toString(),
            'name' => 'temp_air',
            'unit' => '°C',
            'min_value' => -50,
            'max_value' => 60,
            'precision' => 1,
        ]);

        $this->sensor = Sensor::create([
            'id' => Str::uuid()->toString(),
            'sensor_type_id' => $sensorType->id,
            'serial_number' => 'TEMP-001',
            'model' => 'DHT22',
        ]);

        Sanctum::actingAs($this->user);
    }

    public function test_install_sensor_to_device(): void
    {
        $response = $this->postJson("/api/v1/devices/{$this->device->id}/sensors", [
            'sensor_id' => $this->sensor->id,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => ['id', 'device_id', 'sensor_id', 'installed_at'],
            ]);

        $this->assertDatabaseHas('sensor_installations', [
            'device_id' => $this->device->id,
            'sensor_id' => $this->sensor->id,
        ]);
    }

    public function test_install_same_sensor_twice_returns_409(): void
    {
        $this->postJson("/api/v1/devices/{$this->device->id}/sensors", [
            'sensor_id' => $this->sensor->id,
        ]);

        $response = $this->postJson("/api/v1/devices/{$this->device->id}/sensors", [
            'sensor_id' => $this->sensor->id,
        ]);

        $response->assertStatus(409)
            ->assertJsonPath('error.code', 'CONFLICT');
    }

    public function test_install_sensor_active_on_other_device_returns_409(): void
    {
        $otherDevice = Device::create([
            'id' => Str::uuid()->toString(),
            'name' => 'WS-GRT-002',
            'location_id' => $this->device->location_id,
            'status' => 'active',
            'secret_hash' => hash('sha256', 'secret2'),
        ]);

        $this->postJson("/api/v1/devices/{$otherDevice->id}/sensors", [
            'sensor_id' => $this->sensor->id,
        ]);

        $response = $this->postJson("/api/v1/devices/{$this->device->id}/sensors", [
            'sensor_id' => $this->sensor->id,
        ]);

        $response->assertStatus(409)
            ->assertJsonPath('error.code', 'CONFLICT');
    }

    public function test_remove_sensor_from_device(): void
    {
        $this->postJson("/api/v1/devices/{$this->device->id}/sensors", [
            'sensor_id' => $this->sensor->id,
        ]);

        $response = $this->deleteJson("/api/v1/devices/{$this->device->id}/sensors/{$this->sensor->id}");

        $response->assertStatus(204);

        $this->assertDatabaseHas('sensor_installations', [
            'device_id' => $this->device->id,
            'sensor_id' => $this->sensor->id,
        ]);

        $installation = SensorInstallation::where('device_id', $this->device->id)
            ->where('sensor_id', $this->sensor->id)
            ->first();

        $this->assertNotNull($installation->removed_at);
    }

    public function test_remove_nonexistent_installation_returns_404(): void
    {
        $response = $this->deleteJson("/api/v1/devices/{$this->device->id}/sensors/{$this->sensor->id}");

        $response->assertStatus(404)
            ->assertJsonPath('error.code', 'NOT_FOUND');
    }
}
