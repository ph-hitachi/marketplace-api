<?php

use App\Exceptions\AccountDeactivatedException;
use App\Exceptions\UnexpectedErrorException;
use App\Http\Middleware\EnsureRole;
use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Apply security headers to every response
        $middleware->append(SecurityHeaders::class);

        // Middleware aliases
        $middleware->alias([
            'role'   => EnsureRole::class,
            'active' => EnsureUserIsActive::class,
        ]);

        // Apply rate limiting to the api middleware group
        $middleware->throttleApi();
    })
    ->withExceptions(function (Exceptions $exceptions): void {

        // Render all API errors as JSON
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );

        // ── UnexpectedErrorException (domain errors: balance, stock, transitions, etc.) ──
        $exceptions->render(function (UnexpectedErrorException $e, Request $request) {
            return response()->json([
                'error_code'     => $e->getErrorCode(),
                'exception_type' => class_basename($e),
                'message'        => $e->getMessage(),
            ], $e->getStatusCode());
        });

        // ── 401 Unauthenticated ────────────────────────────────────────────────────
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            return response()->json([
                'error_code'     => 'UNAUTHENTICATED',
                'exception_type' => class_basename($e),
                'message'        => 'You are not authenticated. Please provide a valid Bearer token.',
            ], Response::HTTP_UNAUTHORIZED);
        });

        // ── 403 Forbidden (Policy / Gate failures) ─────────────────────────────────
        $exceptions->render(function (AuthorizationException $e, Request $request) {
            return response()->json([
                'error_code'     => 'FORBIDDEN',
                'exception_type' => class_basename($e),
                'message'        => 'You do not have permission to perform this action.',
            ], Response::HTTP_FORBIDDEN);
        });

        $exceptions->render(function (AccessDeniedHttpException $e, Request $request) {
            return response()->json([
                'error_code'     => 'FORBIDDEN',
                'exception_type' => class_basename($e),
                'message'        => 'You do not have permission to perform this action.',
            ], Response::HTTP_FORBIDDEN);
        });

        // ── 404 Not Found (Route or Model) ─────────────────────────────────────────
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            // Unwrap ModelNotFoundException for a cleaner message
            $previous = $e->getPrevious();
            $message  = $previous instanceof ModelNotFoundException
                ? 'The requested resource was not found.'
                : 'The requested endpoint does not exist.';

            return response()->json([
                'error_code'     => 'NOT_FOUND',
                'exception_type' => $previous ? class_basename($previous) : class_basename($e),
                'message'        => $message,
            ], Response::HTTP_NOT_FOUND);
        });

        // ── 422 Validation Error ────────────────────────────────────────────────────
        $exceptions->render(function (ValidationException $e, Request $request) {
            return response()->json([
                'error_code'     => 'VALIDATION_ERROR',
                'exception_type' => class_basename($e),
                'message'        => 'The given data was invalid.',
                'errors'         => $e->errors(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        });

        // ── 429 Too Many Requests ───────────────────────────────────────────────────
        $exceptions->render(function (TooManyRequestsHttpException $e, Request $request) {
            return response()->json([
                'error_code'     => 'TOO_MANY_REQUESTS',
                'exception_type' => class_basename($e),
                'message'        => 'Too many requests. Please slow down and try again in a moment.',
            ], Response::HTTP_TOO_MANY_REQUESTS);
        });

        // ── 500 Internal Server Error (catch-all) ───────────────────────────────────
        $exceptions->render(function (\Throwable $e, Request $request) {
            if ($e instanceof UnexpectedErrorException ||
                $e instanceof AuthenticationException ||
                $e instanceof AuthorizationException ||
                $e instanceof AccessDeniedHttpException ||
                $e instanceof ValidationException ||
                $e instanceof NotFoundHttpException ||
                $e instanceof TooManyRequestsHttpException) {
                return null;
            }

            if ($request->is('api/*')) {
                report($e); // Still logs to Laravel log
                $unexpected = new UnexpectedErrorException('Sorry, something went wrong on the server. Please try again later.');
                return response()->json([
                    'error_code'     => $unexpected->getErrorCode(),
                    'exception_type' => class_basename($unexpected),
                    'message'        => $unexpected->getMessage(),
                ], $unexpected->getStatusCode());
            }
        });

    })->create();
