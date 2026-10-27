<?php

use App\Http\Middleware\ForceJsonResponse;
use App\Http\Middleware\ThrottleApiRequests;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->api(prepend: [
            ForceJsonResponse::class,
        ]);

        $middleware->append([
            ThrottleApiRequests::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request, Throwable $e): bool => $request->is('api/*'),
        );

        $exceptions->render(function (Throwable $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return match (true) {
                $e instanceof ValidationException => response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => $e->errors(),
                ], 422),
                $e instanceof AuthenticationException => response()->json([
                    'message' => 'Unauthenticated.',
                ], 401),
                $e instanceof NotFoundHttpException => response()->json([
                    'message' => 'Resource not found.',
                ], 404),
                $e instanceof MethodNotAllowedHttpException => response()->json([
                    'message' => 'Method not allowed.',
                ], 405),
                $e instanceof HttpExceptionInterface => null,
                $e instanceof HttpResponseException => null,
                // The HTTP kernel reports the exception before rendering; do not double-report it.
                default => response()->json(['message' => 'Server error.'], 500),
            };
        });
    })->create();
