<?php

namespace App\Http\Controllers;

use App\Services\ReadingService;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReadingController extends Controller
{
    public function __construct(
        protected ReadingService $readingService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $params = $request->only([
            'device_id', 'sensor_type', 'from', 'to', 'interval', 'agg', 'cursor', 'per_page',
        ]);

        $data = $this->readingService->getReadings($params);

        return ApiResponse::success($data);
    }

    public function latest(string $id): JsonResponse
    {
        $data = $this->readingService->getLatestReadings($id);

        return ApiResponse::success($data);
    }

    public function summary(Request $request): JsonResponse
    {
        $deviceId = $request->input('device_id');
        $date = $request->input('date', now('Asia/Jakarta')->format('Y-m-d'));

        $data = $this->readingService->getDailySummary($deviceId, $date);

        return ApiResponse::success($data);
    }
}
