<?php

use App\Http\Middleware\EnsureRequestId;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        apiPrefix: 'api/v1',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->prepend(EnsureRequestId::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->render(function (Throwable $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                $status = $e instanceof HttpExceptionInterface ? $e->getStatusCode() : 500;
                $requestId = $request->attributes->get('request_id');

                $payload = [
                    'error' => [
                        'code' => $status,
                        'message' => $status === 500 && !config('app.debug') 
                            ? 'Une erreur interne est survenue.' 
                            : $e->getMessage(),
                        'request_id' => $requestId,
                    ],
                ];

                if (config('app.debug') && $status === 500) {
                    $payload['error']['trace'] = $e->getFile() . ':' . $e->getLine();
                }

                return response()->json($payload, $status, [
                    'X-Request-Id' => $requestId,
                ]);
            }
        });
    })->create();
