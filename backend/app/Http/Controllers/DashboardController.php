<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function __construct(
        protected DashboardService $dashboardService,
    ) {}

    public function overview(): JsonResponse
    {
        $data = $this->dashboardService->getOverview();

        return ApiResponse::success($data);
    }
}
