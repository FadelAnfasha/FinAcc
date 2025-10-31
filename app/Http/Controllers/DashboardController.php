<?php

namespace App\Http\Controllers;

use App\Models\ActualCost;
use App\Models\ActualSalesQuantity;
use App\Models\DiffCostXSalesQty;
use Inertia\Inertia;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $lastMonth = Carbon::now()->subMonthNoOverflow()->format('M');
        $Month = Carbon::now()->subMonth()->format('F');
        $lastYear = Carbon::now()->format('Y');
        $columnTopQuantity = strtolower($lastMonth) . '_qty';

        $topActualCost = ActualCost::orderBy('total', 'desc')
            ->where('period', 'like', '%' . $lastMonth . '%')
            ->limit(5)
            ->select('item_code', 'total', 'period')
            ->get();

        $topQuantity = ActualSalesQuantity::orderBy($columnTopQuantity, 'desc')
            ->limit(5)
            ->select('item_code', $columnTopQuantity . ' as quantity')
            ->get();

        $topDifferenceCost = DiffCostXSalesQty::orderBy('total', 'asc')
            ->where('period', 'like',  $lastMonth . '%')
            ->limit(5)
            ->select('item_code', 'quantity', 'total', 'period')
            ->get();

        return Inertia::render('Dashboard', [
            'topActualCost' => $topActualCost,
            'topQuantity' => $topQuantity,
            'topDifferenceCost' => $topDifferenceCost,
            'lastMonth' => $Month . "'" . $lastYear,
        ]);
    }
}
