<?php

namespace App\Http\Middleware;

use Closure;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Facades\JWTAuth;

class JwtAuthFromCookie
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->cookie('token');
        if ($token) {
            try {
                JWTAuth::setToken($token);

                $user = JWTAuth::authenticate();

                Auth::login($user);
            } catch (Exception $e) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }
        }
        return $next($request);
    }
}
