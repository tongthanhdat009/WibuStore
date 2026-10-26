<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RequireSourceToken
{
    /** Header-only auth cho connector goi tu server sang server (khong dung session Sanctum). */
    public function handle(Request $request, Closure $next)
    {
        $expected = (string) config('services.odoo.source_token', '');
        $provided = (string) $request->header('X-Source-Token', '');
        if ($expected === '' || $provided === '' || ! hash_equals($expected, $provided)) {
            return response()->json(['error' => ['code' => 'UNAUTHENTICATED', 'message' => 'Source token khong hop le']], 401);
        }

        return $next($request);
    }
}
