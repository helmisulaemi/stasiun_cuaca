<?php

namespace App\Repositories;

use App\Interfaces\SensorRepositoryInterface;
use App\Models\Sensor;
use Illuminate\Pagination\LengthAwarePaginator;

class SensorRepository implements SensorRepositoryInterface
{
    public function getPaginated(array $filters): LengthAwarePaginator
    {
        $query = Sensor::with('sensorType');

        if (isset($filters['sensor_type_id'])) {
            $query->where('sensor_type_id', $filters['sensor_type_id']);
        }

        if (isset($filters['q'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('serial_number', 'like', "%{$filters['q']}%")
                  ->orWhere('model', 'like', "%{$filters['q']}%");
            });
        }

        return $query->orderBy('created_at', 'desc')
            ->paginate($filters['per_page'] ?? 15);
    }

    public function getById(string $id): ?Sensor
    {
        return Sensor::with('sensorType')->find($id);
    }

    public function store(array $data): Sensor
    {
        return Sensor::create($data);
    }

    public function update(array $data, string $id): Sensor
    {
        $sensor = Sensor::findOrFail($id);
        $sensor->update($data);
        return $sensor;
    }

    public function delete(string $id): void
    {
        $sensor = Sensor::findOrFail($id);
        $sensor->forceDelete();
    }

    public function countBySensorType(string $sensorTypeId): int
    {
        return Sensor::where('sensor_type_id', $sensorTypeId)->count();
    }
}
