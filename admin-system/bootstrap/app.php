<?php

use Illuminate\Foundation\Application;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function ($middleware) {
        $middleware->api(\Illuminate\Http\Middleware\HandleCors::class);
        
        // Register middleware aliases
        $middleware->alias([
            'admin' => \App\Http\Middleware\EnsureAdmin::class,
            'superadmin' => \App\Http\Middleware\EnsureSuperAdmin::class,
            'municipalityadmin' => \App\Http\Middleware\EnsureMunicipalityAdmin::class,
        ]);
    })
    ->withExceptions(function ($exceptions) {
        //
    })->create();
