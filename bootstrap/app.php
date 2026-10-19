<?php

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->render(function (ValidationException $e, Request $request) {
            if (! $request->is('api/*') && ! $request->expectsJson()) {
                return null;
            }

            return response()->json([
                'success' => false,
                'message' => 'The given data was invalid.',
                'errors' => $e->errors(),
            ], $e->status);
        });

        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if (! $request->is('api/*') && ! $request->expectsJson()) {
                return null;
            }

            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated. Please log in to continue.',
            ], 401);
        });

        $exceptions->render(function (AccessDeniedHttpException $e, Request $request) {
            if (! $request->is('api/*') && ! $request->expectsJson()) {
                return null;
            }

            return response()->json([
                'success' => false,
                'message' => 'This action is unauthorized.',
            ], 403);
        });

        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if (! $request->is('api/*') && ! $request->expectsJson()) {
                return null;
            }

            return response()->json([
                'success' => false,
                'message' => 'The requested resource was not found.',
            ], 404);
        });

        $exceptions->render(function (ThrottleRequestsException $e, Request $request) {
            if (! $request->is('api/*') && ! $request->expectsJson()) {
                return null;
            }

            return response()->json([
                'success' => false,
                'message' => 'Too many requests. Please try again later.',
            ], 429);
        });

        $exceptions->render(function (HttpExceptionInterface $e, Request $request) {
            if (! $request->is('api/*') && ! $request->expectsJson()) {
                return null;
            }

            return response()->json([
                'success' => false,
                'message' => $e->getMessage() !== '' ? $e->getMessage() : 'The request could not be processed.',
            ], $e->getStatusCode());
        });
    })->create();
