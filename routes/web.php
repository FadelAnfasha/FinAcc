<?php

use App\Http\Controllers\MaterialController;
use App\Http\Controllers\SalesQuantityController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Cache;
use App\Http\Controllers\RequestForServiceController;
use Inertia\Inertia;
use App\Http\Controllers\BOMController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\ProcessCostController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\BillOfMaterialController;
use App\Http\Controllers\BusinessPartnerController;
use App\Http\Controllers\CycleTimeController;
use App\Http\Controllers\PackingController;
use App\Http\Controllers\ProcessController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\WagesDistributionController;
use App\Models\SalesQuantity;

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
        ->middleware('role:Admin');

    Route::post('/rfs/{id}/reject', [RequestForServiceController::class, 'reject'])
        ->middleware('role:Admin');

    Route::post('/rfs/{id}/execute', [RequestForServiceController::class, 'execute'])
        ->middleware('role:Admin');

    Route::post('/rfs/{id}/finish', [RequestForServiceController::class, 'finish'])
        ->middleware('role:Admin');
});


#==========================
#======= BOM Route ========
#==========================
Route::middleware(['auth', 'verified'])->group(function () {
    // Main Process Cost page
    // Route::get('/pc', [ProcessCostController::class, 'index'])
    //     ->name('pc.index');

    // Master Data page
    Route::get('/bom/master', [BOMController::class, 'master'])
        ->name('bom.master');


    // Report page
    Route::get('/bom/report', [BOMController::class, 'report'])
        ->name('bom.report');
});

#=============================
#=====> Materials Route <=====
#=============================

Route::middleware(['auth', 'verified'])->group(function () {
    Route::post('/mat/import', [MaterialController::class, 'import'])->name('mat.import');
    Route::put('/mat/update/{id}', [MaterialController::class, 'update'])->name('mat.update');
    Route::delete('/mat/destroy/{id}', [MaterialController::class, 'destroy'])->name('mat.destroy');
    Route::get('/mat/import-progress', function () {
        return response()->json([
            'progress' => Cache::get('import-progress-mat', 0),
        ]);
    });
});

#=============================
#========> Packings <=========
#=============================

Route::middleware(['auth', 'verified'])->group(function () {
    Route::post('/pack/import', [PackingController::class, 'import'])->name('pack.import');
    Route::put('/pack/update/{id}', [PackingController::class, 'update'])->name('pack.update');
    Route::delete('/pack/destroy/{id}', [PackingController::class, 'destroy'])->name('pack.destroy');
    Route::get('/pack/import-progress', function () {
        return response()->json([
            'progress' => Cache::get('import-progress-pack', 0),
        ]);
    });
});

#=============================
#=======> Processes <=========
#=============================

Route::middleware(['auth', 'verified'])->group(function () {
    Route::post('/proc/import', [ProcessController::class, 'import'])->name('proc.import');
    Route::put('/proc/update/{id}', [ProcessController::class, 'update'])->name('proc.update');
    Route::delete('/proc/destroy/{id}', [ProcessController::class, 'destroy'])->name('proc.destroy');
    Route::get('/proc/import-progress', function () {
        return response()->json([
            'progress' => Cache::get('import-progress-proc', 0),
        ]);
    });
});

#=============================
#====> Bill of Materials <====
#=============================

Route::middleware(['auth', 'verified'])->group(function () {
    Route::post('/bom/import', [BillOfMaterialController::class, 'import'])->name('bom.import');

    Route::get('/bom/components/{id}', [BillOfMaterialController::class, 'components'])
        ->name('bom.components');

    Route::get('/bom/import-progress', function () {
        return response()->json([
            'progress' => Cache::get('import-progress-bom', 0),
        ]);
    });
});

#==========================
#======== PC Route ========
#==========================

