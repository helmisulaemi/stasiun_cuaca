<?php

namespace Tests\Feature;

use App\Models\Device;
use App\Models\Location;
use App\Models\Sensor;
use App\Models\SensorReading;
use App\Models\SensorType;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ReadingQueryTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Device $device;
    protected SensorType $tempType;
    protected Sensor $tempSensor;

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

        $this->tempType = SensorType::create([
            'id' => Str::uuid()->toString(),
            'name' => 'temp_air',
            'unit' => '°C',
            'min_value' => -50,
            'max_value' => 60,
            'precision' => 1,
        ]);

        $this->tempSensor = Sensor::create([
            'id' => Str::uuid()->toString(),
            'sensor_type_id' => $this->tempType->id,
            'serial_number' => 'TEMP-001',
            'model' => 'DHT22',
        ]);

        Sanctum::actingAs($this->user);
    }

    private function createReading(string $ts, float $value): void
    {
        SensorReading::create([
            'id' => Str::uuid()->toString(),
            'sensor_id' => $this->tempSensor->id,
            'device_id' => $this->device->id,
            'device_ts' => $ts,
            'server_ts' => $ts,
            'seq' => 1,
            'raw_value' => $value,
            'calibrated_value' => $value,
            'quality_flag' => 'OK',
            'battery_v' => 3.9,
            'rssi' => -70,
            'firmware' => '1.4.2',
        ]);
    }

    public function test_readings_requires_device_id(): void
    {
        $response = $this->getJson('/api/v1/readings');

        $response->assertStatus(422)
            ->assertJsonPath('error.code', 'VALIDATION_ERROR');
    }

    public function test_readings_raw_interval(): void
    {
        $this->createReading(now('UTC')->subHours(2)->toDateTimeString(), 27.5);

        $response = $this->getJson('/api/v1/readings?device_id=' . $this->device->id . '&interval=raw');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.interval', 'raw')
            ->assertJsonStructure([
                'data' => [
                    'device_id',
                    'interval',
                    'agg',
                    'series' => [
                        ['sensor_type', 'unit', 'timestamps', 'values'],
                    ],
                ],
            ]);
    }

    public function test_readings_auto_select_interval(): void
    {
        $this->createReading(now('UTC')->subHours(2)->toDateTimeString(), 27.5);

        $response = $this->getJson('/api/v1/readings?device_id=' . $this->device->id);

        $response->assertStatus(200)
            ->assertJsonPath('data.interval', 'raw');
    }

    public function test_readings_interval_too_coarse_returns_422(): void
    {
        $from = now('UTC')->subDays(10)->timestamp;
        $to = now('UTC')->timestamp;

        $response = $this->getJson('/api/v1/readings?' . http_build_query([
            'device_id' => $this->device->id,
            'interval' => 'raw',
            'from' => $from,
            'to' => $to,
        ]));

        $response->assertStatus(422)
            ->assertJsonPath('error.code', 'INTERVAL_TOO_COARSE')
            ->assertJsonPath('error.details.suggested_interval', '1h');
    }

    public function test_readings_invalid_interval_returns_422(): void
    {
        $response = $this->getJson('/api/v1/readings?device_id=' . $this->device->id . '&interval=5m');

        $response->assertStatus(422)
            ->assertJsonPath('error.code', 'VALIDATION_ERROR');
    }
}
