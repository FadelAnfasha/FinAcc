<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RequestForServiceController;
use Inertia\Inertia;
use App\Http\Controllers\BOMController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\ProcessCostController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\BusinessPartnerController;

#===========================
#======  Main Route  =======
#===========================
Route::get('/', function (Request $request) {
    // Jika user sudah login, arahkan ke dashboard
    if (Auth::check()) {
        return redirect()->route('dashboard');
    }

    // Jika belum login, tampilkan halaman login
    return Inertia::render('auth/Login', [
        'canResetPassword' => Route::has('password.request'),
        'status' => $request->session()->get('status'),
    ]);
})->name('home');

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');


#==========================
#======= RFS Route ========
#==========================
Route::middleware(['auth', 'verified'])->group(function () {

    // RFS Routes
    Route::resource('rfs', RequestForServiceController::class);


    Route::post('/rfs/{id}/accept', [RequestForServiceController::class, 'accept'])
        ->middleware('role:Superior');

    Route::post('/rfs/{id}/reject', [RequestForServiceController::class, 'reject'])
        ->middleware('role:Superior');

    Route::post('/rfs/{id}/execute', [RequestForServiceController::class, 'execute'])
        ->middleware('role:Admin');

    Route::post('/rfs/{id}/finish', [RequestForServiceController::class, 'finish'])
        ->middleware('role:Admin');
});


#==========================
#======= BOM Route ========
#==========================
Route::get('/bom', [BOMController::class, 'index'])->name('bom.index');


#==========================
#======== PC Route ========
#==========================

Route::middleware(['auth', 'verified'])->group(function () {
    // Main Process Cost page
    // Route::get('/pc', [ProcessCostController::class, 'index'])
    //     ->name('pc.index');

    // Master Data page
    Route::get('/pc/master', [ProcessCostController::class, 'master'])
        ->name('pc.master');


    // Report page
    Route::get('/pc/report', [ProcessCostController::class, 'report'])
        ->name('pc.report');
});

#=============================
#====> Business Partner <=====
#=============================

Route::middleware(['auth', 'verified'])->group(function () {
    Route::post('/bps/import', [BusinessPartnerController::class, 'import'])->name('bps.import');
});


#==========================
#====== Admin Route =======
#==========================
Route::middleware(['auth', 'verified', 'role:Admin'])->group(function () {

    // Admin Panel Main Route
    Route::get('/admin', [AdminController::class, 'index'])
        ->name('admin.index');

    // Role Management Routes
    Route::prefix('admin/roles')->name('admin.roles.')->group(function () {
        Route::post('/', [AdminController::class, 'storeRole'])->name('store');
        Route::put('/{role}', [AdminController::class, 'updateRole'])->name('update');
        Route::delete('/{role}', [AdminController::class, 'destroyRole'])->name('destroy');
        Route::get('/list', [AdminController::class, 'getRoles'])->name('list');
    });

    // Permission Management Routes
    Route::prefix('admin/permissions')->name('admin.permissions.')->group(function () {
        Route::post('/', [AdminController::class, 'storePermission'])->name('store');
        Route::put('/{permission}', [AdminController::class, 'updatePermission'])->name('update');
        Route::delete('/{permission}', [AdminController::class, 'destroyPermission'])->name('destroy');
        Route::get('/list', [AdminController::class, 'getPermissions'])->name('list');
    });

    // User Role Assignment Routes
    Route::prefix('admin/users')->name('admin.users.')->group(function () {
        Route::post('{selected_user}/assign-role', [AdminController::class, 'assignRole'])->name('assign-role');
        Route::post('/remove-role', [AdminController::class, 'removeRole'])->name('remove-role');
        Route::get('/list', [AdminController::class, 'getUsers'])->name('list');
    });

    // Register new user by Admin
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::post('register', [RegisteredUserController::class, 'store'])->name('register');
    });
});

require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';
