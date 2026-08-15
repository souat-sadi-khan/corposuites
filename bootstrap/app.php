<?php

use App\Http\Middleware\CheckIfInstalled;
use App\Http\Middleware\LanguageMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        // api: __DIR__.'/../routes/api.php',
        then: function () {
            Route::middleware(['check.installation'])->prefix('install')->group(base_path('routes/installer.php'));
            Route::middleware(['isInstalled', 'web'])->prefix('admin')->name('admin.')->group(base_path('routes/admin.php'));
        }
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'check.installation' => App\Http\Middleware\CheckInstallation::class,
            'isAdmin' => App\Http\Middleware\AdminPermission::class,
            'isInstalled' => App\Http\Middleware\CheckIfInstalled::class,
        ]);

        $middleware->web(append: [
            CheckIfInstalled::class,
            LanguageMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
