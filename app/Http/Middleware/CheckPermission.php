<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response) $next
     */
    public function handle(Request $request, Closure $next, $permission): Response
    {
        $user = Auth::user();

        if ($user && $user->hasPermission($permission)) {
            return $next($request);
        }

        return response()->json(['message' => 'Forbidden'], 403);
    }
}
