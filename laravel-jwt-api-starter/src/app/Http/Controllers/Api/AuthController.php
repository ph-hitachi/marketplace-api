<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use App\Exceptions\AccountDeactivatedException;
use App\Exceptions\InvalidCredentialsException;
use Dedoc\Scramble\Attributes\Group;

#[Group('Authentication', weight: 1)]
class AuthController extends Controller
{
    /**
     * Register user.
     *
     * Register a new user account with a name, email, password, and default role.
     *
     * @param RegisterRequest $request
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $data = $request->validated();

        $user = User::create($data);

        /** @var \PHPOpenSourceSaver\JWTAuth\JWTGuard $guard */
        $guard = Auth::guard('api');
        $token = $guard->login($user);

        return response()->json([
            'user' => new UserResource($user),
            'authorization' => [
                'access_token' => $token,
                'token_type' => 'bearer',
                'expires_in' => $guard->factory()->getTTL() * 60,
            ],
        ], Response::HTTP_CREATED);
    }

    /**
     * Authenticate user.
     *
     * Authenticate a user using their email and password credentials to receive a stateless JWT access token.
     *
     * @param LoginRequest $request
     *
     * @response array{user: UserResource, authorization: array{access_token: string, token_type: string, expires_in: int}}
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->only('email', 'password');

        /** @var \PHPOpenSourceSaver\JWTAuth\JWTGuard $guard */
        $guard = Auth::guard('api');

        if (!$token = $guard->attempt($credentials)) {
            throw new InvalidCredentialsException();
        }

        /** @var User $user */
        $user = $guard->user();

        if (!$user->is_active) {
            $guard->logout();
            throw new AccountDeactivatedException();
        }

        return $this->respondWithToken($token, $user);
    }

    /**
     * Logout user.
     *
     * Revoke the user's current JWT access token and log them out of the application.
     */
    public function logout(): Response
    {
        Auth::guard('api')->logout();

        return response()->noContent();
    }

    /**
     * Refresh token.
     *
     * Refresh the user's current authentication token, invalidating the old one and returning a new JWT.
     *
     * @response array{user: UserResource, authorization: array{access_token: string, token_type: string, expires_in: int}}
     */
    public function refresh(): JsonResponse
    {
        /** @var \PHPOpenSourceSaver\JWTAuth\JWTGuard $guard */
        $guard = Auth::guard('api');
        $token = $guard->refresh();
        $user = $guard->user();

        return $this->respondWithToken($token, $user);
    }

    /**
     * Get the token array structure.
     */
    protected function respondWithToken(string $token, User $user): JsonResponse
    {
        /** @var \PHPOpenSourceSaver\JWTAuth\JWTGuard $guard */
        $guard = Auth::guard('api');

        return response()->json([
            'user' => new UserResource($user),
            'authorization' => [
                'access_token' => $token,
                'token_type' => 'bearer',
                'expires_in' => $guard->factory()->getTTL() * 60,
            ],
        ]);
    }
}
