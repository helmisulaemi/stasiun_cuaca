<?php

namespace Tests\Feature;

use App\Models\Sensor;
use App\Models\SensorType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SensorCRUDTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected SensorType $sensorType;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'id' => Str::uuid()->toString(),
            'name' => 'Admin',
            'email' => 'admin@test.com',
            'password_hash' => bcrypt('password'),
        ]);

        $this->sensorType = SensorType::create([
            'id' => Str::uuid()->toString(),
            'name' => 'temp_air',
            'unit' => '°C',
            'min_value' => -50,
            'max_value' => 60,
            'precision' => 1,
        ]);

        Sanctum::actingAs($this->user);
    }

    public function test_list_sensors_with_pagination(): void
    {
        Sensor::create([
            'id' => Str::uuid()->toString(),
            'sensor_type_id' => $this->sensorType->id,
            'serial_number' => 'TEMP-001',
            'model' => 'DHT22',
        ]);

        Sensor::create([
            'id' => Str::uuid()->toString(),
            'sensor_type_id' => $this->sensorType->id,
            'serial_number' => 'TEMP-002',
            'model' => 'DHT22',
        ]);

        $response = $this->getJson('/api/v1/sensors?per_page=15');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.total', 2);
    }

    public function test_show_sensor(): void
    {
        $sensor = Sensor::create([
            'id' => Str::uuid()->toString(),
            'sensor_type_id' => $this->sensorType->id,
            'serial_number' => 'TEMP-001',
            'model' => 'DHT22',
        ]);

        $response = $this->getJson("/api/v1/sensors/{$sensor->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.serial_number', 'TEMP-001')
            ->assertJsonPath('data.sensor_type.name', 'temp_air');
    }

    public function test_create_sensor(): void
    {
        $response = $this->postJson('/api/v1/sensors', [
            'sensor_type_id' => $this->sensorType->id,
            'serial_number' => 'TEMP-NEW',
            'model' => 'DHT22',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.serial_number', 'TEMP-NEW');

        $this->assertDatabaseHas('sensors', ['serial_number' => 'TEMP-NEW']);
    }

    public function test_update_sensor(): void
    {
        $sensor = Sensor::create([
            'id' => Str::uuid()->toString(),
            'sensor_type_id' => $this->sensorType->id,
            'serial_number' => 'TEMP-OLD',
            'model' => 'DHT22',
        ]);

        $response = $this->patchJson("/api/v1/sensors/{$sensor->id}", [
            'model' => 'BME280',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.model', 'BME280');
    }

    public function test_delete_sensor(): void
    {
        $sensor = Sensor::create([
            'id' => Str::uuid()->toString(),
            'sensor_type_id' => $this->sensorType->id,
            'serial_number' => 'TEMP-DEL',
            'model' => 'DHT22',
        ]);

        $response = $this->deleteJson("/api/v1/sensors/{$sensor->id}");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('sensors', ['id' => $sensor->id]);
    }

    public function test_show_nonexistent_sensor_returns_404(): void
    {
        $fakeId = Str::uuid()->toString();
        $response = $this->getJson("/api/v1/sensors/{$fakeId}");

        $response->assertStatus(404)
            ->assertJsonPath('error.code', 'NOT_FOUND');
    }
}
