<?php

use App\Http\Controllers\Auth\RegisteredUserController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Request;
use Inertia\Inertia;

use App\Models\SalesQuantity;
use App\Http\Controllers\ActualCostController;
use App\Http\Controllers\actualMaterialController;
use App\Http\Controllers\actualSalesQuantityController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\BillOfMaterialController;
use App\Http\Controllers\BusinessPartnerController;
use App\Http\Controllers\CycleTimeController;
use App\Http\Controllers\DifferenceCostController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\ProcessCostController;
use App\Http\Controllers\RequestForServiceController;
use App\Http\Controllers\SalesQuantityController;
use App\Http\Controllers\standardMaterialController;
use App\Http\Controllers\StandardCostController;
use App\Http\Controllers\ValveController;
use App\Http\Controllers\WagesDistributionController;
use PhpOffice\PhpSpreadsheet\Calculation\DateTimeExcel\Difference;

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

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [MenuController::class, 'Dashboard'])
        ->name('dashboard');

    Route::get('/rfs', [MenuController::class, 'RFS'])
        ->name('rfs.index');

    Route::get('/admin', [MenuController::class, 'Admin'])
        ->name('admin.index');

    Route::get('/pc/master', [MenuController::class, 'ProcessCost_Master'])
        // ->middleware('role:Process Cost - Full Access')
        ->name('pc.master');

    Route::get('/bom/master', [MenuController::class, 'BOM_Master'])
        ->name('bom.master');

    Route::get('/sc/masterStandard', [MenuController::class, 'Standard_Master'])
        ->name('sc.master');

    Route::get('/ac/masterActual', [MenuController::class, 'Actual_Master'])
        ->name('ac.master');

    Route::get('/pc/report', [MenuController::class, 'ProcessCost_Report'])
        // ->middleware(['role:Process Cost - View|Process Cost - Full Access'])
        ->name('pc.report');

    Route::get('/bom/report', [MenuController::class, 'BOM_Report'])
        ->name('bom.report');

    Route::get('/sc/report', [MenuController::class, 'Standard_Report'])
        ->name('sc.report');

    Route::get('/dc/report', [MenuController::class, 'DiffCost_Report'])
        ->name('dc.report');
});

#==========================
#======= RFS Route ========
#==========================
Route::middleware(['auth', 'verified'])->group(function () {
    Route::post('/rfs/store', [RequestForServiceController::class, 'store'])
        ->middleware('permission:CreateRequest');

    Route::post('/rfs/{id}/accept', [RequestForServiceController::class, 'accept'])
        ->middleware('permission:Approve');

    Route::post('/rfs/{id}/reject', [RequestForServiceController::class, 'reject'])
        ->middleware('permission:Reject');

    Route::post('/rfs/{id}/execute', [RequestForServiceController::class, 'execute'])
        ->middleware('permission:Execute');

    Route::post('/rfs/{id}/uat', [RequestForServiceController::class, 'user_accept'])
        ->middleware('permission:UserAcceptance');

    Route::post('/rfs/{id}/revision', [RequestForServiceController::class, 'revision'])
        ->middleware('permission:Revision');

    Route::post('/rfs/{id}/finish', [RequestForServiceController::class, 'finish'])
        ->middleware('permission:Finish');
});

