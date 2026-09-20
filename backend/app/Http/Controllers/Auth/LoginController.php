<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\Sanctum;

class LoginController extends Controller
{
    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->validated();

        if (! Auth::attempt($credentials)) {
            return ApiResponse::error(401, 'UNAUTHENTICATED', 'Email atau password salah.');
        }

        /** @var \App\Models\User $user */
        $user = Auth::user();
        $token = $user->createToken('auth-token')->plainTextToken;

        return ApiResponse::success([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'token' => $token,
        ]);
    }
}
