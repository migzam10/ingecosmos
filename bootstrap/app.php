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
        $middleware->alias([
            'role'   => \App\Http\Middleware\CheckRole::class,
            'activo' => \App\Http\Middleware\VerificarUsuarioActivo::class,
        ]);
        // Verificar que el usuario siga activo en cada request autenticado
        $middleware->appendToGroup('web', \App\Http\Middleware\VerificarUsuarioActivo::class);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
