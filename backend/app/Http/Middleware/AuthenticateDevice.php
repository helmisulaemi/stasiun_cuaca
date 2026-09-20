<?php

namespace App\Http\Middleware;

use App\Models\Device;
use App\Support\Api\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateDevice
{
    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = $request->header('X-Api-Key');

        if (! $apiKey) {
            return ApiResponse::error(401, 'UNAUTHENTICATED', 'Kredensial tidak valid.');
        }

        $computedHash = hash('sha256', $apiKey);

        $device = Device::withTrashed()
            ->where('secret_hash', $computedHash)
            ->first();

        if (! $device || $device->trashed()) {
            return ApiResponse::error(401, 'UNAUTHENTICATED', 'Kredensial tidak valid.');
        }

        if ($device->status !== 'active') {
            return ApiResponse::error(403, 'FORBIDDEN', 'Device tidak aktif.');
        }

        $request->attributes->set('device', $device);

        return $next($request);
    }
}
