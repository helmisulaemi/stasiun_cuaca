<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTelemetryRequest;
use App\Services\IngestService;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IngestController extends Controller
{
    public function __construct(
        protected IngestService $ingestService,
    ) {}

    public function store(StoreTelemetryRequest $request): JsonResponse
    {
        $device = $request->attributes->get('device');
        $data = $request->validated();

        $result = $this->ingestService->processSingle($device, $data);

        return ApiResponse::success([
            'received' => $result['received'],
            'accepted' => $result['accepted'],
            'duplicates' => $result['duplicates'],
            'rejected' => $result['rejected'],
            'items' => $result['items'],
        ]);
    }

    public function heartbeat(Request $request): JsonResponse
    {
        $device = $request->attributes->get('device');
        $data = $request->only(['battery_v', 'rssi', 'fw']);

        $result = $this->ingestService->processHeartbeat($device, $data);

        return ApiResponse::success($result);
    }
}
