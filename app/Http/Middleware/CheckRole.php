<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request untuk verifikasi role
     */
    public function handle(Request $request, Closure $next, $role = 'admin'): Response
    {
        $user = auth('api')->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized - Token tidak valid'
            ], 401);
        }

        // Jika user role tidak sesuai dengan required role
        if ($user->role !== $role) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden - Anda tidak memiliki akses untuk resource ini',
                'required_role' => $role,
                'your_role' => $user->role
            ], 403);
        }

        return $next($request);
    }
}
