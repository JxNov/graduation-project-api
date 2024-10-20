<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Illuminate\Http\JsonResponse;

class AuthController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['login', 'refresh', 'profile']]);
    }

    public function login(Request $request): JsonResponse
    {
        $credentials = $request->only('email', 'password');

        if (!$token = auth()->attempt($credentials)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return $this->respondWithToken($token);
    }

    public function profile(): JsonResponse
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();

            $user = [
                'name' => $user->name,
                'username' => $user->username,
                'date_of_birth' => $user->date_of_birth,
                'gender' => $user->gender,
                'phone_number' => $user->phone_number,
                'email' => $user->email,
            ];

            return response()->json($user);
        } catch (JWTException $exception) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
    }

    public function logout(): JsonResponse
    {
        auth()->logout();

        return response()
            ->json(['message' => 'Successfully logged out'])
            ->cookie('token', null, 0, null, null, false, true);
    }

    public function refresh(Request $request): JsonResponse
    {
        try {
            $token = $request->cookie('token');

            if (!$token) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }

            $newToken = auth()->refresh($token);

            return $this->respondWithToken($newToken);
        } catch (JWTException $exception) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
    }

    private function respondWithToken($token): JsonResponse
    {
        return response()
            ->json([
                'status' => 'success',
            ])
            ->cookie('token', $token, 60 * 24 * 30, null, null, false, true);
    }
}
