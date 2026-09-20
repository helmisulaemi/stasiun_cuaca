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

class ReadingSummaryTest extends TestCase
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

    public function test_summary_requires_device_id(): void
    {
        $response = $this->getJson('/api/v1/readings/summary');

        $response->assertStatus(422)
            ->assertJsonPath('error.code', 'VALIDATION_ERROR');
    }

    public function test_summary_returns_data(): void
    {
        $today = Carbon::now('Asia/Jakarta')->format('Y-m-d');

        SensorReading::create([
            'id' => Str::uuid()->toString(),
            'sensor_id' => $this->tempSensor->id,
            'device_id' => $this->device->id,
            'device_ts' => now('UTC')->subHours(2),
            'server_ts' => now('UTC'),
            'seq' => 1,
            'raw_value' => 25.0,
            'calibrated_value' => 25.0,
            'quality_flag' => 'OK',
            'battery_v' => 3.9,
            'rssi' => -70,
            'firmware' => '1.4.2',
        ]);

        $response = $this->getJson("/api/v1/readings/summary?device_id={$this->device->id}&date={$today}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => ['device_id', 'date'],
            ]);
    }
}
