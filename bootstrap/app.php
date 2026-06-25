<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        apiPrefix: 'api/v1',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Alias de middleware de roles/permisos (Spatie) — ver docs/11_SECURITY.md
        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
            'admin' => \App\Http\Middleware\EnsureAdmin::class,
        ]);

        // Invitados no autenticados: al login del panel si están bajo /admin, al login del cliente si no.
        $middleware->redirectGuestsTo(fn ($request) => $request->is('admin', 'admin/*')
            ? route('admin.login')
            : route('login'));
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
