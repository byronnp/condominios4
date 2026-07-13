<?php

use App\Exceptions\Auth\InvalidCredentialsException;
use App\Exceptions\Auth\InvitationAlreadyUsedException;
use App\Exceptions\Auth\InvitationExpiredException;
use App\Exceptions\Auth\RefreshTokenExpiredException;
use App\Exceptions\Auth\RefreshTokenRevokedException;
use App\Exceptions\Auth\SessionInactiveException;
use App\Exceptions\Auth\UserAccessDisabledException;
use App\Exceptions\Auth\UserInactiveException;
use App\Exceptions\Condominiums\CondominiumForbiddenException;
use App\Exceptions\Condominiums\CondominiumInactiveException;
use App\Support\Api\ApiResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
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
            fn (Request $request) => $request->is('api/*'),
        );

        $exceptions->render(function (ValidationException $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return ApiResponse::error(
                message: 'Los datos enviados no son válidos.',
                status: 422,
                errors: $exception->errors(),
                code: 'validation_failed',
            );
        });

        $exceptions->render(function (AuthenticationException $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return ApiResponse::error(
                message: 'No autenticado.',
                status: 401,
                code: 'unauthenticated',
            );
        });

        $exceptions->render(function (AuthorizationException $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return ApiResponse::error(
                message: 'No tienes permiso para realizar esta acción.',
                status: 403,
                code: 'forbidden',
            );
        });

        $exceptions->render(function (InvalidCredentialsException $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return ApiResponse::error($exception->getMessage(), 401, code: 'invalid_credentials');
        });

        $exceptions->render(function (UserAccessDisabledException $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return ApiResponse::error($exception->getMessage(), 403, code: 'user_access_disabled');
        });

        $exceptions->render(function (UserInactiveException $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return ApiResponse::error($exception->getMessage(), 403, code: 'user_inactive');
        });

        $exceptions->render(function (SessionInactiveException $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return ApiResponse::error($exception->getMessage(), 401, code: 'session_invalid');
        });

        $exceptions->render(function (RefreshTokenExpiredException $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return ApiResponse::error($exception->getMessage(), 401, code: 'refresh_token_expired');
        });

        $exceptions->render(function (RefreshTokenRevokedException $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return ApiResponse::error($exception->getMessage(), 401, code: 'refresh_token_revoked');
        });

        $exceptions->render(function (InvitationExpiredException $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return ApiResponse::error($exception->getMessage(), 422, code: 'access_invitation_expired');
        });

        $exceptions->render(function (InvitationAlreadyUsedException $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return ApiResponse::error($exception->getMessage(), 422, code: 'access_invitation_used');
        });

        $exceptions->render(function (CondominiumForbiddenException $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return ApiResponse::error($exception->getMessage(), 403, code: 'condominium_forbidden');
        });

        $exceptions->render(function (CondominiumInactiveException $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return ApiResponse::error($exception->getMessage(), 403, code: 'condominium_inactive');
        });

        $exceptions->render(function (ThrottleRequestsException $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return ApiResponse::error(
                message: 'Demasiados intentos. Intenta nuevamente más tarde.',
                status: 429,
                code: 'too_many_attempts',
            );
        });

        $exceptions->render(function (ModelNotFoundException|NotFoundHttpException $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return ApiResponse::error(
                message: 'Recurso no encontrado.',
                status: 404,
                code: 'not_found',
            );
        });

        $exceptions->render(function (MethodNotAllowedHttpException $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return ApiResponse::error(
                message: 'Método HTTP no permitido para esta ruta.',
                status: 405,
                code: 'method_not_allowed',
            );
        });

        $exceptions->render(function (Throwable $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            $status = $exception instanceof HttpExceptionInterface
                ? $exception->getStatusCode()
                : 500;

            return ApiResponse::error(
                message: $status >= 500 ? 'Error interno del servidor.' : 'No se pudo procesar la solicitud.',
                status: $status,
                code: $status >= 500 ? 'server_error' : 'request_error',
            );
        });
    })->create();
