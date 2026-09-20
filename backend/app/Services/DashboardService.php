<?php

namespace App\Services;

use App\Interfaces\DeviceRepositoryInterface;
use App\Interfaces\ReadingRepositoryInterface;

class DashboardService
{
    public function __construct(
        protected DeviceRepositoryInterface $deviceRepository,
        protected ReadingRepositoryInterface $readingRepository,
    ) {}

    public function getOverview(): array
    {
        $offlineThreshold = config('device.offline_threshold_minutes', 15);

        $devices = $this->deviceRepository->getAll();

        $devicesData = $devices->map(function ($device) use ($offlineThreshold) {
            $isOffline = ! $device->last_seen_at
                || $device->last_seen_at->diffInMinutes(now()) > $offlineThreshold;

            $latest = $this->readingRepository->getLatestReadings($device->id);
            $latestMap = $latest->mapWithKeys(fn ($r) => [$r->sensor_type => round($r->value, 2)]);

            return [
                'id' => $device->id,
                'name' => $device->name,
                'status' => $device->status,
                'is_offline' => $isOffline,
                'last_seen_at' => $device->last_seen_at?->toISOString(),
                'last_battery_v' => $device->last_battery_v,
                'location' => [
                    'name' => $device->location->name ?? null,
                ],
                'latest' => $latestMap,
            ];
        });

        $total = $devices->count();
        $offlineCount = $devicesData->where('is_offline', true)->count();

        return [
            'devices' => $devicesData->values()->all(),
            'summary' => [
                'total_devices' => $total,
                'online' => $total - $offlineCount,
                'offline' => $offlineCount,
            ],
        ];
    }
}
