<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ReportService;

class ReportController extends Controller
{
    public function indexCTxSQ(ReportService $service)
    {
        $datas = $service->calculateCTxSQ();
        $processes = $service->getProcessList();

        $totals = [];
        foreach ($processes as $proc) {
            $totals[$proc] = $datas->sum(fn($d) => $d->ctxsq[$proc] ?? 0);
        }

        return redirect()->route('pc.report')->with([
            'datas' => $datas,
            'processes' => $processes,
            'totals' => $totals,
        ]);
    }

    public function indexBaseCost(ReportService $service)
    {
        $ctxsq = $service->calculateCTxSQ();
        $base = $service->calculateBaseCost($ctxsq);
        $processes = $service->getProcessList();
        $totals = [];
        foreach ($processes as $proc) {
            $totals[$proc] = round($base->sum(fn($d) => $d->basecost[$proc] ?? 0), 2);
        }


        return view('report.baseCost', ['datas' => $base, 'processes' => $processes, 'totals' => $totals]);
    }

    public function indexCostPerProcess(ReportService $service)
    {
        $ctxsq = $service->calculateCTxSQ();
        $base = $service->calculateBaseCost($ctxsq);
        $cpp = $service->calculateCostPerProcess($base);
        $processes = $service->getProcessList($cpp);

        $totals = [];
        foreach ($processes as $proc) {
            $totals[$proc] = round($cpp->sum(fn($d) => $d->cpp[$proc] ?? 0), 2);
        }

        return view('report.costPerProcess', ['datas' => $cpp, 'processes' => $processes, 'totals' => $totals]);
    }

    public function indexProcessCost(ReportService $service)
    {
        $ctxsq = $service->calculateCTxSQ();
        $base = $service->calculateBaseCost($ctxsq);
        $cpp = $service->calculateCostPerProcess($base);
        $processCost = $service->calculateProcessCost($cpp);

        // Group by item_code
        $grouped = $processCost->groupBy('item_code');

        // Ambil nilai maksimum untuk setiap proses dari item yang sama
        $maxProcessCost = $grouped->map(function ($items) {
            $first = $items->first(); // untuk info umum seperti bp_code, type, dll

            // Ambil semua nama proses (misal "Total Disc", dll)
            $allProcessKeys = collect($items)->flatMap(fn($i) => array_keys($i->process_cost))->unique();

            // Hitung max untuk masing-masing proses
            $maxCosts = [];

            // hasil tanpa desimal
            foreach ($allProcessKeys as $key) {
                $maxValue = collect($items)->max(fn($i) => $i->process_cost[$key] ?? 0);

                // Bulatkan ke atas tanpa desimal
                $rounded = ceil($maxValue);

                $maxCosts[$key] = $rounded;
            }

            return (object)[
                'bp_code' => $first->bp_code,
                'bp_name' => $first->bp_name,
                'item_code' => $first->item_code,
                'type' => $first->type,
                'quantity' => $first->quantity, // bisa disesuaikan: sum atau ambil max/first
                'process_cost' => $maxCosts,
            ];
        })->values(); // Reset index

        foreach ($maxProcessCost as $item) {
            ProcessCost::updateOrCreate(
                ['item_code' => $item->item_code],
                [
                    'max_of_disc'      => $item->process_cost['Max of Disc'] ?? 0,
                    'max_of_rim'       => $item->process_cost['Max of Rim'] ?? 0,
                    'max_of_sidering'  => $item->process_cost['Max of Sidering'] ?? 0,
                    'max_of_assy'      => $item->process_cost['Max of Assy'] ?? 0,
                    'max_of_ced'       => $item->process_cost['Max of CED'] ?? 0,
                    'max_of_topcoat'   => $item->process_cost['Max of Topcoat'] ?? 0,
                    'max_of_packaging' => $item->process_cost['Max of Packaging'] ?? 0,
                    'max_of_total'     => $item->process_cost['Max of Total'] ?? 0,
                ]
            );
        }

        $footerMaxValues = [];

        foreach ($service->getLine() as $group) {
            // Langsung gunakan nama key sesuai array: "Max of Disc", dst
            $footerMaxValues[$group] = $maxProcessCost->max(fn($item) => $item->process_cost[$group] ?? 0);
        }

        return view('report.processCost', [
            'datas' => $maxProcessCost,
            'groupings' => $service->getLine(),
            'footerMaxValues' => $footerMaxValues,
        ]);
    }

