<?php

namespace Tests\Feature;

use App\Models\Device;
use App\Models\Location;
use App\Models\Sensor;
use App\Models\SensorReading;
use App\Models\SensorType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DashboardOverviewTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'id' => Str::uuid()->toString(),
            'name' => 'Admin',
            'email' => 'admin@test.com',
            'password_hash' => bcrypt('password'),
        ]);

        Sanctum::actingAs($this->user);
    }

    public function test_overview_returns_device_list(): void
    {
        $location = Location::create([
            'id' => Str::uuid()->toString(),
            'name' => 'Gardu Pantai',
            'latitude' => -6.1234,
            'longitude' => 106.1234,
            'altitude' => 10,
        ]);

        Device::create([
            'id' => Str::uuid()->toString(),
            'name' => 'WS-GRT-001',
            'location_id' => $location->id,
            'status' => 'active',
            'secret_hash' => hash('sha256', 'secret1'),
            'last_seen_at' => now()->subMinutes(5),
        ]);

        $response = $this->getJson('/api/v1/dashboard/overview');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [
                    'devices' => [
                        [
                            'id',
                            'name',
                            'status',
                            'is_offline',
                            'last_seen_at',
                            'location',
                            'latest',
                        ],
                    ],
                    'summary' => [
                        'total_devices',
                        'online',
                        'offline',
                    ],
                ],
            ]);
    }

    public function test_overview_counts_online_offline(): void
    {
        $location = Location::create([
            'id' => Str::uuid()->toString(),
            'name' => 'Gardu Pantai',
            'latitude' => -6.1234,
            'longitude' => 106.1234,
            'altitude' => 10,
        ]);

        Device::create([
            'id' => Str::uuid()->toString(),
            'name' => 'WS-GRT-001',
            'location_id' => $location->id,
            'status' => 'active',
            'secret_hash' => hash('sha256', 'secret1'),
            'last_seen_at' => now()->subMinutes(5),
        ]);

        Device::create([
            'id' => Str::uuid()->toString(),
            'name' => 'WS-GRT-002',
            'location_id' => $location->id,
            'status' => 'active',
            'secret_hash' => hash('sha256', 'secret2'),
            'last_seen_at' => now()->subMinutes(20),
        ]);

        $response = $this->getJson('/api/v1/dashboard/overview');

        $response->assertStatus(200)
            ->assertJsonPath('data.summary.total_devices', 2)
            ->assertJsonPath('data.summary.online', 1)
            ->assertJsonPath('data.summary.offline', 1);
    }
}
