<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;

class LocationController extends Controller
{
    public function index(): JsonResponse
    {
        $locations = Location::orderBy('name')->get(['id', 'name']);

        return ApiResponse::success($locations);
    }
}