    public function indexUsage()
    {
        $processes = [
            'blanking',
            'spinDisc',
            'autoDisc',
            'manualDisc',
            'C3/SN',
            'repairC3',
            'discLathe',
            'Total Disc',
            'rim1',
            'rim2',
            'rim2insp',
            'rim3',
            'Total Rim',
            'coiler',
            'forming',
            'Total Sidering',
            'assy1',
            'assy2',
            'machining',
            'shotpeening',
            'Total Assy',
            'ced',
            'topcoat',
            'Total Painting',
            'packing_dom',
            'packing_exp',
            'Total Packing',
            'Total Cycle Time Usage',
        ];

        $groups = [
            'Total Disc' => ['blanking', 'spinDisc', 'autoDisc', 'manualDisc', 'C3/SN', 'repairC3', 'discLathe'],
            'Total Rim' => ['rim1', 'rim2', 'rim2insp', 'rim3'],
            'Total Sidering' => ['coiler', 'forming'],
            'Total Assy' => ['assy1', 'assy2', 'machining', 'shotpeening'],
            'Total Painting' => ['ced', 'topcoat'],
            'Total Packing' => ['packing_dom', 'packing_exp'],
        ];

        $datas = RawSalesQty::with(['bp', 'item'])->get();

        $result = $datas->map(function ($data) use ($processes, $groups) {
            $usage = [];

            foreach ($processes as $process) {
                $field = str_replace(['/', ' '], '', $process);
                $value = optional($data->item)->{$field};
                $usage[$process] = ($value && $value > 0) ? 1 : 0;
            }

            // Hitung total grup proses
            foreach ($groups as $groupName => $members) {
                $usage[$groupName] = collect($members)->sum(fn($p) => $usage[$p] ?? 0);
            }

            // Total Cycle Time Usage dari semua grup
            $usage['Total Cycle Time Usage'] = collect(array_keys($groups))
                ->sum(fn($group) => $usage[$group] ?? 0);

            return (object)[
                'bp_code' => $data->bp_code,
                'bp_name' => optional($data->bp)->bp_name,
                'item_code' => $data->item_code,
                'type' => optional($data->item)->type,
                'quantity' => $data->quantity,
                'usage' => $usage,
            ];
        });

        $totals = [];
        foreach ($processes as $process) {
            $totals[$process] = $result->sum(fn($item) => $item->usage[$process] ?? 0);
        }

        return view('report.usage', [
            'datas' => $result,
            'processes' => $processes,
            'totals' => $totals
        ]);
    }

