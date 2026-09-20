<?php

namespace Tests\Feature;

use App\Models\Sensor;
use App\Models\SensorCalibration;
use App\Models\SensorType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CalibrationHistoryTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
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

    public function test_list_calibrations(): void
    {
        SensorCalibration::create([
            'id' => Str::uuid()->toString(),
            'sensor_id' => $this->sensor->id,
            'offset' => 0.5,
            'scale' => 1.0,
            'effective_from' => '2026-01-01',
        ]);

        $response = $this->getJson("/api/v1/sensors/{$this->sensor->id}/calibrations");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.total', 1);
    }

    public function test_create_calibration(): void
    {
        $response = $this->postJson("/api/v1/sensors/{$this->sensor->id}/calibrations", [
            'offset' => 0.5,
            'scale' => 1.02,
            'effective_from' => '2026-06-01T00:00:00Z',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.offset', '0.50000')
            ->assertJsonPath('data.scale', '1.02000');

        $this->assertDatabaseHas('sensor_calibrations', [
            'sensor_id' => $this->sensor->id,
        ]);
    }

    public function test_create_calibration_validation_error(): void
    {
        $response = $this->postJson("/api/v1/sensors/{$this->sensor->id}/calibrations", [
            'offset' => 'not-a-number',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error.code', 'VALIDATION_ERROR');
    }

    public function test_list_calibrations_ordered_by_effective_from_desc(): void
    {
        SensorCalibration::create([
            'id' => Str::uuid()->toString(),
            'sensor_id' => $this->sensor->id,
            'offset' => 0.5,
            'scale' => 1.0,
            'effective_from' => '2026-01-01',
        ]);

        SensorCalibration::create([
            'id' => Str::uuid()->toString(),
            'sensor_id' => $this->sensor->id,
            'offset' => 0.3,
            'scale' => 1.02,
            'effective_from' => '2026-06-01',
        ]);

        $response = $this->getJson("/api/v1/sensors/{$this->sensor->id}/calibrations");

        $response->assertStatus(200);

        $data = $response->json('data.data');
        $this->assertCount(2, $data);
        $this->assertEquals('2026-06-01', substr($data[0]['effective_from'], 0, 10));
    }
}
