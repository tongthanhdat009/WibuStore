<?php

use App\Http\Middleware\ForceJson;
use App\Http\Middleware\RequirePermission;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\HandleCors;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->statefulApi();
        $middleware->validateCsrfTokens(except: [
            'api/v1/webhooks/*',
        ]);
        $middleware->append(HandleCors::class);
        $middleware->alias(['perm' => RequirePermission::class]);
        $middleware->appendToGroup('api', ForceJson::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json(['error' => ['code' => 'UNAUTHENTICATED', 'message' => 'Chua dang nhap']], 401);
            }
        });
        $exceptions->render(function (AuthorizationException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json(['error' => ['code' => 'FORBIDDEN', 'message' => 'Khong co quyen truy cap']], 403);
            }
        });
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json(['error' => ['code' => 'NOT_FOUND', 'message' => 'Khong tim thay']], 404);
            }
        });
    })->create();
