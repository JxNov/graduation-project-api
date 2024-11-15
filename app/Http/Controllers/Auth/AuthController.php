<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
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

            if (!$user) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }

            $user->roles = $user->roles()->get();

            $permissions = [];
            foreach ($user->roles as $role) {
                $permissions = array_merge($permissions, $role->permissions()->pluck('value')->toArray());
            }
            $user->permissions = array_merge($permissions, $user->permissions()->pluck('value')->toArray());
            $user->permissions = array_values(array_unique($user->permissions));

            $user = [
                'name' => $user->name,
                'username' => $user->username,
                'dateOfBirth' => $user->date_of_birth,
                'gender' => $user->gender,
                'phoneNumber' => $user->phone_number,
                'email' => $user->email,
                'roles' => $user->roles->pluck('name'),
                'permissions' => $user->permissions,
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
        $minutes = 60 * 24 * 30;
        $cookie = Cookie::make('token', $token, $minutes, null, null, false, true);

        return response()
            ->json([
                'message' => 'Successfully',
            ])
            ->cookie($cookie);
    }
}
