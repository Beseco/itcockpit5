<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Öffentliche Token-Routen vom CSRF-Schutz ausnehmen
        // (Token im URL ist die eigentliche Sicherheit)
        $middleware->validateCsrfTokens(except: [
            'offboarding/bestaetigung/*',
            'offboarding/admin/*',
            'revision/*',
        ]);

        $middleware->appendToGroup('web', [
            \App\Http\Middleware\EnsureUserIsActive::class,
        ]);

        $middleware->alias([
            'role' => \App\Http\Middleware\CheckRole::class,
            'module.permission' => \App\Http\Middleware\CheckModulePermission::class,
            'module.access' => \App\Http\Middleware\CheckModuleAccess::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
