<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, $role)
    {
        $user = $request->user();

        // ❌ kalau belum login
        if (!$user) {
            return response()->json([
                'message' => 'Unauthenticated'
            ], 401);
        }

        // ❌ kalau role ga sesuai
        if ($user->role !== $role) {
            return response()->json([
                'message' => 'Forbidden - akses ditolak'
            ], 403);
        }

        return $next($request);
    }
}