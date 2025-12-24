<?php


use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Request;
use Inertia\Inertia;

use App\Http\Controllers\ActualBillOfMaterialController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\ActualCostController;
use App\Http\Controllers\ActualMaterialController;
use App\Http\Controllers\ActualSalesQuantityController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\BillOfMaterialController;
use App\Http\Controllers\BusinessPartnerController;
use App\Http\Controllers\CycleTimeController;
use App\Http\Controllers\DifferenceCostController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\ProcessCostController;
use App\Http\Controllers\RequestForServiceController;
use App\Http\Controllers\SalesQuantityController;
use App\Http\Controllers\StandardBillOfMaterialController;
use App\Http\Controllers\StandardConsumableController;
use App\Http\Controllers\StandardCostController;
use App\Http\Controllers\standardMaterialController;
use App\Http\Controllers\WagesDistributionController;


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

    Route::get('/ac/report', [MenuController::class, 'Actual_Report'])
        ->name('ac.report');

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
    Route::delete('/sq/destroy/{id}', [SalesQuantityController::class, 'destroy'])->name('sq.destroy');
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


#==========================
#====== Only Standard =====
#==========================
Route::middleware(['auth', 'verified'])->group(function () {
    #==========================
    #==== Standard Master =====
    #==========================
    Route::get('/api/standard/get-material', [StandardMaterialController::class, 'getPaginated'])->name('master.standard.paginated.material');
    Route::get('/api/standard/list-material-group', [StandardMaterialController::class, 'getGroupList'])->name('master.standard.list.material-group');
    Route::get('/api/standard/get-all-material', [StandardMaterialController::class, 'getAllMaterial'])->name('master.standard.get.all.material');

    Route::post('/standard/import-material', [StandardMaterialController::class, 'import'])->middleware('permission:Update_MasterData')->name('master.standard.import.material');
    Route::put('/standard/update-material/{id}', [StandardMaterialController::class, 'update'])->name('master.standard.update.material');
    Route::delete('/standard/destroy-material/{id}', [StandardMaterialController::class, 'destroy'])->name('master.standard.destroy.material');
    Route::get('/standard/import-material-progress', function () {
        return response()->json([
            'progress' => Cache::get('import-standard-material-progress', 0),
        ]);
    });

    #==========================
    #==== Standard BOM =====
    #==========================
    Route::get('/api/standard/get-bom', [StandardBillOfMaterialController::class, 'getPaginated'])->name('master.standard.paginated.bom');
    Route::get('/api/standard/get-component', [StandardBillOfMaterialController::class, 'getComponent'])->name('master.standard.component.bom');
    Route::get('/api/standard/get-structured-bom', [StandardBillOfMaterialController::class, 'getStructuredBOM'])->name('master.standard.structured.bom');

    Route::post('/standard/import-bom', [StandardBillOfMaterialController::class, 'import'])
        ->middleware('permission:Update_MasterData')
        ->name('master.standard.import.bom');
    Route::get('/standard/import-bom-progress', function () {
        return response()->json([
            'progress' => Cache::get('import-standard-bom-progress', 0),
        ]);
    });

    #==========================
    #== Standard Consumables ==
    #==========================

    Route::get('/api/standard/get-consumable', [StandardConsumableController::class, 'getPaginated'])->name('master.standard.consumables.paginated');

    Route::middleware(['auth', 'verified'])->group(function () {
        Route::post('/standard/import-consumable', [StandardConsumableController::class, 'import'])->middleware('permission:Update_MasterData')->name('master.standard.import.consumable');
        Route::put('/standard/update-consumable/{id}', [StandardConsumableController::class, 'update'])->name('master.standard.update.consumable');
        Route::delete('/consumable/destroy-consumable/{id}', [StandardConsumableController::class, 'destroy'])->name('master.standard.destroy.consumable');
        Route::get('/standard/import-consumable-progress', function () {
            return response()->json([
                'progress' => Cache::get('import-standard-consumable-progress', 0),
            ]);
        });
    });

    #==========================
    #==== Standard Report =====
    #==========================
    Route::get('/api/standard/get-std-cost', [StandardCostController::class, 'getPaginated'])->name('report.standard.paginatedStandardCost');
    Route::get('/api/standard/list-period', [StandardCostController::class, 'getPeriod'])->name('report.standard.listPeriod');
    Route::get('/api/standard/list-type', [StandardCostController::class, 'getType'])->name('report.standard.listType');
    Route::get('/api/standard/export-report', [StandardCostController::class, 'getExport'])->name('report.standard.export');

    Route::post('/standard/update-report/SC', [StandardCostController::class, 'update'])->middleware('permission:Update_Report')->name('report.standard.update');
    Route::get('/standard/preview-report/{item_code}', action: [StandardCostController::class, 'preview'])->name('report.standard.preview');
});

