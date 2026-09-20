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

class RainfallConversionTest extends TestCase
{
    use RefreshDatabase;

    protected Device $device;
    protected SensorType $rainCounterType;
    protected Sensor $rainCounterSensor;

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

        $this->rainCounterType = SensorType::create([
            'id' => Str::uuid()->toString(),
            'name' => 'rain_counter',
            'unit' => 'tips',
            'min_value' => 0,
            'max_value' => 4294967295,
            'precision' => 0,
        ]);

        $this->rainCounterSensor = Sensor::create([
            'id' => Str::uuid()->toString(),
            'sensor_type_id' => $this->rainCounterType->id,
            'model' => 'TB-3',
        ]);

        SensorInstallation::create([
            'id' => Str::uuid()->toString(),
            'device_id' => $this->device->id,
            'sensor_id' => $this->rainCounterSensor->id,
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

    public function test_normal_delta_calculation(): void
    {
        $ts1 = now('UTC')->subMinutes(2)->timestamp;
        $ts2 = now('UTC')->subMinute()->timestamp;

        $payload1 = [
            'fw' => '1.4.2',
            'ts' => $ts1,
            'seq' => 1,
            'battery_v' => 3.92,
            'rssi' => -71,
            'readings' => [['s' => 'rain_counter', 'v' => 1000]],
        ];

        $payload2 = [
            'fw' => '1.4.2',
            'ts' => $ts2,
            'seq' => 2,
            'battery_v' => 3.92,
            'rssi' => -71,
            'readings' => [['s' => 'rain_counter', 'v' => 1005]],
        ];

        $this->postJson('/api/v1/ingest/telemetry', $payload1, $this->headers())->assertStatus(200);
        $this->postJson('/api/v1/ingest/telemetry', $payload2, $this->headers())->assertStatus(200);

        $readings = SensorReading::orderBy('device_ts')->get();

        $this->assertEquals(0, $readings[0]->rain_mm); // first reading, baseline
        $this->assertEqualsWithDelta(1.0, $readings[1]->rain_mm, 0.01); // (1005-1000) × 0.2 = 1.0
    }

    public function test_counter_reset_detection(): void
    {
        $ts1 = now('UTC')->subMinutes(2)->timestamp;
        $ts2 = now('UTC')->subMinute()->timestamp;

        $payload1 = [
            'fw' => '1.4.2',
            'ts' => $ts1,
            'seq' => 1,
            'battery_v' => 3.92,
            'rssi' => -71,
            'readings' => [['s' => 'rain_counter', 'v' => 1043]],
        ];

        $payload2 = [
            'fw' => '1.4.2',
            'ts' => $ts2,
            'seq' => 2,
            'battery_v' => 3.92,
            'rssi' => -71,
            'readings' => [['s' => 'rain_counter', 'v' => 5]],
        ];

        $this->postJson('/api/v1/ingest/telemetry', $payload1, $this->headers())->assertStatus(200);
        $this->postJson('/api/v1/ingest/telemetry', $payload2, $this->headers())->assertStatus(200);

        $readings = SensorReading::orderBy('device_ts')->get();

        $this->assertEquals(0, $readings[0]->rain_mm); // first reading, baseline
        $this->assertEqualsWithDelta(1.0, $readings[1]->rain_mm, 0.01); // EC-4: delta = 5 (cur), rain_mm = 5 × 0.2 = 1.0
    }

    public function test_first_reading_returns_zero(): void
    {
        $ts = now('UTC')->subMinute()->timestamp;

        $payload = [
            'fw' => '1.4.2',
            'ts' => $ts,
            'seq' => 1,
            'battery_v' => 3.92,
            'rssi' => -71,
            'readings' => [['s' => 'rain_counter', 'v' => 100]],
        ];

        $this->postJson('/api/v1/ingest/telemetry', $payload, $this->headers())->assertStatus(200);

        $reading = SensorReading::first();
        $this->assertEquals(0, $reading->rain_mm);
    }

    public function test_delta_zero_returns_zero_rain_mm(): void
    {
        $ts1 = now('UTC')->subMinutes(2)->timestamp;
        $ts2 = now('UTC')->subMinute()->timestamp;

        $payload1 = [
            'fw' => '1.4.2',
            'ts' => $ts1,
            'seq' => 1,
            'battery_v' => 3.92,
            'rssi' => -71,
            'readings' => [['s' => 'rain_counter', 'v' => 1000]],
        ];

        $payload2 = [
            'fw' => '1.4.2',
            'ts' => $ts2,
            'seq' => 2,
            'battery_v' => 3.92,
            'rssi' => -71,
            'readings' => [['s' => 'rain_counter', 'v' => 1000]],
        ];

        $this->postJson('/api/v1/ingest/telemetry', $payload1, $this->headers())->assertStatus(200);
        $this->postJson('/api/v1/ingest/telemetry', $payload2, $this->headers())->assertStatus(200);

        $readings = SensorReading::orderBy('device_ts')->get();

        $this->assertEquals(0, $readings[0]->rain_mm);
        $this->assertEquals(0, $readings[1]->rain_mm); // delta = 0
    }
}