#==========================
#====== Admin Route =======
#==========================
Route::middleware(['auth', 'verified', 'role:Admin'])->group(function () {

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

#==========================
#======== PC Route ========
#==========================
Route::middleware(['auth', 'verified'])->group(function () {
    // Report page


    Route::post('/pc/update/CTxSQ', [ProcessCostController::class, 'updateCTxSQ'])->middleware('permission:Update_Report')->name('pc.updateCTxSQ');
    Route::post('/pc/update/BaseCost', [ProcessCostController::class, 'updateBaseCost'])->middleware('permission:Update_Report')->name('pc.updateBaseCost');
    Route::post('/pc/update/CPP', [ProcessCostController::class, 'updateCPP'])->middleware('permission:Update_Report')->name('pc.updateCPP');
    Route::post('/pc/update/PC', [ProcessCostController::class, 'updatePC'])->middleware('permission:Update_Report')->name('pc.updatePC');
});

#=============================
#====> Business Partner <=====
#=============================

Route::middleware(['auth', 'verified'])->group(function () {
    Route::post('/bp/import', [BusinessPartnerController::class, 'import'])->middleware('permission:Update_MasterData')->name('bp.import');
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
    Route::post('/ct/import', [CycleTimeController::class, 'import'])->middleware('permission:Update_MasterData')->name('ct.import');
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
    Route::post('/sq/import', [SalesQuantityController::class, 'import'])->middleware('permission:Update_MasterData')->name('sq.import');
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
    Route::post('/wd/import', [WagesDistributionController::class, 'import'])->middleware('permission:Update_MasterData')->name('wd.import');
    Route::put('/wd/update', [WagesDistributionController::class, 'update'])->name('wd.update');
    Route::get('/wd/import-progress', function () {
        return response()->json([
            'progress' => Cache::get('import-progress-wd', 0),
        ]);
    });
});

#=============================
#====> Bill of Materials <====
#=============================
Route::middleware(['auth', 'verified'])->group(function () {

    Route::post('/bom/import', [BillOfMaterialController::class, 'import'])
        ->middleware('permission:Update_MasterData')
        ->name('bom.import');

    Route::get('/bom/components/{id}', [BillOfMaterialController::class, 'components'])
        ->name('bom.components');

    Route::get('/bom/import-progress', function () {
        return response()->json([
            'progress' => Cache::get('import-progress-bom', 0),
        ]);
    });
});

#=============================
#=========> Valves <==========
#=============================

Route::middleware(['auth', 'verified'])->group(function () {
    Route::post('/valve/import', [ValveController::class, 'import'])->middleware('permission:Update_MasterData')->name('valve.import');
    Route::put('/valve/update/{id}', [ValveController::class, 'update'])->name('valve.update');
    Route::delete('/valve/destroy/{id}', [ValveController::class, 'destroy'])->name('valve.destroy');
    Route::get('/valve/import-progress', function () {
        return response()->json([
            'progress' => Cache::get('import-progress-valve', 0),
        ]);
    });
});

#==========================
#====== Only Standard =====
#==========================
Route::middleware(['auth', 'verified'])->group(function () {
    #==========================
    #==== Standard Master =====
    #==========================
    Route::post('/standardMat/import', [standardMaterialController::class, 'import'])->middleware('permission:Update_MasterData')->name('stamat.import');
    Route::put('/standardMat/update/{id}', [standardMaterialController::class, 'update'])->name('stamat.update');
    Route::delete('/standardMat/destroy/{id}', [standardMaterialController::class, 'destroy'])->name('stamat.destroy');
    Route::get('/standardMat/import-progress', function () {
        return response()->json([
            'progress' => Cache::get('import-progress-standardMat', 0),
        ]);
    });


    #==========================
    #==== Standard Report =====
    #==========================

    Route::post('/sc/update/SC', [StandardCostController::class, 'update'])->middleware('permission:Update_Report')->name('sc.update');
    Route::get('/sc/previewSC/{item_code}', action: [StandardCostController::class, 'preview'])->name('sc.preview');

    // Route::get('/sc/download/{item_code}', [BOMController::class, 'downloadReport'])->name('sc.download');

});

#==========================
#====== Only Actual =======
#==========================
Route::middleware(['auth', 'verified'])->group(function () {
    #==========================
    #===== Actual Master ======
    #==========================

    Route::post('/actualMat/import', [actualMaterialController::class, 'import'])->middleware('permission:Update_MasterData')->name('acmat.import');
    Route::put('/actualMat/update/{id}', [actualMaterialController::class, 'update'])->name('acmat.update');
    Route::delete('/actualMat/destroy/{id}', [actualMaterialController::class, 'destroy'])->name('acmat.destroy');
    Route::get('/actualMat/import-progress', function () {
        return response()->json([
            'progress' => Cache::get('import-progress-actualMat', 0),
        ]);
    });

    #==========================
    #===== Actual Report ======
    #==========================
    Route::get('/ac/report', [MenuController::class, 'reportActual'])
        ->name('ac.report');
    Route::post('/ac/update/AC', [ActualCostController::class, 'update'])->middleware('permission:Update_Report')->name('ac.update');
    Route::get('/ac/previewSC/{item_code}', action: [ActualCostController::class, 'preview'])->name('ac.preview');
    // Route::post('/sc/update/DC', [BOMController::class, 'updateDifferenceCost'])->middleware('permission:Update_Report')->name('sc.updateDC');
    // Route::post('/sc/update/DCxSQ', [BOMController::class, 'updateDCxSQ'])->middleware('permission:Update_Report')->name('sc.updateDCxSQ');

    #=============================
    #====== Actual SalesQty ======
    #=============================
    Route::post('/acsqty/import', [actualSalesQuantityController::class, 'import'])->middleware('permission:Update_MasterData')->name('acsqty.import');
    Route::put('/acsqty/update/{id}', [actualSalesQuantityController::class, 'update'])->name('acsqty.update');
    Route::delete('/acsqty/destroy/{id}', [actualSalesQuantityController::class, 'destroy'])->name('acsqty.destroy');
    Route::get('/acsqty/import-progress', function () {
        return response()->json([
            'progress' => Cache::get('import-progress-acsqty', 0),
        ]);
    });
});


#=============================
#====== Difference Cost ======
#=============================

Route::middleware(['auth', 'verified'])->group(function () {
    Route::post('/dc/update/DC', [DifferenceCostController::class, 'updateDifferenceCost'])->middleware('permission:Update_Report')->name('dc.updateDC');
    Route::post('/dc/update/DCxSQ', [DifferenceCostController::class, 'updateDCxSQ'])->middleware('permission:Update_Report')->name('dc.updateDCxSQ');
});


#=============================
#====== Production Cost ======
#=============================

Route::middleware(['auth', 'verified'])->group(function () {
    Route::post('/prodcost/report', [DifferenceCostController::class, 'updateDifferenceCost'])->middleware('permission:Update_Report')->name('prodc.report');
});


require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';