#==========================
#====== Only Actual =======
#==========================
Route::middleware(['auth', 'verified'])->group(function () {
    #==========================
    #===== Actual Master ======
    #==========================
    Route::get('/api/actual/get-material', [ActualMaterialController::class, 'getPaginated'])->name('master.actual.paginated.material');
    Route::get('/api/actual/list-material-period', [ActualMaterialController::class, 'getPeriod'])->name('master.actual.list.material-period');

    Route::post('/actual/import-material', [ActualMaterialController::class, 'import'])->middleware('permission:Update_MasterData')->name('master.actual.import.material');
    Route::put('/actual/update-material/{id}', [ActualMaterialController::class, 'update'])->name('master.actual.update.material');
    Route::delete('/actual/destroy-material/{id}', [ActualMaterialController::class, 'destroy'])->name('master.actual.destroy.material');
    Route::get('/actual/import-material-progress', function () {
        return response()->json([
            'progress' => Cache::get('import-actual-material-progress', 0),
        ]);
    });

    #=============================
    #====== Actual SalesQty ======
    #=============================
    Route::get('/api/actual/get-salesqty', [ActualSalesQuantityController::class, 'getPaginated'])->name('master.actual.paginated.material');
    Route::get('/api/actual/list-sales-quantity-month', [actualSalesQuantityController::class, 'getSalesMonth'])->name('master.actual.list.sales-quantity.month');

    Route::post('/actual/import-sales-quantity', [actualSalesQuantityController::class, 'import'])->middleware('permission:Update_MasterData')->name('master.actual.import.sales-quantity');
    Route::put('/actual/update-sales-quantity/{id}', [actualSalesQuantityController::class, 'update'])->name('master.actaul.update.sales-quantity');
    Route::delete('/actual/destroy-sales-quantity/{id}', [actualSalesQuantityController::class, 'destroy'])->name('master.actual.destroy.sales-quantity');
    Route::get('/actual/import-sales-quantity-progress', function () {
        return response()->json([
            'progress' => Cache::get('import-actual-sales-quantity-progress', 0),
        ]);
    });

    #==========================
    #==== Actual BOM =====
    #==========================
    Route::get('/api/actual/get-bom', [ActualBillOfMaterialController::class, 'getPaginated'])->name('master.actual.paginated.bom');
    Route::get('/api/actual/get-component', [ActualBillOfMaterialController::class, 'getComponent'])->name('master.actual.component.bom');

    Route::post('/actual-bom/import', [ActualBillOfMaterialController::class, 'import'])
        ->middleware('permission:Update_MasterData')
        ->name('master.actual.import.bom');
    Route::get('/actual/import-bom-progress', function () {
        return response()->json([
            'progress' => Cache::get('import-actual-bom-progress', 0),
        ]);
    });

    #==========================
    #===== Actual Report ======
    #==========================
    Route::get('/api/actual/get-act-cost', [ActualCostController::class, 'getPaginated'])->name('report.actual.paginatedStandardCost');
    Route::get('/api/actual/list-period', [ActualCostController::class, 'getPeriod'])->name('report.actual.listPeriod');
    Route::get('/api/actual/list-type', [ActualCostController::class, 'getType'])->name('report.actual.listType');
    Route::get('/api/actual/export-report', [ActualCostController::class, 'getExport'])->name('report.actual.export');


    Route::post('/actual/update-report/SC', [ActualCostController::class, 'update'])->middleware('permission:Update_Report')->name('report.actual.update');
    // Route::get('/actual/preview-report/{item_code}', action: [ActualCostController::class, 'preview'])->name('report.actual.preview');
});

#=============================
#====== Difference Cost ======
#=============================

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/api/difference/get-difference-cost', [DifferenceCostController::class, 'getPaginated'])->name('report.difference.paginated');
    Route::get('/api/difference/list-period', [DifferenceCostController::class, 'getPeriod'])->name('report.difference.period');
    Route::get('/api/difference/list-remark', [DifferenceCostController::class, 'getRemark'])->name('report.difference.remark');
    Route::get('/api/difference/get-total', [DifferenceCostController::class, 'getTotal'])->name('report.difference.total');
    Route::get('/api/difference/export-report', [DifferenceCostController::class, 'getExport'])->name('report.difference.export');



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
