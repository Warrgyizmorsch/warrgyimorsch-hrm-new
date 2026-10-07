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
    ->withMiddleware(function (Middleware $middleware): void {

        $middleware->alias([
        'role.access' => \App\Http\Middleware\RoleAccess::class,
        ]);

        // New employees with an unapproved self-service profile only see "Complete your profile".
        $middleware->appendToGroup('web', \App\Http\Middleware\EnsureProfileApproved::class);

    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();