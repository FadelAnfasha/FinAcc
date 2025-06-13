<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RequestForServiceController;
use Inertia\Inertia;

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

Route::get('/rfs', [RequestForServiceController::class, 'index'])->name('rfs.index');


#==========================
#======= BOM Route ========
#==========================


require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';
