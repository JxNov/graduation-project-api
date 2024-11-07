<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Exceptions\JWTException;

class CheckAdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();

            if ($user === null || !$user->isAdmin()) {
                return response()->json(
                    [
                        'error' => 'Bạn không có quyền truy cập'
                    ],
                    Response::HTTP_FORBIDDEN
                );
            }
        } catch (TokenExpiredException $e) {
            return response()->json(
                [
                    'error' => 'Token đã hết hạn'
                ],
                Response::HTTP_UNAUTHORIZED
            );
        } catch (TokenInvalidException $e) {
            return response()->json(
                [
                    'error' => 'Token không hợp lệ'
                ],
                Response::HTTP_UNAUTHORIZED
            );
        } catch (JWTException $e) {
            return response()->json(
                [
                    'error' => 'Token không được cung cấp'
                ],
                Response::HTTP_UNAUTHORIZED
            );
        }

        return $next($request);
    }
}