    public function indexBomReport()
    {
        // Ambil semua data BOM yang sudah terurut
        $bomData = BOM::all();

        if ($bomData->isEmpty()) {
            return response()->json(['message' => 'Tidak ada data BOM tersedia']);
        }

        // Group data berdasarkan depth = 1 sebagai main, sisanya sebagai komponennya
        $groups = collect();
        $currentGroup = collect();

        foreach ($bomData as $row) {
            if ($row->depth == 1) {
                if ($currentGroup->isNotEmpty()) {
                    $groups->push($currentGroup);
                }
                $currentGroup = collect([$row]);
            } else {
                $currentGroup->push($row);
            }
        }

        if ($currentGroup->isNotEmpty()) {
            $groups->push($currentGroup);
        }

        // Tambahkan properti type_name pada main item & cari komponennya berdasarkan tipe
        foreach ($groups as $group) {
            $main = $group->first();
            $typeChar = substr($main->item_code, 3, 1);
            $main->type_name = match ($typeChar) {
                'D' => 'Disc',
                'N' => 'Side Ring',
                'W' => 'Wheel',
                'R' => 'Rim',
                default => $main->item_code,
            };

            // Komponen lain ...
            $group->disc = $group->first(function ($item) {
                return substr($item->item_code, 0, 3) === 'RFD';
            });
            $group->rim = $group->first(function ($item) {
                return substr($item->item_code, 0, 3) === 'RFR';
            });
            $group->sidering = $group->first(function ($item) {
                return substr($item->item_code, 0, 3) === 'RFS';
            });

            // Properti pr_*
            $group->pr_disc = $group->first(function ($item) {
                return substr($item->item_code, 0, 5) === 'RS-DC';
            });
            $group->pr_rim = $group->first(function ($item) {
                return substr($item->item_code, 0, 5) === 'RS-RB';
            });
            $group->pr_sr = $group->first(function ($item) {
                return substr($item->item_code, 0, 5) === 'RS-SR';
            });
            $group->pr_assy = $group->first(function ($item) {
                return substr($item->item_code, 0, 5) === 'RS-AS';
            });

            // Logika filter CED
            $cdItems = $group->filter(function ($item) {
                return Str::startsWith($item->item_code, 'RS-CD');
            })->values();

            if ($cdItems->count() >= 2) {
                $group->cedW = $cdItems[0];
                $group->cedSR = $cdItems[1];
            } elseif ($cdItems->count() === 1) {
                if ($typeChar === 'D') {
                    $group->cedW = $cdItems[0];
                    $group->cedSR = null;
                } elseif ($typeChar === 'N') {
                    $group->cedW = null;
                    $group->cedSR = $cdItems[0];
                } else {
                    $group->cedW = null;
                    $group->cedSR = null;
                }
            } else {
                $group->cedW = null;
                $group->cedSR = null;
            }

            $tcItems = $group->filter(function ($item) {
                return Str::startsWith($item->item_code, 'RS-TC');
            })->values();

            if ($tcItems->count() >= 2) {
                $group->tcW = $tcItems[0];
                $group->tcSR = $tcItems[1];
            } elseif ($tcItems->count() === 1) {
                if ($typeChar === 'D') {
                    $group->tcW = $tcItems[0];
                    $group->tcSR = null;
                } elseif ($typeChar === 'N') {
                    $group->tcW = null;
                    $group->tcSR = $tcItems[0];
                } else {
                    $group->tcW = null;
                    $group->tcSR = null;
                }
            } else {
                $group->tcW = null;
                $group->tcSR = null;
            }

            $group->wip_disc = $group->first(function ($item) {
                return substr($item->item_code, 0, 2) === 'WB' && substr($item->item_code, 3, 2) === 'D-';
            });

            $group->wip_rim = $group->first(function ($item) {
                return substr($item->item_code, 0, 2) === 'WA' && substr($item->item_code, 3, 2) === 'R-';
            });

            $group->wip_sr = $group->first(function ($item) {
                return substr($item->item_code, 0, 2) === 'WD' && substr($item->item_code, 3, 2) === 'S-';
            });

            $group->wip_assy = $group->first(function ($item) {
                return substr($item->item_code, 0, 2) === 'WC' && substr($item->item_code, 3, 2) === 'W-';
            });

            $group->wip_cedW = $group->first(function ($item) {
                return substr($item->item_code, 0, 2) === 'WE' && substr($item->item_code, 3, 2) === 'W-';
            });

            $group->wip_cedSR = $group->first(function ($item) {
                return substr($item->item_code, 0, 2) === 'WE' && substr($item->item_code, 3, 2) === 'S-';
            });

            $group->wip_tcW = $group->first(function ($item) {
                return substr($item->item_code, 0, 2) === 'WF' && substr($item->item_code, 3, 2) === 'W-';
            });

            $group->wip_tcSR = $group->first(function ($item) {
                return substr($item->item_code, 0, 2) === 'WF' && substr($item->item_code, 3, 2) === 'S-';
            });

            $group->wip_valve = $group->first(function ($item) {
                return substr($item->item_code, 0, 3) === 'CGP'
                    && (
                        stripos($item->description, 'valve') !== false
                        || stripos($item->description, 'VLI') !== false
                    );
            });
        }

        return view('report.bomReport', ['bomGroups' => $groups]);
    }
}
