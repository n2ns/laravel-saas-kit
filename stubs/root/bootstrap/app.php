<?php

use App\Http\Middleware\AuthenticateApiKey;
use App\Http\Middleware\EnsureActiveApiSession;
use App\Http\Middleware\EnsureAuthEpoch;
use App\Http\Middleware\SetLocale;
use App\Http\Middleware\TrackSiteVisit;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectTo(fn (Request $request) => localized_route('login', [
            'locale' => $request->route('locale') ?? $request->segment(1),
        ]));
        $middleware->redirectUsersTo(fn (Request $request) => localized_route('dashboard', [
            'locale' => $request->route('locale') ?? $request->segment(1),
        ]));

        $middleware->web(append: [
            SetLocale::class,
            TrackSiteVisit::class,
        ]);

        $middleware->prependToPriorityList(
            before: SubstituteBindings::class,
            prepend: SetLocale::class,
        );

        $middleware->validateCsrfTokens(except: [
            'stripe/webhook',
            'auth/google/one-tap', // Google One Tap posts directly from Google JS
        ]);

        $middleware->encryptCookies(except: [
            'g_csrf_token', // Google One Tap client-side cookie
        ]);

        $middleware->alias([
            'api_key' => AuthenticateApiKey::class,
            'set-locale' => SetLocale::class,
            'auth.epoch' => EnsureAuthEpoch::class,
            'auth.session' => EnsureActiveApiSession::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (Throwable $e, Request $request) {
            // Skip ValidationException to preserve Laravel's native validation error format
            if ($e instanceof ValidationException) {
                return null;
            }

            if ($request->is('api/*') || $request->expectsJson()) {
                $status = match (true) {
                    $e instanceof AuthenticationException => 401,
                    $e instanceof AuthorizationException => 403,
                    $e instanceof HttpException => $e->getStatusCode(),
                    default => 500,
                };

                $message = $e->getMessage();
                $error = class_basename($e);

                if ($status >= 500 && ! config('app.debug')) {
                    $error = 'ServerError';
                    $message = SymfonyResponse::$statusTexts[$status] ?? 'Server Error';
                } elseif ($message === '') {
                    $message = SymfonyResponse::$statusTexts[$status] ?? 'Error';
                }

                return response()->json([
                    'success' => false,
                    'error' => $error,
                    'message' => $message,
                ], $status);
            }
        });
    })->create();
