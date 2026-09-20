<?php

namespace App\Support\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class ApiResponse
{
    public static function success(
        mixed $data = null,
        int $status = 200,
        array $meta = [],
    ): JsonResponse {
        return response()->json([
            'success' => true,
            'data' => $data,
            'meta' => array_merge(['request_id' => Str::uuid()->toString()], $meta),
        ], $status);
    }

    public static function error(
        int $status,
        string $code,
        string $message,
        array $details = [],
    ): JsonResponse {
        $error = [
            'code' => $code,
            'message' => $message,
        ];

        if (! empty($details)) {
            $error['details'] = $details;
        }

        return response()->json([
            'success' => false,
            'error' => $error,
            'meta' => ['request_id' => Str::uuid()->toString()],
        ], $status);
    }
}
