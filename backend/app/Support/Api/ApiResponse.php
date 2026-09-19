<?php

namespace App\Support\Api;

use Illuminate\Http\JsonResponse;

class ApiResponse
{
    public static function success(
        mixed $data = null,
        int $status = 200,
        array $meta = [],
    ): JsonResponse {
        $response = [
            'data' => $data,
        ];

        if (! empty($meta)) {
            $response['meta'] = $meta;
        }

        return response()->json($response, $status);
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
            'error' => $error,
        ], $status);
    }
}