Route::middleware(['auth', 'verified'])->group(function () {
    // Master Data page
    Route::get('/pc/master', [ProcessCostController::class, 'master'])
        ->name('pc.master');


    // Report page
    Route::get('/pc/report', [ProcessCostController::class, 'report'])
        ->name('pc.report');

    Route::post('pc/update/CTxSQ', [ProcessCostController::class, 'updateCTxSQ'])->name('pc.updateCTxSQ');
    Route::post('pc/update/BaseCost', [ProcessCostController::class, 'updateBaseCost'])->name('pc.updateBaseCost');
    Route::post('pc/update/CPP', [ProcessCostController::class, 'updateCPP'])->name('pc.updateCPP');
});

#=============================
#====> Business Partner <=====
#=============================

Route::middleware(['auth', 'verified'])->group(function () {
    Route::post('/bp/import', [BusinessPartnerController::class, 'import'])->name('bp.import');
    Route::post('/bp/store', [BusinessPartnerController::class, 'store'])->name('bp.store');
    Route::get('/bp/import-progress', function () {
        return response()->json([
            'progress' => Cache::get('import-progress-bp', 0),
        ]);
    });
});


#=============================
#=======> Cycle Time <========
#=============================

Route::middleware(['auth', 'verified'])->group(function () {
    Route::post('/ct/import', [CycleTimeController::class, 'import'])->name('ct.import');
    Route::get('/ct/import-progress', function () {
        return response()->json([
            'progress' => Cache::get('import-progress-ct', 0),
        ]);
    });
});

#=============================
#=====> Sales Quantity <======
#=============================

Route::middleware(['auth', 'verified'])->group(function () {
    Route::post('/sq/import', [SalesQuantityController::class, 'import'])->name('sq.import');
    Route::put('/sq/update/{id}', [SalesQuantityController::class, 'update'])->name('sq.update');
    Route::delete('/sq/destroy/{id}', [SalesQuantity::class, 'destroy'])->name('sq.destroy');
    Route::get('/sq/import-progress', function () {
        return response()->json([
            'progress' => Cache::get('import-progress-sq', 0),
        ]);
    });
});

#=============================
#===> Wages Distribution <====
#=============================

Route::middleware(['auth', 'verified'])->group(function () {
    Route::post('/wd/import', [WagesDistributionController::class, 'import'])->name('wd.import');
    Route::put('/wd/update', [WagesDistributionController::class, 'update'])->name('wd.update');
    Route::get('/wd/import-progress', function () {
        return response()->json([
            'progress' => Cache::get('import-progress-wd', 0),
        ]);
    });
});

#==========================
#====== Admin Route =======
#==========================
Route::middleware(['auth', 'verified', 'role:Superior|Admin'])->group(function () {

    // Admin Panel Main Route
    Route::get('/admin', [AdminController::class, 'index'])
        ->name('admin.index');

    // Role Management Routes
    Route::prefix('admin/roles')->middleware('role:Admin')->name('admin.roles.')->group(function () {
        Route::post('/', [AdminController::class, 'storeRole'])->name('store');
        Route::put('/{role}', [AdminController::class, 'updateRole'])->name('update');
        Route::delete('/{role}', [AdminController::class, 'destroyRole'])->name('destroy');
        Route::get('/list', [AdminController::class, 'getRoles'])->name('list');
    });

    // Permission Management Routes
    Route::prefix('admin/permissions')->middleware('role:Admin')->name('admin.permissions.')->group(function () {
        Route::post('/', [AdminController::class, 'storePermission'])->name('store');
        Route::put('/{permission}', [AdminController::class, 'updatePermission'])->name('update');
        Route::delete('/{permission}', [AdminController::class, 'destroyPermission'])->name('destroy');
        Route::get('/list', [AdminController::class, 'getPermissions'])->name('list');
    });

    // User Role Assignment Routes
    Route::prefix('admin/users')->middleware('role:Admin')->name('admin.users.')->group(function () {
        Route::post('{selected_user}/assign-role', [AdminController::class, 'assignRole'])->name('assign-role');
        Route::post('/remove-role', [AdminController::class, 'removeRole'])->name('remove-role');
        Route::get('/list', [AdminController::class, 'getUsers'])->name('list');
    });

    // Register new user by Admin
    Route::prefix('admin')->middleware('role:Admin')->name('admin.')->group(function () {
        Route::post('register', [RegisteredUserController::class, 'store'])->name('register');
    });
});

require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';
