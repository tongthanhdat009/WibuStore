<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RequirePermission
{
    public function handle(Request $request, Closure $next, string $permission)
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['error' => ['code' => 'UNAUTHENTICATED', 'message' => 'Chua dang nhap']], 401);
        }
        if (! $user->hasPermission($permission)) {
            return response()->json(['error' => ['code' => 'FORBIDDEN', 'message' => 'Khong co quyen: '.$permission]], 403);
        }

        return $next($request);
    }
}
