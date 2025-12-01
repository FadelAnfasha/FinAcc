<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use Carbon\Carbon;

use App\Models\ActualCost;
use App\Models\StandardCost;
use App\Models\DifferenceCost;
use App\Models\DiffCostXSalesQty;
use App\Traits\CostAnalysisTrait;

class DifferenceCostController extends Controller
{
    use CostAnalysisTrait;
    public function updateDifferenceCost(Request $request)
    {
        $validatedData = $request->validate([
            'standard_period' => 'required',
            'actual_period' => 'required',
        ]);
        $standardCost = StandardCost::where('period', $validatedData['standard_period'])
            ->get();

        $actualCost = ActualCost::where('period', $validatedData['actual_period'])
            ->get();

        $differenceCosts = [];
        $processedItemCodes = [];

        $currentYear = strlen($validatedData['actual_period']) > 4
            ? substr($validatedData['actual_period'], -4)
            : $validatedData['actual_period'];


        foreach ($standardCost as $sc) {
            $ac = $actualCost->firstWhere('item_code', $sc->item_code);
            $finalRemark = 'Normal';

            // Dapatkan harga komponen (menggunakan ?? 0 untuk keamanan)
            $sc_disc_price = $sc->disc_price ?? 0;
            $ac_disc_price = $ac->disc_price ?? 0;
            $sc_rim_price = $sc->rim_price ?? 0;
            $ac_rim_price = $ac->rim_price ?? 0;
            $sc_sidering_price = $sc->sidering_price ?? 0;
            $ac_sidering_price = $ac->sidering_price ?? 0;

            $componentMapping = [
                'disc' => ['std' => $sc_disc_price, 'act' => $ac_disc_price],
                'rim' => ['std' => $sc_rim_price, 'act' => $ac_rim_price],
                'sidering' => ['std' => $sc_sidering_price, 'act' => $ac_sidering_price],
            ];

            if ($ac) {
                foreach ($componentMapping as $name => $data) {
                    if ($data['std'] > 0 && $data['act'] == 0) {
                        $finalRemark = "No Budget";
                        break;
                    }
                }

                if (str_starts_with($finalRemark, 'Normal')) {
                    foreach ($componentMapping as $name => $data) {
                        if ($data['std'] == 0 && $data['act'] > 0) {
                            $previousSCPeriod = $this->getStandardCostHistoryPeriod($sc->item_code, $currentYear);
                            if ($previousSCPeriod) {
                                $finalRemark = "Using previous standard cost {$previousSCPeriod}";
                            } else {
                                $finalRemark = "New Product";
                            }
                            break;
                        }
                    }
                }

                $diff_raw_material = $sc->total_raw_material - $ac->total_raw_material;
                $diff_process = $sc->total_process - $ac->total_process;
                $diff_total = $diff_raw_material + $diff_process;

                $diff_disc = $sc_disc_price - $ac_disc_price;
                $diff_rim = $sc_rim_price - $ac_rim_price;
                $diff_sidering = $sc_sidering_price - $ac_sidering_price;

                $differenceCosts[] = [
                    'item_code' => $sc->item_code,
                    'period' => $validatedData['actual_period'],

                    'total_raw_material' => $diff_raw_material,
                    'total_process' => $diff_process,
                    'total' => $diff_total,

                    'diff_disc' => $diff_disc,
                    'diff_rim' => $diff_rim,
                    'diff_sidering' => $diff_sidering,

                    'remark' => $finalRemark,

                    'created_at' => now(),
                    'updated_at' => now(),
                ];
                $processedItemCodes[] = $sc->item_code;
            } else {
                foreach ($componentMapping as $name => $data) {
                    if ($data['std'] > 0) {
                        $finalRemark = "No Budget";
                        break;
                    }
                }

                $differenceCosts[] = [
                    'item_code' => $sc->item_code,
                    'period' => $validatedData['actual_period'],

                    'total_raw_material' => $sc->total_raw_material,
                    'total_process' => $sc->total_process,
                    'total' => $sc->total_raw_material + $sc->total_process,

                    'diff_disc' => $sc_disc_price,
                    'diff_rim' => $sc_rim_price,
                    'diff_sidering' => $sc_sidering_price,

                    'remark' => $finalRemark,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        $onlyActualCost = $actualCost->whereNotIn('item_code', $processedItemCodes);

        foreach ($onlyActualCost as $ac) {
            $finalRemark = 'Normal';

            $diff_raw_material = 0 - $ac->total_raw_material;
            $diff_process = 0 - $ac->total_process;
            $diff_total = $diff_raw_material + $diff_process;

            $ac_disc_price = $ac->disc_price ?? 0;
            $ac_rim_price = $ac->rim_price ?? 0;
            $ac_sidering_price = $ac->sidering_price ?? 0;

            $componentMapping = [
                'disc' => ['std' => 0, 'act' => $ac_disc_price],
                'rim' => ['std' => 0, 'act' => $ac_rim_price],
                'sidering' => ['std' => 0, 'act' => $ac_sidering_price],
            ];

            foreach ($componentMapping as $name => $data) {
                if ($data['std'] == 0 && $data['act'] > 0) {
                    $previousSCPeriod = $this->getStandardCostHistoryPeriod($ac->item_code, $currentYear);
                    if ($previousSCPeriod) {
                        $finalRemark = "Using previous standard cost {$previousSCPeriod}";
                    } else {
                        $finalRemark = "New Product";
                    }
                    break;
                }
            }

            $differenceCosts[] = [
                'item_code' => $ac->item_code,
                'period' => $validatedData['actual_period'],

                'total_raw_material' => $diff_raw_material,
                'total_process' => $diff_process,
                'total' => $diff_total,

                'diff_disc' => 0 - $ac_disc_price,
                'diff_rim' => 0 - $ac_rim_price,
                'diff_sidering' => 0 - $ac_sidering_price,

                'remark' => $finalRemark,

                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        DifferenceCost::upsert($differenceCosts, ['item_code', 'period'], [
            'total_raw_material',
            'total_process',
            'total',
            'diff_disc',
            'diff_rim',
            'diff_sidering',
            'remark'
        ]);

        return redirect()->route('dc.report');
    }

    public function updateDCxSQ(Request $request)
    {
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
