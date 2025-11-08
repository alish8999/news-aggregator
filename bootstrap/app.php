<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Auth\AuthenticationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Database\Eloquent\ModelNotFoundException;


return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Register the ForceJsonResponse middleware for API routes
        $middleware->api(prepend: [
            \App\Http\Middleware\ForceJsonResponse::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // 401 - Unauthenticated (Missing or invalid token)
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated. Please provide a valid Bearer token.',
                    'errors' => [
                        'authentication' => ['You must be authenticated to access this resource.']
                    ]
                ], 401);
            }
        });

        // 403 - Forbidden (Authenticated but not authorized)
        $exceptions->render(function (AuthorizationException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Forbidden. You do not have permission to access this resource.',
                    'errors' => [
                        'authorization' => [$e->getMessage() ?: 'This action is unauthorized.']
                    ]
                ], 403);
            }
        });

        // 404 - Not Found (Route or resource not found)
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Resource not found.',
                    'errors' => [
                        'route' => ['The requested endpoint does not exist.']
                    ]
                ], 404);
            }
        });

        // 404 - Model Not Found (Eloquent model not found)
        $exceptions->render(function (ModelNotFoundException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                $model = strtolower(class_basename($e->getModel()));
                return response()->json([
                    'success' => false,
                    'message' => "The requested {$model} was not found.",
                    'errors' => [
                        'resource' => ["No {$model} found with the given identifier."]
                    ]
                ], 404);
            }
        });

        // 405 - Method Not Allowed (Wrong HTTP method)
        $exceptions->render(function (MethodNotAllowedHttpException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Method not allowed.',
                    'errors' => [
                        'method' => ['The HTTP method used is not allowed for this endpoint.']
                    ]
                ], 405);
            }
        });

        // 422 - Validation Error
        $exceptions->render(function (ValidationException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors' => $e->errors()
                ], 422);
            }
        });

        // 429 - Too Many Requests (Rate limiting)
        $exceptions->render(function (ThrottleRequestsException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Too many requests. Please slow down.',
                    'errors' => [
                        'rate_limit' => ['You have exceeded the rate limit. Please try again later.']
                    ]
                ], 429);
            }
        });

        // 500 - Server Error and other exceptions
        $exceptions->render(function (Throwable $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                // Get status code
                $statusCode = method_exists($e, 'getStatusCode')
                    ? $e->getStatusCode()
                    : 500;

                // Determine message based on environment
                $message = config('app.debug')
                    ? $e->getMessage()
                    : 'An error occurred while processing your request.';

                $response = [
                    'success' => false,
                    'message' => $message,
                ];

                // Include detailed error information only in debug mode
                if (config('app.debug')) {
                    $response['errors'] = [
                        'exception' => get_class($e),
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                        'trace' => collect($e->getTrace())->take(5)->map(function ($trace) {
                            return [
                                'file' => $trace['file'] ?? 'unknown',
                                'line' => $trace['line'] ?? 'unknown',
                                'function' => $trace['function'] ?? 'unknown',
                            ];
                        })->toArray()
                    ];
                }

                return response()->json($response, $statusCode);
            }
        });
    })->create();
