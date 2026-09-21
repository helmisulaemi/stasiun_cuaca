<?php

namespace App\Interfaces;

use App\Models\Sensor;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface SensorRepositoryInterface
{
    public function getPaginated(array $filters): LengthAwarePaginator;
    public function getAvailable(): Collection;
    public function getById(string $id): ?Sensor;
    public function store(array $data): Sensor;
    public function update(array $data, string $id): Sensor;
    public function delete(string $id): void;
    public function countBySensorType(string $sensorTypeId): int;
}
