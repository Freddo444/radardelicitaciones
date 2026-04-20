<?php

use App\Http\Middleware\EnsureSubscriptionActiveMiddleware;
use App\Http\Middleware\ResolveTenantMiddleware;
use App\Http\Middleware\SuperAdminMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
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
        $middleware->validateCsrfTokens(except: [
            'paypal/webhook',
            'azul/webhook',
        ]);

        $middleware->alias([
            'tenant' => ResolveTenantMiddleware::class,
            'super-admin' => SuperAdminMiddleware::class,
            'subscription.active' => EnsureSubscriptionActiveMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
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
