<?php

namespace Tests\Unit;

use App\Models\Device;
use App\Models\Location;
use App\Models\Sensor;
use App\Models\SensorInstallation;
use App\Models\SensorReading;
use App\Models\SensorType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class QualityFlagTest extends TestCase
{
    use RefreshDatabase;

    protected Device $device;

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
    }

    protected function headers(array $extra = []): array
    {
        return array_merge([
            'X-Api-Key' => 'test-api-key-12345',
            'Content-Type' => 'application/json',
        ], $extra);
    }

    protected function createSensor(string $typeName, string $unit, float $min, float $max): Sensor
    {
        $type = SensorType::create([
            'id' => Str::uuid()->toString(),
            'name' => $typeName,
            'unit' => $unit,
            'min_value' => $min,
            'max_value' => $max,
            'precision' => 1,
        ]);

        $sensor = Sensor::create([
            'id' => Str::uuid()->toString(),
            'sensor_type_id' => $type->id,
            'model' => 'TestSensor',
        ]);

        SensorInstallation::create([
            'id' => Str::uuid()->toString(),
            'device_id' => $this->device->id,
            'sensor_id' => $sensor->id,
            'installed_at' => now()->subDay(),
        ]);

        return $sensor;
    }

    public function test_humidity_out_of_range(): void
    {
        $this->createSensor('humidity', '%', 0, 100);

        $payload = [
            'fw' => '1.4.2',
            'ts' => now('UTC')->subMinute()->timestamp,
            'seq' => 1,
            'battery_v' => 3.92,
            'rssi' => -71,
            'readings' => [['s' => 'humidity', 'v' => 150]],
        ];

        $this->postJson('/api/v1/ingest/telemetry', $payload, $this->headers())->assertStatus(200);

        $reading = SensorReading::first();
        $this->assertEquals('OUT_OF_RANGE', $reading->quality_flag);
    }

    public function test_temp_air_sentinel_error(): void
    {
        $this->createSensor('temp_air', '°C', -50, 60);

        $payload = [
            'fw' => '1.4.2',
            'ts' => now('UTC')->subMinute()->timestamp,
            'seq' => 1,
            'battery_v' => 3.92,
            'rssi' => -71,
            'readings' => [['s' => 'temp_air', 'v' => -999]],
        ];

        $this->postJson('/api/v1/ingest/telemetry', $payload, $this->headers())->assertStatus(200);

        $reading = SensorReading::first();
        $this->assertEquals('SENSOR_ERROR', $reading->quality_flag);
    }

    public function test_boundary_min_ok(): void
    {
        $this->createSensor('humidity', '%', 0, 100);

        $payload = [
            'fw' => '1.4.2',
            'ts' => now('UTC')->subMinute()->timestamp,
            'seq' => 1,
            'battery_v' => 3.92,
            'rssi' => -71,
            'readings' => [['s' => 'humidity', 'v' => 0]],
        ];

        $this->postJson('/api/v1/ingest/telemetry', $payload, $this->headers())->assertStatus(200);

        $reading = SensorReading::first();
        $this->assertEquals('OK', $reading->quality_flag);
    }

    public function test_boundary_max_ok(): void
    {
        $this->createSensor('temp_air', '°C', -50, 60);

        $payload = [
            'fw' => '1.4.2',
            'ts' => now('UTC')->subMinute()->timestamp,
            'seq' => 1,
            'battery_v' => 3.92,
            'rssi' => -71,
            'readings' => [['s' => 'temp_air', 'v' => 60]],
        ];

        $this->postJson('/api/v1/ingest/telemetry', $payload, $this->headers())->assertStatus(200);

        $reading = SensorReading::first();
        $this->assertEquals('OK', $reading->quality_flag);
    }

    public function test_clock_skew_within_tolerance(): void
    {
        $this->createSensor('temp_air', '°C', -50, 60);

        // ts = now + 200s (within 300s tolerance)
        $payload = [
            'fw' => '1.4.2',
            'ts' => now('UTC')->addSeconds(200)->timestamp,
            'seq' => 1,
            'battery_v' => 3.92,
            'rssi' => -71,
            'readings' => [['s' => 'temp_air', 'v' => 27.4]],
        ];

        $this->postJson('/api/v1/ingest/telemetry', $payload, $this->headers())->assertStatus(200);

        $reading = SensorReading::first();
        $this->assertEquals('CLOCK_SKEW', $reading->quality_flag);
    }
}
