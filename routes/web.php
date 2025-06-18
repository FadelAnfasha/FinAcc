<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RequestForServiceController;
use Inertia\Inertia;
use App\Http\Controllers\BOMController;


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

// routes/web.php


Route::middleware(['auth', 'verified'])->group(function () {

    // RFS Routes
    Route::resource('rfs', RequestForServiceController::class);


    Route::post('/rfs/{id}/accept', [RequestForServiceController::class, 'accept'])
    ->middleware('role:Reviewer');

    Route::post('/rfs/{id}/reject', [RequestForServiceController::class, 'reject'])
    ->middleware('role:Reviewer');  

    Route::post('/rfs/{id}/execute', [RequestForServiceController::class, 'execute'])
    ->middleware('role:Admin'); 
    Route::post('/rfs/{id}/finish', [RequestForServiceController::class, 'finish'])
    ->middleware('role:Admin'); 

    // Additional RFS routes
    Route::patch('rfs/{rfs}/status', [RequestForServiceController::class, 'updateStatus'])
        ->name('rfs.update-status');

    Route::get('rfs/{rfs}/download', [RequestForServiceController::class, 'downloadAttachment'])
        ->name('rfs.download');

    Route::get('rfs-statistics', [RequestForServiceController::class, 'statistics'])
        ->name('rfs.statistics');

});


#==========================
#======= BOM Route ========
#==========================
Route::get('/bom', [BOMController::class, 'index'])->name('bom.index');



require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';
