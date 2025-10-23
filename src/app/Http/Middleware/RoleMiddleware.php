<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, $role)
    {
        if (!auth()->check()) {
            abort(403);
        }
        $user = auth()->user();

        // ロールが一致しない場合は403
        if ($user->role !== $role) {
            abort(403, '権限がありません。');
        }

        return $next($request);
    }
}
