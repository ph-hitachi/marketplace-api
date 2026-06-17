<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Exceptions\InvalidCredentialsException;
use App\Exceptions\AccountDeactivatedException;

/**
 * @tags Auth
 */
class AuthController extends Controller
{
    /**
     * Register user.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $data = $request->validated();

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'role' => $data['role'],
        ]);

        if ($user->role === 'seller') {
            $user->sellerProfile()->create([
                'shop_name' => $data['shop_name'],
                'shop_description' => $data['shop_description'] ?? null,
            ]);
        }

        $token = $user->createToken(
            'auth_token',
            ['*'],
            now()->addDays((int) config('sanctum.token_expiry_days', 3))
        );

        return response()->json([
            'message' => 'Registration successful.',
            'user' => $user->load('sellerProfile'),
            'access_token' => $token->plainTextToken,
            'token_type' => 'Bearer',
        ], 201);
    }

    /**
     * Login user.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->only('email', 'password');

        if (!Auth::attempt($credentials)) {
            throw new InvalidCredentialsException();
        }

        /** @var User $user */
        $user = Auth::user();

        if (!$user->is_active) {
            Auth::logout();
            throw new AccountDeactivatedException();
        }

        // Revoke old tokens to enforce single-session per demo
        $user->tokens()->delete();

        $token = $user->createToken(
            'auth_token',
            ['*'],
            now()->addDays((int) config('sanctum.token_expiry_days', 3))
        );

        return response()->json([
            'message' => 'Login successful.',
            'user' => $user->load('sellerProfile'),
            'access_token' => $token->plainTextToken,
            'token_type' => 'Bearer',
        ]);
    }

    /**
     * Logout user.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully.']);
    }

    /**
     * View profile.
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load('sellerProfile');

        return response()->json(['user' => $user]);
    }
}
