<?php

namespace App\Interfaces;

use Illuminate\Pagination\LengthAwarePaginator;

interface DeviceRepositoryInterface
{
    public function getAll();

    public function getById($id);

    public function store(array $data);

    public function update(array $data, $id);

    public function delete($id);

    public function getPaginated(array $filters): LengthAwarePaginator;

    public function getHealthData(string $id): ?array;

    public function generateSecret(): array;

    public function recordStatusTransition(string $deviceId, ?string $from, string $to, ?string $reason, ?string $userId): void;
}
