<?php

use App\Http\Middleware\EnsureRequestId;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
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
                $requestId = $request->attributes->get('request_id');

                $status = match (true) {
                    $e instanceof AuthenticationException => 401,
                    $e instanceof AuthorizationException => 403,
                    $e instanceof ModelNotFoundException => 404,
                    $e instanceof ValidationException => 422,
                    $e instanceof HttpExceptionInterface => $e->getStatusCode(),
                    default => 500,
                };

                $message = match ($status) {
                    401 => 'Non authentifié.',
                    403 => 'Action non autorisée.',
                    404 => 'Ressource introuvable.',
                    default => ($status === 500 && !config('app.debug'))
                        ? 'Une erreur interne est survenue.'
                        : $e->getMessage(),
                };

                $payload = [
                    'error' => [
                        'code' => $status,
                        'message' => $message,
                        'request_id' => $requestId,
                    ],
                ];

                if ($e instanceof ValidationException) {
                    $payload['error']['validation_errors'] = $e->errors();
                }

                if (config('app.debug') && $status === 500) {
                    $payload['error']['trace'] = $e->getFile() . ':' . $e->getLine();
                }

                return response()->json($payload, $status, [
                    'X-Request-Id' => $requestId,
                ]);
            }
        });
    })->create();
