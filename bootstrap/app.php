<?php

use App\Http\Middleware\CaptureUtmAttributionMiddleware;
use App\Http\Middleware\EnsureSubscriptionActiveMiddleware;
use App\Http\Middleware\ResolveTenantMiddleware;
use App\Http\Middleware\SuperAdminMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Sentry\Laravel\Integration;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            Route::middleware(['web', 'auth', 'super-admin'])
                ->prefix('admin')
                ->name('admin.')
                ->group(base_path('routes/admin.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Only answer to the application's own host. url() builds absolute links
        // from the request host — including password-reset and signed unsubscribe
        // links — so an unvalidated Host header would let those point elsewhere.
        // Derived from APP_URL; auto-disabled in local and during tests.
        $middleware->trustHosts();

        $middleware->validateCsrfTokens(except: [
            'paypal/webhook',
            'azul/webhook',
        ]);

        $middleware->web(append: [
            CaptureUtmAttributionMiddleware::class,
        ]);

        $middleware->alias([
            'tenant' => ResolveTenantMiddleware::class,
            'super-admin' => SuperAdminMiddleware::class,
            'subscription.active' => EnsureSubscriptionActiveMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Laravel 11+ requires this call for sentry/sentry-laravel to capture
        // unhandled exceptions — the service provider does not register it
        // automatically. Without it, 500s never reach Sentry.
        Integration::handles($exceptions);

        $exceptions->render(function (HttpExceptionInterface $e, Request $request) {
            if ($e->getStatusCode() !== 403 || $request->expectsJson()) {
                return null;
            }

            if (! $request->user()) {
                return redirect()->route('login');
            }

            return redirect()->route('dashboard')
                ->with('warning', 'No tienes permiso para realizar esa acción.');
        });
    })->create();
