<?php

namespace App\Repositories;

use App\Interfaces\ReadingRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ReadingRepository implements ReadingRepositoryInterface
{
    public function queryRaw(string $deviceId, ?string $sensorType, Carbon $from, Carbon $to): Collection
    {
        $query = DB::table('sensor_readings as sr')
            ->join('sensors as s', 's.id', '=', 'sr.sensor_id')
            ->join('sensor_types as st', 'st.id', '=', 's.sensor_type_id')
            ->where('sr.device_id', $deviceId)
            ->where('sr.quality_flag', 'OK')
            ->whereBetween('sr.device_ts', [$from, $to])
            ->select(
                'sr.device_ts',
                'st.name as sensor_type',
                'st.unit',
                'sr.calibrated_value as value',
                'sr.rain_mm',
            );

        if ($sensorType) {
            $query->where('st.name', $sensorType);
        }

        return $query->orderBy('sr.device_ts')->get();
    }

    public function queryAggregated(
        string $deviceId,
        ?string $sensorType,
        Carbon $from,
        Carbon $to,
        string $interval,
        string $agg,
    ): Collection {
        $bucketInterval = match ($interval) {
            '1m' => '1 minute',
            '1h' => '1 hour',
            '1d' => '1 day',
            default => '1 hour',
        };

        $selectColumn = match ($agg) {
            'min' => 'MIN(sr.calibrated_value)',
            'max' => 'MAX(sr.calibrated_value)',
            'sum' => 'SUM(sr.calibrated_value)',
            default => 'AVG(sr.calibrated_value)',
        };

        $rainSelect = $agg === 'sum'
            ? 'SUM(CASE WHEN st.name = \'rain_counter\' THEN sr.rain_mm ELSE 0 END) as rain_total'
            : '0 as rain_total';

        $query = DB::table('sensor_readings as sr')
            ->join('sensors as s', 's.id', '=', 'sr.sensor_id')
            ->join('sensor_types as st', 'st.id', '=', 's.sensor_type_id')
            ->where('sr.device_id', $deviceId)
            ->where('sr.quality_flag', 'OK')
            ->whereBetween('sr.device_ts', [$from, $to])
            ->select(
                DB::raw("time_bucket('{$bucketInterval}', sr.device_ts) as interval_start"),
                'st.name as sensor_type',
                'st.unit',
                DB::raw("{$selectColumn} as value"),
                DB::raw($rainSelect),
                DB::raw('COUNT(*) as sample_count'),
            )
            ->groupBy('interval_start', 'st.name', 'st.unit')
            ->orderBy('interval_start');

        if ($sensorType) {
            $query->where('st.name', $sensorType);
        }

        return $query->get();
    }

    public function query1mAggregate(
        string $deviceId,
        ?string $sensorType,
        Carbon $from,
        Carbon $to,
        string $agg,
    ): Collection {
        $selectColumn = match ($agg) {
            'min' => 'min_value',
            'max' => 'max_value',
            'sum' => 'total_rain_mm',
            default => 'avg_value',
        };

        $query = DB::table('reading_agg_1m_mv')
            ->where('device_id', $deviceId)
            ->whereBetween('interval_start', [$from, $to])
            ->select(
                'interval_start',
                'sensor_type_id',
                DB::raw("{$selectColumn} as value"),
                'total_rain_mm',
                'sample_count',
            )
            ->orderBy('interval_start');

        if ($sensorType) {
            $sensorTypeId = DB::table('sensor_types')->where('name', $sensorType)->value('id');
            if ($sensorTypeId) {
                $query->where('sensor_type_id', $sensorTypeId);
            }
        }

        $rows = $query->get();

        return $this->enrichWithSensorType($rows);
    }

    public function query1hAggregate(
        string $deviceId,
        ?string $sensorType,
        Carbon $from,
        Carbon $to,
        string $agg,
    ): Collection {
        $selectColumn = match ($agg) {
            'min' => 'min_value',
            'max' => 'max_value',
            'sum' => 'total_rain_mm',
            default => 'avg_value',
        };

        $query = DB::table('reading_agg_1h_mv')
            ->where('device_id', $deviceId)
            ->whereBetween('interval_start', [$from, $to])
            ->select(
                'interval_start',
                'sensor_type_id',
                DB::raw("{$selectColumn} as value"),
                'total_rain_mm',
                'sample_count',
            )
            ->orderBy('interval_start');

        if ($sensorType) {
            $sensorTypeId = DB::table('sensor_types')->where('name', $sensorType)->value('id');
            if ($sensorTypeId) {
                $query->where('sensor_type_id', $sensorTypeId);
            }
        }

        $rows = $query->get();

        return $this->enrichWithSensorType($rows);
    }

    public function query1dAggregate(
        string $deviceId,
        ?string $sensorType,
        Carbon $from,
        Carbon $to,
        string $agg,
    ): Collection {
        $selectColumn = match ($agg) {
            'min' => 'min_value',
            'max' => 'max_value',
            'sum' => 'total_rain_mm',
            default => 'avg_value',
        };

        $query = DB::table('reading_agg_1d_mv')
            ->where('device_id', $deviceId)
            ->whereBetween('interval_start', [$from, $to])
            ->select(
                'interval_start',
                'sensor_type_id',
                DB::raw("{$selectColumn} as value"),
                'total_rain_mm',
                'sample_count',
            )
            ->orderBy('interval_start');

        if ($sensorType) {
            $sensorTypeId = DB::table('sensor_types')->where('name', $sensorType)->value('id');
            if ($sensorTypeId) {
                $query->where('sensor_type_id', $sensorTypeId);
            }
        }

        $rows = $query->get();

        return $this->enrichWithSensorType($rows);
    }

    public function getLatestReadings(string $deviceId): Collection
    {
        $latestTs = DB::table('sensor_readings as sr')
            ->join('sensors as s', 's.id', '=', 'sr.sensor_id')
            ->join('sensor_types as st', 'st.id', '=', 's.sensor_type_id')
            ->where('sr.device_id', $deviceId)
            ->where('sr.quality_flag', 'OK')
            ->select('st.name as sensor_type', DB::raw('MAX(sr.device_ts) as max_ts'))
            ->groupBy('st.name');

        return DB::table('sensor_readings as sr')
            ->join('sensors as s', 's.id', '=', 'sr.sensor_id')
            ->join('sensor_types as st', 'st.id', '=', 's.sensor_type_id')
            ->joinSub($latestTs, 'latest', function ($join) {
                $join->on('st.name', '=', 'latest.sensor_type')
                     ->on('sr.device_ts', '=', 'latest.max_ts');
            })
            ->where('sr.device_id', $deviceId)
            ->select(
                'st.name as sensor_type',
                'st.unit',
                'sr.calibrated_value as value',
                'sr.device_ts',
            )
            ->get();
    }

    public function getDailySummary(string $deviceId, string $date): ?array
    {
        $start = Carbon::parse($date, 'Asia/Jakarta')->startOfDay()->setTimezone('UTC');
        $end = $start->copy()->addDay();

        if (DB::getDriverName() !== 'pgsql') {
            return $this->getDailySummaryFallback($deviceId, $start, $end);
        }

        $rows = DB::table('reading_agg_1d_mv')
            ->where('device_id', $deviceId)
            ->whereBetween('interval_start', [$start, $end])
            ->get();

        if ($rows->isEmpty()) {
            return null;
        }

        $summary = ['date' => $date];

        foreach ($rows as $row) {
            $typeName = DB::table('sensor_types')->where('id', $row->sensor_type_id)->value('name');

            if ($typeName === 'temp_air') {
                $summary['temp'] = [
                    'min' => round($row->min_value, 1),
                    'max' => round($row->max_value, 1),
                    'avg' => round($row->avg_value, 1),
                ];
            } elseif ($typeName === 'rain_counter') {
                $summary['rain_total'] = round($row->total_rain_mm, 1);
            } elseif ($typeName === 'wind_speed') {
                $summary['wind_max'] = round($row->max_value, 1);
            } elseif ($typeName === 'humidity') {
                $summary['humidity_avg'] = round($row->avg_value, 1);
            }
        }

        return $summary;
    }

    private function getDailySummaryFallback(string $deviceId, Carbon $from, Carbon $to): ?array
    {
        $rows = DB::table('sensor_readings as sr')
            ->join('sensors as s', 's.id', '=', 'sr.sensor_id')
            ->join('sensor_types as st', 'st.id', '=', 's.sensor_type_id')
            ->where('sr.device_id', $deviceId)
            ->where('sr.quality_flag', 'OK')
            ->whereBetween('sr.device_ts', [$from, $to])
            ->select(
                'st.name as sensor_type',
                DB::raw('MIN(sr.calibrated_value) as min_val'),
                DB::raw('MAX(sr.calibrated_value) as max_val'),
                DB::raw('AVG(sr.calibrated_value) as avg_val'),
                DB::raw('SUM(sr.rain_mm) as rain_total'),
            )
            ->groupBy('st.name')
            ->get();

        if ($rows->isEmpty()) {
            return null;
        }

        $summary = ['date' => $from->setTimezone('Asia/Jakarta')->format('Y-m-d')];

        foreach ($rows as $row) {
            if ($row->sensor_type === 'temp_air') {
                $summary['temp'] = [
                    'min' => round($row->min_val, 1),
                    'max' => round($row->max_val, 1),
                    'avg' => round($row->avg_val, 1),
                ];
            } elseif ($row->sensor_type === 'rain_counter') {
                $summary['rain_total'] = round($row->rain_total ?? 0, 1);
            } elseif ($row->sensor_type === 'wind_speed') {
                $summary['wind_max'] = round($row->max_val, 1);
            } elseif ($row->sensor_type === 'humidity') {
                $summary['humidity_avg'] = round($row->avg_val, 1);
            }
        }

        return $summary;
    }

    private function enrichWithSensorType(Collection $rows): Collection
    {
        $types = DB::table('sensor_types')
            ->get(['id', 'name', 'unit'])
            ->keyBy('id');

        return $rows->map(function ($row) use ($types) {
            $type = $types->get($row->sensor_type_id);
            $row->sensor_type = $type?->name ?? $row->sensor_type_id;
            $row->unit = $type?->unit ?? '';
            return $row;
        });
    }
}
