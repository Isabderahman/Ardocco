<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureRole
{
    /**
     * @param  array<int, string>  ...$roles
     */
    public function handle(Request $request, Closure $next, ...$roles)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        if (empty($roles) || in_array($user->role, $roles, true)) {
            return $next($request);
        }

        return response()->json([
            'success' => false,
            'message' => 'Forbidden.',
        ], 403);
    }
}

