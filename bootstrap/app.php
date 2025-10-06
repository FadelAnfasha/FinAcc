<?php

use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\PermissionMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        $middleware->web(append: [
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        // Ganti app('router')->aliasMiddleware(...) dengan $middleware->alias([...])
        // Ini adalah cara yang direkomendasikan di Laravel 11/12 (jika Anda menggunakan versi tersebut)
        $middleware->alias([
            // Role sudah benar
            'role' => RoleMiddleware::class,

            // PERBAIKI: Arahkan 'permission' ke kelas PermissionMiddleware yang benar
            'permission' => PermissionMiddleware::class,

            // Anda mungkin ingin menambahkan ini juga
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);

        // Catatan: Jika Anda tetap ingin menggunakan sintaks app('router')->aliasMiddleware, 
        // pastikan baris ini ada:
        // app('router')->aliasMiddleware('permission', PermissionMiddleware::class); 

    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Konfigurasi exceptions di sini jika perlu
    })
    ->create();
