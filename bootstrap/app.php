<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->validateCsrfTokens(except: [
            'cmspage/update',
        ]);
        $middleware->validateCsrfTokens(except: [
            'pages/update/*',
        ]);
        $middleware->validateCsrfTokens(except: [
            'categories/*',
        ]);
        $middleware->validateCsrfTokens(except: [
            'categories',
        ]);
        $middleware->validateCsrfTokens(except: [
            'articles/*',
        ]);
        $middleware->validateCsrfTokens(except: [
            'image/upload',
        ]);
        $middleware->validateCsrfTokens(except: [
            'send/email',
        ]);
        $middleware->validateCsrfTokens(except: [
            'pages/*',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
