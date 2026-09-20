<?php

namespace Tests\Unit;

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

class CalibrationTest extends TestCase
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

    public function test_offset_only(): void
    {
        SensorCalibration::create([
            'id' => Str::uuid()->toString(),
            'sensor_id' => $this->tempAirSensor->id,
            'offset' => 2.0,
            'scale' => 1.0,
            'effective_from' => now()->subMonth(),
        ]);

        $payload = [
            'fw' => '1.4.2',
            'ts' => now('UTC')->subMinute()->timestamp,
            'seq' => 1,
            'battery_v' => 3.92,
            'rssi' => -71,
            'readings' => [['s' => 'temp_air', 'v' => 27.0]],
        ];

        $this->postJson('/api/v1/ingest/telemetry', $payload, $this->headers())->assertStatus(200);

        $reading = SensorReading::first();
        $this->assertEqualsWithDelta(29.0, $reading->calibrated_value, 0.01); // 27.0 × 1.0 + 2.0 = 29.0
    }

    public function test_scale_only(): void
    {
        SensorCalibration::create([
            'id' => Str::uuid()->toString(),
            'sensor_id' => $this->tempAirSensor->id,
            'offset' => 0,
            'scale' => 0.9,
            'effective_from' => now()->subMonth(),
        ]);

        $payload = [
            'fw' => '1.4.2',
            'ts' => now('UTC')->subMinute()->timestamp,
            'seq' => 1,
            'battery_v' => 3.92,
            'rssi' => -71,
            'readings' => [['s' => 'temp_air', 'v' => 100.0]],
        ];

        $this->postJson('/api/v1/ingest/telemetry', $payload, $this->headers())->assertStatus(200);

        $reading = SensorReading::first();
        $this->assertEqualsWithDelta(90.0, $reading->calibrated_value, 0.01); // 100.0 × 0.9 + 0 = 90.0
    }

    public function test_both_scale_and_offset(): void
    {
        SensorCalibration::create([
            'id' => Str::uuid()->toString(),
            'sensor_id' => $this->tempAirSensor->id,
            'offset' => 0.5,
            'scale' => 1.1,
            'effective_from' => now()->subMonth(),
        ]);

        $payload = [
            'fw' => '1.4.2',
            'ts' => now('UTC')->subMinute()->timestamp,
            'seq' => 1,
            'battery_v' => 3.92,
            'rssi' => -71,
            'readings' => [['s' => 'temp_air', 'v' => 10.0]],
        ];

        $this->postJson('/api/v1/ingest/telemetry', $payload, $this->headers())->assertStatus(200);

        $reading = SensorReading::first();
        $this->assertEqualsWithDelta(11.5, $reading->calibrated_value, 0.01); // 10.0 × 1.1 + 0.5 = 11.5
    }

    public function test_passthrough_without_calibration(): void
    {
        $payload = [
            'fw' => '1.4.2',
            'ts' => now('UTC')->subMinute()->timestamp,
            'seq' => 1,
            'battery_v' => 3.92,
            'rssi' => -71,
            'readings' => [['s' => 'temp_air', 'v' => 27.4]],
        ];

        $this->postJson('/api/v1/ingest/telemetry', $payload, $this->headers())->assertStatus(200);

        $reading = SensorReading::first();
        $this->assertEqualsWithDelta(27.4, $reading->calibrated_value, 0.01); // passthrough
    }

    public function test_historical_calibration(): void
    {
        // Create two calibrations: old one (effective_from 2 months ago) and new one (effective_from 1 month ago)
        SensorCalibration::create([
            'id' => Str::uuid()->toString(),
            'sensor_id' => $this->tempAirSensor->id,
            'offset' => 1.0,
            'scale' => 1.0,
            'effective_from' => now()->subMonths(2),
        ]);

        SensorCalibration::create([
            'id' => Str::uuid()->toString(),
            'sensor_id' => $this->tempAirSensor->id,
            'offset' => 2.0,
            'scale' => 1.0,
            'effective_from' => now()->subMonth(),
        ]);

        $payload = [
            'fw' => '1.4.2',
            'ts' => now('UTC')->subMinute()->timestamp,
            'seq' => 1,
            'battery_v' => 3.92,
            'rssi' => -71,
            'readings' => [['s' => 'temp_air', 'v' => 27.0]],
        ];

        $this->postJson('/api/v1/ingest/telemetry', $payload, $this->headers())->assertStatus(200);

        $reading = SensorReading::first();
        $this->assertEqualsWithDelta(29.0, $reading->calibrated_value, 0.01); // 27.0 × 1.0 + 2.0 = 29.0 (latest calibration)
    }
}
