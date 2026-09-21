<?php

namespace App\Repositories;

use App\Interfaces\DeviceRepositoryInterface;
use App\Models\Device;
use App\Models\DeviceStatusHistory;
use App\Models\SensorInstallation;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;

class DeviceRepository implements DeviceRepositoryInterface
{
    public function getAll()
    {
        return Device::with('location')->get();
    }

    public function getById($id)
    {
        return Device::with('location')->findOrFail($id);
    }

    public function store(array $data)
    {
        return Device::create($data);
    }

    public function update(array $data, $id)
    {
        $device = Device::findOrFail($id);
        $device->update($data);

        return $device;
    }

    public function delete($id)
    {
        $device = Device::findOrFail($id);
        $device->delete();

        return $device;
    }

    public function getPaginated(array $filters): LengthAwarePaginator
    {
        $query = Device::with('location')->whereNull('deleted_at');

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['location_id'])) {
            $query->where('location_id', $filters['location_id']);
        }

        if (isset($filters['q'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', "%{$filters['q']}%")
                  ->orWhere('fw_version', 'like', "%{$filters['q']}%");
            });
        }

        return $query->orderBy('created_at', 'desc')
            ->paginate($filters['per_page'] ?? 15);
    }

    public function getHealthData(string $id): ?array
    {
        $device = Device::withTrashed()->find($id);
        if (! $device) {
            return null;
        }

        $offlineThreshold = config('device.offline_threshold_minutes', 15);
        $lastSeen = $device->last_seen_at;
        $isOffline = ! $lastSeen || $lastSeen->diffInMinutes(now()) > $offlineThreshold;

        return [
            'device_id' => $device->id,
            'name' => $device->name,
            'status' => $device->status,
            'is_offline' => $isOffline,
            'last_seen_at' => $lastSeen?->toISOString(),
            'minutes_since_last_seen' => $lastSeen?->diffInMinutes(now()),
            'last_battery_v' => $device->last_battery_v,
            'last_rssi' => $device->last_rssi,
            'fw_version' => $device->fw_version,
        ];
    }

    public function generateSecret(): array
    {
        $plain = bin2hex(random_bytes(32));
        $hash = hash('sha256', $plain);

        return ['plain' => $plain, 'hash' => $hash];
    }

    public function getInstalledSensors(string $deviceId): Collection
    {
        return SensorInstallation::where('device_id', $deviceId)
            ->whereNull('removed_at')
            ->join('sensors', 'sensors.id', '=', 'sensor_installations.sensor_id')
            ->join('sensor_types', 'sensor_types.id', '=', 'sensors.sensor_type_id')
            ->select(
                'sensor_installations.id as installation_id',
                'sensor_installations.installed_at',
                'sensors.id as sensor_id',
                'sensors.serial_number',
                'sensors.model',
                'sensor_types.name as sensor_type_name',
                'sensor_types.unit',
            )
            ->orderBy('sensor_types.name')
            ->get();
    }

    public function recordStatusTransition(string $deviceId, ?string $from, string $to, ?string $reason, ?string $userId): void
    {
        DeviceStatusHistory::create([
            'id' => Str::uuid()->toString(),
            'device_id' => $deviceId,
            'from_status' => $from,
            'status' => $to,
            'changed_at' => now(),
            'changed_by_user_id' => $userId,
            'reason' => $reason,
        ]);
    }
}
