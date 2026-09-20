<?php

namespace Tests\Feature;

use App\Models\Device;
use App\Models\Location;
use App\Models\Sensor;
use App\Models\SensorCalibration;
use App\Models\SensorInstallation;
use App\Models\SensorReading;
use App\Models\SensorType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class IngestTelemetryTest extends TestCase
{
    use RefreshDatabase;

    protected Device $device;
    protected SensorType $tempAirType;
    protected Sensor $tempAirSensor;

    protected function setUp(): void
    {
        parent::setUp();

        $locationId = Str::uuid()->toString();
        Location::create([
            'id' => $locationId,
            'name' => 'Gardu Pantai',
            'latitude' => -6.1234,
            'longitude' => 106.1234,
            'altitude' => 10,
        ]);

        $this->device = Device::create([
            'id' => Str::uuid()->toString(),
            'name' => 'WS-GRT-001',
            'location_id' => $locationId,
            'status' => 'active',
            'secret_hash' => hash('sha256', 'test-api-key-12345'),
        ]);

        $this->tempAirType = SensorType::create([
            'id' => Str::uuid()->toString(),
            'name' => 'temp_air',
            'unit' => '°C',
            'min_value' => -50,
            'max_value' => 60,
            'precision' => 1,
        ]);

        SensorType::create([
            'id' => Str::uuid()->toString(),
            'name' => 'humidity',
            'unit' => '%',
            'min_value' => 0,
            'max_value' => 100,
            'precision' => 1,
        ]);

        $this->tempAirSensor = Sensor::create([
            'id' => Str::uuid()->toString(),
            'sensor_type_id' => $this->tempAirType->id,
            'model' => 'DHT22',
        ]);

        SensorInstallation::create([
            'id' => Str::uuid()->toString(),
            'device_id' => $this->device->id,
            'sensor_id' => $this->tempAirSensor->id,
            'installed_at' => now()->subDay(),
        ]);
    }

    protected function headers(array $extra = []): array
    {
        return array_merge([
            'X-Api-Key' => 'test-api-key-12345',
            'Content-Type' => 'application/json',
        ], $extra);
    }

    protected function validPayload(array $overrides = []): array
    {
        return array_merge([
            'fw' => '1.4.2',
            'ts' => now('UTC')->subMinute()->timestamp,
            'seq' => 1,
            'battery_v' => 3.92,
            'rssi' => -71,
            'readings' => [
                ['s' => 'temp_air', 'v' => 27.4],
            ],
        ], $overrides);
    }

    public function test_happy_path_all_readings_accepted(): void
    {
        $response = $this->postJson('/api/v1/ingest/telemetry', $this->validPayload(), $this->headers());

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.accepted', 1)
            ->assertJsonPath('data.duplicates', 0)
            ->assertJsonPath('data.received', 1);

        $this->assertDatabaseCount('sensor_readings', 1);
    }

    public function test_duplicate_payload_returns_duplicate_count(): void
    {
        $payload = $this->validPayload();

        $this->postJson('/api/v1/ingest/telemetry', $payload, $this->headers())->assertStatus(200);
        $response = $this->postJson('/api/v1/ingest/telemetry', $payload, $this->headers());

        $response->assertStatus(200)
            ->assertJsonPath('data.accepted', 0)
            ->assertJsonPath('data.duplicates', 1);

        $this->assertDatabaseCount('sensor_readings', 1);
    }

    public function test_humidity_out_of_range_gets_quality_flag(): void
    {
        $humidityType = SensorType::where('name', 'humidity')->first();
        $humiditySensor = Sensor::create([
            'id' => Str::uuid()->toString(),
            'sensor_type_id' => $humidityType->id,
            'model' => 'DHT22',
        ]);
        SensorInstallation::create([
            'id' => Str::uuid()->toString(),
            'device_id' => $this->device->id,
            'sensor_id' => $humiditySensor->id,
            'installed_at' => now()->subDay(),
        ]);

        $payload = $this->validPayload([
            'readings' => [['s' => 'humidity', 'v' => 150]],
        ]);

        $response = $this->postJson('/api/v1/ingest/telemetry', $payload, $this->headers());

        $response->assertStatus(200)
            ->assertJsonPath('data.accepted', 1);

        $reading = SensorReading::first();
        $this->assertEquals('OUT_OF_RANGE', $reading->quality_flag);
    }

    public function test_temp_air_sentinel_gets_sensor_error_flag(): void
    {
        $payload = $this->validPayload([
            'readings' => [['s' => 'temp_air', 'v' => -999]],
        ]);

        $response = $this->postJson('/api/v1/ingest/telemetry', $payload, $this->headers());

        $response->assertStatus(200)
            ->assertJsonPath('data.accepted', 1);

        $reading = SensorReading::first();
        $this->assertEquals('SENSOR_ERROR', $reading->quality_flag);
    }

    public function test_future_timestamp_rejects_payload(): void
    {
        $payload = $this->validPayload([
            'ts' => now('UTC')->addHours(1)->timestamp,
        ]);

        $response = $this->postJson('/api/v1/ingest/telemetry', $payload, $this->headers());

        $response->assertStatus(422)
            ->assertJsonPath('error.code', 'TS_IN_FUTURE');

        $this->assertDatabaseCount('sensor_readings', 0);
    }

    public function test_unknown_sensor_type_is_skipped(): void
    {
        $payload = $this->validPayload([
            'readings' => [['s' => 'unknown_sensor', 'v' => 100]],
        ]);

        $response = $this->postJson('/api/v1/ingest/telemetry', $payload, $this->headers());

        $response->assertStatus(200)
            ->assertJsonPath('data.accepted', 0)
            ->assertJsonCount(1, 'data.items')
            ->assertJsonPath('data.items.0.status', 'skipped');

        $this->assertDatabaseCount('sensor_readings', 0);
    }

    public function test_no_api_key_returns_401(): void
    {
        $response = $this->postJson('/api/v1/ingest/telemetry', $this->validPayload(), [
            'Content-Type' => 'application/json',
        ]);

        $response->assertStatus(401)
            ->assertJsonPath('error.code', 'UNAUTHENTICATED');
    }

    public function test_invalid_api_key_returns_401(): void
    {
        $response = $this->postJson('/api/v1/ingest/telemetry', $this->validPayload(), [
            'X-Api-Key' => 'wrong-key',
            'Content-Type' => 'application/json',
        ]);

        $response->assertStatus(401)
            ->assertJsonPath('error.code', 'UNAUTHENTICATED');
    }

    public function test_inactive_device_returns_403(): void
    {
        $this->device->update(['status' => 'maintenance']);

        $response = $this->postJson('/api/v1/ingest/telemetry', $this->validPayload(), $this->headers());

        $response->assertStatus(403)
            ->assertJsonPath('error.code', 'FORBIDDEN');
    }

    public function test_validation_error_returns_422(): void
    {
        $payload = ['fw' => '1.4.2'];

        $response = $this->postJson('/api/v1/ingest/telemetry', $payload, $this->headers());

        $response->assertStatus(422)
            ->assertJsonPath('error.code', 'VALIDATION_ERROR');
    }

    public function test_calibration_is_applied(): void
    {
        SensorCalibration::create([
            'id' => Str::uuid()->toString(),
            'sensor_id' => $this->tempAirSensor->id,
            'offset' => 2.0,
            'scale' => 1.0,
            'effective_from' => now()->subMonth(),
        ]);

        $payload = $this->validPayload([
            'readings' => [['s' => 'temp_air', 'v' => 27.0]],
        ]);

        $this->postJson('/api/v1/ingest/telemetry', $payload, $this->headers())->assertStatus(200);

        $reading = SensorReading::first();
        $this->assertEquals(29.0, $reading->calibrated_value);
    }
}
