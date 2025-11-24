<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use Carbon\Carbon;

use App\Models\ActualCost;
use App\Models\StandardCost;
use App\Models\DifferenceCost;
use App\Models\DiffCostXSalesQty;

class DifferenceCostController extends Controller
{
    public function updateDifferenceCost(Request $request)
    {
        $validatedData = $request->validate([
            'standard_period' => 'required',
            'actual_period' => 'required',
        ]);

        $standardCost = StandardCost::where('period', $validatedData['standard_period'])
            // ->take(35)]
            // ->where('item_code', 'F16N01')
            ->get();

        // dd($standardCost);

        $actualCost = ActualCost::where('period', $validatedData['actual_period'])
            // ->take(35)
            // ->where('item_code', 'F16N01')
            ->get();

        // dd($actualCost);

        $differenceCosts = [];
        $processedItemCodes = [];

        foreach ($standardCost as $sc) {
            $ac = $actualCost->firstWhere('item_code', $sc->item_code);

            if ($ac) {
                $differenceCosts[] = [
                    'item_code' => $sc->item_code,
                    'period' => $validatedData['actual_period'],
                    'total_raw_material' => $sc->total_raw_material - $ac->total_raw_material,
                    'total_process' => $sc->total_process - $ac->total_process,
                    'total' => $sc->total - $ac->total,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
                $processedItemCodes[] = $sc->item_code;
            } else {
                $differenceCosts[] = [
                    'item_code' => $sc->item_code,
                    'period' => $validatedData['actual_period'],
                    'total_raw_material' => $sc->total_raw_material,
                    'total_process' => $sc->total_process,
                    'total' => $sc->total,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        $onlyActualCost = $actualCost->whereNotIn('item_code', $processedItemCodes);

        foreach ($onlyActualCost as $ac) {
            $differenceCosts[] = [
                'item_code' => $ac->item_code,
                'period' => $validatedData['actual_period'],
                'total_raw_material' => 0 - $ac->total_raw_material,
                'total_process' => 0 - $ac->total_process,
                'total' => 0 - $ac->total,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // dd(vars: $differenceCosts);

        // Simpan ke database menggunakan upsert
        DifferenceCost::upsert($differenceCosts, ['item_code', 'period'], ['total_raw_material', 'total_process', 'total']);
        return redirect()->route('dc.report');
    }

    public function updateDCxSQ(Request $request)
    {
        // dd($request->all());
        $now = Carbon::now();
        $validatedData = $request->validate([
            'period' => 'required',
            'sales_period' => 'required',
        ]);

        $salesPeriodColumn = $validatedData['sales_period'];

        $dc = DifferenceCost::select(
            'difference_cost.item_code',
            'difference_cost.total_raw_material',
            'difference_cost.total_process',
            'difference_cost.total',
            DB::raw("COALESCE(acs.{$salesPeriodColumn}, 0) as month_qty")
        )
            ->where('difference_cost.period', $validatedData['period'])
            ->leftJoin('actual_salesquantities as acs', 'difference_cost.item_code', '=', 'acs.item_code')
            ->get();

        $salesPeriodTemp = str_replace('_qty', '', $salesPeriodColumn);
        $salesMonth = ucfirst($salesPeriodTemp);
        $period = $validatedData['period'] . ' / ' . $salesMonth;

        $dataToInsert = $dc->map(function ($item) use ($validatedData, $salesMonth, $period, $now) {
            $data = $item->toArray();
            $qty = $data['month_qty'];

            $data['total_raw_material'] = $data['total_raw_material'] * $qty;
            $data['total_process']      = $data['total_process'] * $qty;
            $data['total']              = $data['total'] * $qty;

            // Tambahkan kolom KUNCI UNIK dan data lain
            $data['period'] = $period;
            $data['quantity'] = $qty;
            $data['created_at'] = $now;
            $data['updated_at'] = $now;

            unset($data['month_qty']);
            return $data;
        })->toArray();

        if (!empty($dataToInsert)) {
            $uniqueKeys = ['item_code', 'period', 'sales_month'];
            $updateColumns = ['total_raw_material', 'total_process', 'total', 'quantity', 'updated_at'];

            DiffCostXSalesQty::upsert(
                $dataToInsert,
                $uniqueKeys,
                $updateColumns
            );
        }

        return back()->with('status', 'DCxSQ report updated successfully!');
    }
}
