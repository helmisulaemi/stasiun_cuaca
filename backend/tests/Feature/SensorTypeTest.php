<?php

namespace Tests\Feature;

use App\Models\SensorType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SensorTypeTest extends TestCase
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

    public function test_list_sensor_types(): void
    {
        SensorType::create([
            'id' => Str::uuid()->toString(),
            'name' => 'temp_air',
            'unit' => '°C',
            'min_value' => -50,
            'max_value' => 60,
            'precision' => 1,
        ]);

        $response = $this->getJson('/api/v1/sensor-types');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [
                    'data' => [
                        ['id', 'name', 'unit'],
                    ],
                    'current_page',
                    'total',
                ],
            ]);
    }

    public function test_create_sensor_type(): void
    {
        $response = $this->postJson('/api/v1/sensor-types', [
            'name' => 'new_sensor',
            'unit' => 'mg/m³',
            'min_value' => 0,
            'max_value' => 500,
            'precision' => 2,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'new_sensor');

        $this->assertDatabaseHas('sensor_types', ['name' => 'new_sensor']);
    }

    public function test_create_sensor_type_validation_error(): void
    {
        $response = $this->postJson('/api/v1/sensor-types', [
            'name' => '',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error.code', 'VALIDATION_ERROR');
    }

    public function test_create_sensor_type_duplicate_name(): void
    {
        SensorType::create([
            'id' => Str::uuid()->toString(),
            'name' => 'temp_air',
            'unit' => '°C',
            'min_value' => -50,
            'max_value' => 60,
            'precision' => 1,
        ]);

        $response = $this->postJson('/api/v1/sensor-types', [
            'name' => 'temp_air',
            'unit' => '°C',
            'min_value' => -50,
            'max_value' => 60,
            'precision' => 1,
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }
}
