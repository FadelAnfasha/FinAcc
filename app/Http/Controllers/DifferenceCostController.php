<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

use Carbon\Carbon;

use App\Models\ActualCost;
use App\Models\StandardCost;
use App\Models\DifferenceCost;
use App\Models\DiffCostXSalesQty;
use App\Models\ActualSalesQuantity;
use App\Traits\CostAnalysisTrait;
use PhpOffice\PhpSpreadsheet\Calculation\DateTimeExcel\Difference;

class DifferenceCostController extends Controller
{
    use CostAnalysisTrait;
    protected function sortPeriods(Collection $periods): Collection
    {
        $monthOrder = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

        return $periods->sort(function ($a, $b) use ($monthOrder) {
            $isYtdA = str_starts_with($a, 'YTD-');
            $isYtdB = str_starts_with($b, 'YTD-');

            if ($isYtdA && !$isYtdB) return 1;
            if (!$isYtdA && $isYtdB) return -1;

            $monthA = substr(str_replace('YTD-', '', $a), 0, 3);
            $monthB = substr(str_replace('YTD-', '', $b), 0, 3);

            $indexA = array_search($monthA, $monthOrder);
            $indexB = array_search($monthB, $monthOrder);

            return $indexA <=> $indexB;
        })->values();
    }

    public function updateDifferenceCost(Request $request)
    {
        $validatedData = $request->validate([
            'standard_period' => 'required',
            'actual_period' => 'required',
        ]);

        $standardCost = StandardCost::where('period', $validatedData['standard_period'])->get();
        $actualCost = ActualCost::where('period', $validatedData['actual_period'])->get();
        $actualSalesQuantities = ActualSalesQuantity::all()->keyBy('item_code');

        $isYTD = str_starts_with($validatedData['actual_period'], 'YTD-');
        $cleanPeriod = str_replace('YTD-', '', $validatedData['actual_period']);
        $monthKey = substr($cleanPeriod, 0, 3);
        $displayPeriod = $validatedData['actual_period'] . ' / ' . $monthKey;

        $months = ['jan', 'feb', 'mar', 'apr', 'may', 'jun', 'jul', 'aug', 'sep', 'oct', 'nov', 'dec'];
        $currentMonthIndex = array_search(strtolower($monthKey), $months);

        $differenceCosts = [];
        $processedItemCodes = [];

        $currentYear = strlen($validatedData['actual_period']) > 4
            ? substr($validatedData['actual_period'], -4)
            : $validatedData['actual_period'];

        foreach ($standardCost as $sc) {
            $ac = $actualCost->firstWhere('item_code', $sc->item_code);
            $salesQtyData = $actualSalesQuantities->get($sc->item_code);

            $qty = 0;
            if ($salesQtyData) {
                if ($isYTD && $currentMonthIndex !== false) {
                    for ($i = 0; $i <= $currentMonthIndex; $i++) {
                        $col = $months[$i] . '_qty';
                        $qty += (int) ($salesQtyData->{$col} ?? 0);
                    }
                } else {
                    $col = strtolower($monthKey) . '_qty';
                    $qty = (int) ($salesQtyData->{$col} ?? 0);
                }
            }

            $finalRemark = 'Normal';
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

                $differenceCosts[] = [
                    'item_code' => $sc->item_code,
                    'period' => $displayPeriod,
                    'quantity' => $qty,
                    'total_raw_material' => $diff_raw_material,
                    'total_process' => $diff_process,
                    'total' => $diff_total,
                    'qty_x_total_raw_material' => $qty * (float) $diff_raw_material,
                    'qty_x_total_process' => $qty * (float) $diff_process,
                    'qty_x_total' => $qty * (float) $diff_total,
                    'diff_disc' => $sc_disc_price - $ac_disc_price,
                    'diff_rim' => $sc_rim_price - $ac_rim_price,
                    'diff_sidering' => $sc_sidering_price - $ac_sidering_price,
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

                $diff_raw_material = $sc->total_raw_material;
                $diff_process = $sc->total_process;
                $diff_total = $diff_raw_material + $diff_process;

                $differenceCosts[] = [
                    'item_code' => $sc->item_code,
                    'period' => $displayPeriod,
                    'quantity' => $qty,
                    'total_raw_material' => $diff_raw_material,
                    'total_process' => $diff_process,
                    'total' => $diff_total,
                    'qty_x_total_raw_material' => $qty * (float) $diff_raw_material,
                    'qty_x_total_process' => $qty * (float) $diff_process,
                    'qty_x_total' => $qty * (float) $diff_total,
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
            $salesQtyData = $actualSalesQuantities->get($ac->item_code);

            $qty = 0;
            if ($salesQtyData) {
                if ($isYTD && $currentMonthIndex !== false) {
                    for ($i = 0; $i <= $currentMonthIndex; $i++) {
                        $col = $months[$i] . '_qty';
                        $qty += (int) ($salesQtyData->{$col} ?? 0);
                    }
                } else {
                    $col = strtolower($monthKey) . '_qty';
                    $qty = (int) ($salesQtyData->{$col} ?? 0);
                }
            }

            $finalRemark = 'Normal';
            $diff_raw_material = 0 - $ac->total_raw_material;
            $diff_process = 0 - $ac->total_process;
            $diff_total = $diff_raw_material + $diff_process;

            $ac_disc_price = $ac->disc_price ?? 0;
            $ac_rim_price = $ac->rim_price ?? 0;
            $ac_sidering_price = $ac->sidering_price ?? 0;

            $previousSCPeriod = $this->getStandardCostHistoryPeriod($ac->item_code, $currentYear);
            if ($previousSCPeriod) {
                $finalRemark = "Using previous standard cost {$previousSCPeriod}";
            } else {
                $finalRemark = "New Product";
            }

            $differenceCosts[] = [
                'item_code' => $ac->item_code,
                'period' => $displayPeriod,
                'quantity' => $qty,
                'total_raw_material' => $diff_raw_material,
                'total_process' => $diff_process,
                'total' => $diff_total,
                'qty_x_total_raw_material' => $qty * (float) $diff_raw_material,
                'qty_x_total_process' => $qty * (float) $diff_process,
                'qty_x_total' => $qty * (float) $diff_total,
                'diff_disc' => 0 - $ac_disc_price,
                'diff_rim' => 0 - $ac_rim_price,
                'diff_sidering' => 0 - $ac_sidering_price,
                'remark' => $finalRemark,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        foreach (array_chunk($differenceCosts, 500) as $chunk) {
            DifferenceCost::upsert($chunk, ['item_code', 'period'], [
                'quantity',
                'total_raw_material',
                'total_process',
                'total',
                'qty_x_total_raw_material',
                'qty_x_total_process',
                'qty_x_total',
                'diff_disc',
                'diff_rim',
                'diff_sidering',
                'remark',
                'updated_at'
            ]);
        }

        return redirect()->route('dc.report');
    }

    protected function getCombinedDiffCost($scCollection, $acCollection, $dcCollection)
    {
        return $dcCollection->map(function ($dcItem) use ($scCollection, $acCollection) {
            $sc = $scCollection->firstWhere('item_code', $dcItem->item_code);
            $ac = $acCollection->firstWhere('item_code', $dcItem->item_code);
            $dcItem->custom_field = 'SC: ' . ($sc->period ?? 'N/A') . ', AC: ' . ($ac->period ?? 'N/A');

            return $dcItem;
        });
    }

    public function getPaginated(Request $request)
    {
        $searchTerm = $request->input('search');
        $itemCodeFilter = $request->input('item_code_filter');
        $descriptionFilter = $request->input('description_filter');
        $periodFilter = $request->input('period_filter');
        $remarkFilter = $request->input('remark_filter');
        $quantityFilter = $request->input('quantity_filter');

        $sortField = $request->input('sort_field');
        $sortOrder = $request->input('sort_order', 'asc');
        $perPage = $request->input('per_page', 10);

        $query = DifferenceCost::select('difference_cost.*')
            ->join('actual_cost AS ac', function ($join) {
                $join->on('ac.item_code', '=', 'difference_cost.item_code');
                $join->whereRaw('difference_cost.period LIKE CONCAT(ac.period, "%")');
            })
            ->join('standard_cost AS sc', function ($join) {
                $join->on('sc.item_code', '=', 'difference_cost.item_code');
                $join->whereRaw('sc.period = SUBSTRING_INDEX(SUBSTRING_INDEX(difference_cost.period, " /", 1), "\'", -1)');
            })
            ->leftJoin('standard_bill_of_materials AS bom', 'bom.item_code', '=', 'difference_cost.item_code')
            ->when($searchTerm, function ($query, $term) {
                $query->where(function ($q) use ($term) {
                    $q->where('difference_cost.item_code', 'LIKE', '%' . $term . '%')
                        ->orWhere('difference_cost.period', 'LIKE', '%' . $term . '%')
                        ->orWhere('bom.description', 'LIKE', '%' . $term . '%');
                });
            })
            ->when($itemCodeFilter, function ($query, $term) {
                $query->where('difference_cost.item_code', 'LIKE', $term . '%');
            })
            ->when($descriptionFilter, function ($query, $term) {
                $query->where('bom.description', 'LIKE', '%' . $term . '%');
            })
            ->when($periodFilter, function ($query, $term) {
                $query->where('difference_cost.period', 'LIKE', '%' . $term . '%');
            })
            ->when($quantityFilter, function ($query, $term) {
                if (is_array($term)) {
                    $query->whereIn('difference_cost.quantity', $term);
                } else {
                    $query->where('difference_cost.quantity', '=', $term);
                }
            })
            ->when($remarkFilter, function ($query, $term) {
                if (is_array($term)) {
                    $query->whereIn('difference_cost.remark', $term);
                } else {
                    $query->where('difference_cost.remark', '=', $term);
                }
            })
            ->where('difference_cost.quantity', '>', 0)
            ->selectRaw('
            difference_cost.*,
            bom.description AS description,
            sc.period AS sc_period,
            sc.disc_code AS sc_disc_code,
            sc.disc_price AS sc_disc_price,
            sc.rim_code AS sc_rim_code,
            sc.rim_price AS sc_rim_price,
            sc.sidering_code AS sc_sidering_code,
            sc.sidering_price AS sc_sidering_price,
            sc.total_raw_material AS sc_total_raw_material,
            sc.total_process AS sc_total_process,
            sc.total AS sc_total,
            ac.period AS ac_period,
            ac.disc_code AS ac_disc_code,
            ac.disc_price AS ac_disc_price,
            ac.rim_code AS ac_rim_code,
            ac.rim_price AS ac_rim_price,
            ac.sidering_code AS ac_sidering_code,
            ac.sidering_price AS ac_sidering_price,
            ac.total_raw_material AS ac_total_raw_material,
            ac.total_process AS ac_total_process,
            ac.total AS ac_total,
            difference_cost.qty_x_total_raw_material,
            difference_cost.qty_x_total_process,
            difference_cost.qty_x_total
        ');

        if ($sortField) {
            $direction = ($sortOrder === 'desc' || $sortOrder === '-1') ? 'desc' : 'asc';

            if ($sortField === 'period') {
                $query->orderByRaw(
                    '
                CASE WHEN difference_cost.period LIKE "YTD-%" THEN 1 ELSE 0 END ' . $direction . ',
                FIELD(SUBSTR(REPLACE(difference_cost.period, "YTD-", ""), 1, 3), 
                "Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec") ' . $direction
                );
            } else {
                $query->orderBy($sortField, $direction);
            }
        } else {
            $query->orderByRaw('
            CASE WHEN difference_cost.period LIKE "YTD-%" THEN 1 ELSE 0 END ASC,
            FIELD(SUBSTR(REPLACE(difference_cost.period, "YTD-", ""), 1, 3), 
            "Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec") ASC
        ');
        }

        $dcPaginated = $query->paginate($perPage);

        return response()->json($dcPaginated);
    }

    public function getTotal(Request $request)
    {
        $periodFilter = $request->input('period_filter');
        $query = DifferenceCost::query();
        if ($periodFilter) {
            $query->where('period', $periodFilter);
        }
        $totals = $query->selectRaw('
        SUM(qty_x_total_raw_material) AS total_raw_material,
        SUM(qty_x_total_process) AS total_process,
        SUM(qty_x_total) AS total
    ')->first();
        return response()->json($totals);
    }

    public function getLatestPeriod()
    {
        $periods = DifferenceCost::distinct('period')
            ->where('period', 'NOT LIKE', 'YTD-%')
            ->pluck('period');

        if ($periods->isEmpty()) return response()->json(null);

        $months = [
            'Jan' => 1,
            'Feb' => 2,
            'Mar' => 3,
            'Apr' => 4,
            'May' => 5,
            'Jun' => 6,
            'Jul' => 7,
            'Aug' => 8,
            'Sep' => 9,
            'Oct' => 10,
            'Nov' => 11,
            'Dec' => 12
        ];

        $latest = $periods->map(function ($p) use ($months) {
            $monthStr = substr($p, 0, 3);

            $parts = explode('/', $p);
            $firstPart = trim($parts[0]);
            $year = (int) substr($firstPart, -4);

            return [
                'original' => $p,
                'year' => $year,
                'month_val' => $months[$monthStr] ?? 0
            ];
        })
            ->sort(function ($a, $b) {
                if ($a['year'] === $b['year']) {
                    return $b['month_val'] <=> $a['month_val'];
                }
                return $b['year'] <=> $a['year'];
            })
            ->first();

        return response()->json($latest['original'], 200, [], JSON_UNESCAPED_SLASHES);
    }

    public function getQuantity(Request $request)
    {
        $dcQuantity = DifferenceCost::distinct()->pluck('quantity');

        return response()->json($dcQuantity);
    }

    public function getPeriod()
    {
        $dcPeriod = DifferenceCost::distinct()->pluck('period');
        $dcPeriod = $this->sortPeriods($dcPeriod);

        return response()->json($dcPeriod);
    }

    public function getRemark()
    {
        $dcRemark = DifferenceCost::distinct()->pluck('remark');

        return response()->json($dcRemark);
    }

    public function getExport(Request $request)
    {
        $periodFilter = $request->input('period_filter');
        $query = DifferenceCost::query();
        if ($periodFilter) {
            $query->where('period', $periodFilter);
        }
        $dcExport = $query->get();

        return response()->json($dcExport);
    }
}
