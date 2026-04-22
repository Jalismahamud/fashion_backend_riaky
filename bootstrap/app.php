<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        channels: __DIR__ . '/../routes/channels.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
        then: function () {
            Route::middleware(['web', 'auth', 'admin'])->prefix('admin')->group(base_path('routes/backend.php'));
        }
    )
    ->withBroadcasting(
        __DIR__ . '/../routes/channels.php',
        ['prefix' => 'api', 'middleware' => ['auth:api']],
    )
    ->withMiddleware(function (Middleware $middleware) {

        $middleware->appendToGroup('web', [
            //  CorsMiddleware::class, // for CORS

            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,

            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ]);
        $middleware->appendToGroup('api', [
            \Illuminate\Http\Middleware\HandleCors::class, // Laravel’s built-in CORS
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ]);
        $middleware->alias([
            'authCheck'     => App\Http\Middleware\AuthCheckMiddleware::class,
            'role'          => App\Http\Middleware\RoleMiddleware::class,
            'admin'         => App\Http\Middleware\AdminMiddleware::class,
            'checkItemUpload' => \App\Http\Middleware\CheckItemUploadLimitMiddleware::class,
            'checkImageUpload' => \App\Http\Middleware\CheckImageUploadLimitMiddleware::class,
            'checkChatting' => \App\Http\Middleware\CheckChattingLimitMiddleware::class

        ]);

        $middleware->validateCsrfTokens(except: [
            '/stripe/webhook',
            'api/*',
            'http://localhost:3000',
            'https://chique-dev.netlify.app',
            'https://aichique.com'
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
