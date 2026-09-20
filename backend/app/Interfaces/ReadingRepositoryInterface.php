<?php

namespace App\Interfaces;

use Carbon\Carbon;
use Illuminate\Support\Collection;

interface ReadingRepositoryInterface
{
    public function queryRaw(string $deviceId, ?string $sensorType, Carbon $from, Carbon $to): Collection;

    public function queryAggregated(
        string $deviceId,
        ?string $sensorType,
        Carbon $from,
        Carbon $to,
        string $interval,
        string $agg,
    ): Collection;

    public function getLatestReadings(string $deviceId): Collection;

    public function getDailySummary(string $deviceId, string $date): ?array;
}
