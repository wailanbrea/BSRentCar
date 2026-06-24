<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\CustomerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Autenticación API (Sanctum).
 * Contratos: docs/06_API_CONTRACTS.md (Auth).
 */
class AuthController extends Controller
{
    public function __construct(private readonly CustomerService $customers)
    {
    }

    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::create([
            'name' => $request->string('name'),
            'email' => $request->string('email'),
            'password' => Hash::make($request->string('password')),
        ]);

        // Todo registro público es un cliente: asignar rol y crear su perfil.
        $user->assignRole('customer');
        $this->customers->createForUser($user);

        $token = $user->createToken('api')->plainTextToken;

        return response()->json([
            'user' => new UserResource($user),
            'token' => $token,
        ], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->string('email'))->first();

        if (! $user || ! Hash::check((string) $request->string('password'), $user->password)) {
            throw ValidationException::withMessages([
                'email' => [__('auth.failed')],
            ]);
        }

        $token = $user->createToken('api')->plainTextToken;

        return response()->json([
            'user' => new UserResource($user),
            'token' => $token,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([], 204);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'user' => new UserResource($request->user()),
        ]);
    }
}
