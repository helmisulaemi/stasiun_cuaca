<?php

namespace App\Services;

use App\Exceptions\BusinessException;
use App\Interfaces\ReadingRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ReadingService
{
    private const INTERVAL_RANGES = [
        'raw' => 86400,      // 24 hours in seconds
        '1m' => 604800,      // 7 days
        '1h' => 7776000,     // 90 days
        '1d' => 31536000,    // 400 days
    ];

    public function __construct(protected ReadingRepositoryInterface $readingRepository) {}

    public function getReadings(array $params): array
    {
        if (! isset($params['device_id'])) {
            throw new BusinessException(422, 'VALIDATION_ERROR', 'Parameter "device_id" wajib diisi.');
        }

        $deviceId = $params['device_id'];
        $sensorType = $params['sensor_type'] ?? null;
        $from = isset($params['from'])
            ? $this->parseTimestamp($params['from'])
            : now('UTC')->subHours(24);
        $to = isset($params['to'])
            ? $this->parseTimestamp($params['to'])
            : now('UTC');

        if ($from->gte($to)) {
            throw new BusinessException(422, 'VALIDATION_ERROR', 'Parameter "from" harus sebelum "to".');
        }

        $rangeSeconds = abs($to->diffInSeconds($from));
        $interval = $params['interval'] ?? $this->autoSelectInterval($rangeSeconds);
        $agg = $params['agg'] ?? 'avg';

        $this->validateInterval($interval, $rangeSeconds);

        $series = $this->querySeries($deviceId, $sensorType, $from, $to, $interval, $agg);

        $grouped = $series->groupBy('sensor_type');

        $result = [];
        foreach ($grouped as $typeName => $rows) {
            $isRain = $typeName === 'rain_counter';
            $unit = $isRain ? 'mm' : $rows->first()->unit;

            $timestamps = $rows->pluck('interval_start')->map(function ($t) {
                return $t instanceof Carbon ? $t->toISOString() : Carbon::parse($t)->toISOString();
            })->values()->all();

            $values = $rows->map(function ($row) use ($isRain) {
                if ($isRain) {
                    return round($row->total_rain_mm ?? $row->rain_mm ?? 0, 2);
                }
                return round($row->value, 2);
            })->values()->all();

            $result[] = [
                'sensor_type' => $typeName,
                'unit' => $unit,
                'timestamps' => $timestamps,
                'values' => $values,
            ];
        }

        return [
            'device_id' => $deviceId,
            'interval' => $interval,
            'agg' => $agg,
            'from' => $from->toISOString(),
            'to' => $to->toISOString(),
            'series' => $result,
        ];
    }

    public function getLatestReadings(string $deviceId): array
    {
        $rows = $this->readingRepository->getLatestReadings($deviceId);

        $readings = $rows->map(function ($row) {
            $ts = $row->device_ts instanceof Carbon ? $row->device_ts : Carbon::parse($row->device_ts);
            return [
                'sensor_type' => $row->sensor_type,
                'unit' => $row->unit,
                'value' => round($row->value, 2),
                'ts' => $ts->toISOString(),
            ];
        })->values()->all();

        return [
            'device_id' => $deviceId,
            'readings' => $readings,
        ];
    }

    public function getDailySummary(?string $deviceId, string $date): array
    {
        if (! $deviceId) {
            throw new BusinessException(422, 'VALIDATION_ERROR', 'Parameter "device_id" wajib diisi.');
        }

        $summary = $this->readingRepository->getDailySummary($deviceId, $date);

        if (! $summary) {
            return [
                'device_id' => $deviceId,
                'date' => $date,
                'temp' => null,
                'rain_total' => null,
                'wind_max' => null,
                'humidity_avg' => null,
            ];
        }

        return array_merge(['device_id' => $deviceId], $summary);
    }

    private function autoSelectInterval(int $rangeSeconds): string
    {
        if ($rangeSeconds <= self::INTERVAL_RANGES['raw']) {
            return 'raw';
        }
        if ($rangeSeconds <= self::INTERVAL_RANGES['1m']) {
            return '1m';
        }
        if ($rangeSeconds <= self::INTERVAL_RANGES['1h']) {
            return '1h';
        }
        return '1d';
    }

    private function parseTimestamp(string $value): Carbon
    {
        if (ctype_digit($value)) {
            return Carbon::createFromTimestamp((int) $value, 'UTC');
        }
        return Carbon::parse($value, 'UTC');
    }

    private function validateInterval(string $interval, int $rangeSeconds): void
    {
        if (! array_key_exists($interval, self::INTERVAL_RANGES)) {
            throw new BusinessException(422, 'VALIDATION_ERROR', "Interval '{$interval}' tidak valid.");
        }

        $maxRange = self::INTERVAL_RANGES[$interval];

        if ($rangeSeconds > $maxRange) {
            $suggested = $this->autoSelectInterval($rangeSeconds);
            throw new BusinessException(
                422,
                'INTERVAL_TOO_COARSE',
                "Interval '{$interval}' tidak cukup untuk rentang ini.",
                ['suggested_interval' => $suggested],
            );
        }
    }

    private function querySeries(
        string $deviceId,
        ?string $sensorType,
        Carbon $from,
        Carbon $to,
        string $interval,
        string $agg,
    ): Collection {
        return match ($interval) {
            'raw' => $this->readingRepository->queryRaw($deviceId, $sensorType, $from, $to),
            '1m' => $this->readingRepository->query1mAggregate($deviceId, $sensorType, $from, $to, $agg),
            '1h' => $this->readingRepository->query1hAggregate($deviceId, $sensorType, $from, $to, $agg),
            '1d' => $this->readingRepository->query1dAggregate($deviceId, $sensorType, $from, $to, $agg),
        };
    }
}
