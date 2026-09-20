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

class IdempotencyTest extends TestCase
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

    public function test_single_payload_3x_produces_1_row(): void
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
        $this->postJson('/api/v1/ingest/telemetry', $payload, $this->headers())->assertStatus(200);
        $this->postJson('/api/v1/ingest/telemetry', $payload, $this->headers())->assertStatus(200);

        $this->assertDatabaseCount('sensor_readings', 1);
    }

    public function test_single_payload_3x_reports_duplicates(): void
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

        $response = $this->postJson('/api/v1/ingest/telemetry', $payload, $this->headers());

        $response->assertStatus(200)
            ->assertJsonPath('data.accepted', 0)
            ->assertJsonPath('data.duplicates', 1);
    }

    public function test_batch_dedup(): void
    {
        $batchPayload = [
            'fw' => '1.4.2',
            'batch' => [
                [
                    'ts' => now('UTC')->subMinute()->timestamp,
                    'seq' => 1,
                    'battery_v' => 3.92,
                    'rssi' => -71,
                    'readings' => [['s' => 'temp_air', 'v' => 27.4]],
                ],
                [
                    'ts' => now('UTC')->subMinute()->timestamp,
                    'seq' => 1,
                    'battery_v' => 3.92,
                    'rssi' => -71,
                    'readings' => [['s' => 'temp_air', 'v' => 27.4]],
                ],
            ],
        ];

        $this->postJson('/api/v1/ingest/telemetry/batch', $batchPayload, $this->headers())->assertStatus(207);

        $this->assertDatabaseCount('sensor_readings', 1);
    }

    public function test_batch_dedup_reports_duplicates(): void
    {
        $batchPayload = [
            'fw' => '1.4.2',
            'batch' => [
                [
                    'ts' => now('UTC')->subMinute()->timestamp,
                    'seq' => 1,
                    'battery_v' => 3.92,
                    'rssi' => -71,
                    'readings' => [['s' => 'temp_air', 'v' => 27.4]],
                ],
                [
                    'ts' => now('UTC')->subMinute()->timestamp,
                    'seq' => 1,
                    'battery_v' => 3.92,
                    'rssi' => -71,
                    'readings' => [['s' => 'temp_air', 'v' => 27.4]],
                ],
            ],
        ];

        $response = $this->postJson('/api/v1/ingest/telemetry/batch', $batchPayload, $this->headers());

        $response->assertStatus(207)
            ->assertJsonPath('data.accepted', 1)
            ->assertJsonPath('data.duplicates', 1);
    }
}
