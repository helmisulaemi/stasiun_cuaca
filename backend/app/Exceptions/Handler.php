<?php

namespace App\Exceptions;

use App\Support\Api\ApiResponse;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Configuration\Exceptions as ExceptionsConfiguration;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Throwable;

class Handler
{
    public static function register(ExceptionsConfiguration $exceptions): void
    {
        $exceptions->renderable(function (Throwable $e) {
            return static::mapException($e);
        });
    }

    public static function mapException(Throwable $e): \Illuminate\Http\JsonResponse
    {
        return match (true) {
            $e instanceof ValidationException => static::validationError($e),
            $e instanceof AuthenticationException => static::unauthenticated(),
            $e instanceof \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException => static::forbidden(),
            $e instanceof NotFoundHttpException => static::notFound(),
            $e instanceof QueryException => static::handleQueryException($e),
            $e instanceof TooManyRequestsHttpException => static::rateLimited(),
            $e instanceof HttpException => static::handleHttpException($e),
            default => static::internalServerError(),
        };
    }

    public static function validationError(ValidationException $e): \Illuminate\Http\JsonResponse
    {
        $details = collect($e->errors())->map(function ($messages, $field) {
            return [
                'field' => $field,
                'code' => 'INVALID_VALUE',
                'message' => $messages[0],
            ];
        })->values()->all();

        return ApiResponse::error(
            status: 422,
            code: 'VALIDATION_ERROR',
            message: 'Data yang diberikan tidak valid.',
            details: $details,
        );
    }

    public static function unauthenticated(): \Illuminate\Http\JsonResponse
    {
        return ApiResponse::error(
            status: 401,
            code: 'UNAUTHENTICATED',
            message: 'Kredensial tidak valid.',
        );
    }

    public static function forbidden(): \Illuminate\Http\JsonResponse
    {
        return ApiResponse::error(
            status: 403,
            code: 'FORBIDDEN',
            message: 'Anda tidak memiliki akses untuk melakukan operasi ini.',
        );
    }

    public static function notFound(): \Illuminate\Http\JsonResponse
    {
        return ApiResponse::error(
            status: 404,
            code: 'NOT_FOUND',
            message: 'Resource tidak ditemukan.',
        );
    }

    public static function conflict(string $message = 'Resource sudah tersedia.'): \Illuminate\Http\JsonResponse
    {
        return ApiResponse::error(
            status: 409,
            code: 'CONFLICT',
            message: $message,
        );
    }

    public static function handleQueryException(QueryException $e): \Illuminate\Http\JsonResponse
    {
        if ($e->errorInfo[1] === 1062 || str_contains($e->getMessage(), 'Duplicate entry')) {
            return static::conflict();
        }

        return static::internalServerError();
    }

    public static function rateLimited(int $retryAfter = 60): \Illuminate\Http\JsonResponse
    {
        return ApiResponse::error(
            status: 429,
            code: 'RATE_LIMITED',
            message: 'Terlalu banyak request. Silakan coba lagi nanti.',
        )->header('Retry-After', $retryAfter);
    }

    public static function invalidRequest(): \Illuminate\Http\JsonResponse
    {
        return ApiResponse::error(
            status: 400,
            code: 'INVALID_REQUEST',
            message: 'Request tidak valid.',
        );
    }

    public static function handleHttpException(HttpException $e): \Illuminate\Http\JsonResponse
    {
        return match ($e->getStatusCode()) {
            400 => static::invalidRequest(),
            404 => static::notFound(),
            429 => static::rateLimited(),
            default => static::internalServerError(),
        };
    }

    public static function internalServerError(): \Illuminate\Http\JsonResponse
    {
        return ApiResponse::error(
            status: 500,
            code: 'INTERNAL_SERVER_ERROR',
            message: 'Terjadi kesalahan internal pada server.',
        );
    }
}
