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
            $user->shop()->create([
                'shop_name' => $data['shop_name'],
                'shop_description' => $data['shop_description'] ?? null,
            ]);
        }

        $token = Auth::guard('api')->login($user);

        return $this->respondWithToken($token, 'Registration successful.', $user->load('shop'), 201);
    }

    /**
     * Login user.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->only('email', 'password');

        if (! $token = Auth::guard('api')->attempt($credentials)) {
            throw new InvalidCredentialsException();
        }

        /** @var User $user */
        $user = Auth::guard('api')->user();

        if (!$user->is_active) {
            Auth::guard('api')->logout();
            throw new AccountDeactivatedException();
        }

        return $this->respondWithToken($token, 'Login successful.', $user->load('shop'));
    }

    /**
     * Logout user.
     */
    public function logout(): JsonResponse
    {
        Auth::guard('api')->logout();

        return response()->json(['message' => 'Logged out successfully.']);
    }

    /**
     * Refresh token.
     */
    public function refresh(): JsonResponse
    {
        return $this->respondWithToken(Auth::guard('api')->refresh(), 'Token refreshed successfully.');
    }

    /**
     * View profile.
     */
    public function me(): JsonResponse
    {
        $user = Auth::guard('api')->user()->load('shop');

        return response()->json(['user' => $user]);
    }

    /**
     * Get the token array structure.
     */
    protected function respondWithToken(string $token, string $message, ?User $user = null, int $status = 200): JsonResponse
    {
        $data = [
            'message' => $message,
        ];

        if ($user) {
            $data['user'] = $user;
        }

        $data['authorization'] = [
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => Auth::guard('api')->factory()->getTTL() * 60
        ];

        return response()->json($data, $status);
    }
}
