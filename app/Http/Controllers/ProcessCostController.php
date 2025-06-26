<?php

namespace App\Http\Controllers;

use App\Models\BusinessPartner;
use App\Models\CycleTime;
use App\Models\ProcessCost;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\SalesQuantity;
use App\Models\WagesDistribution;
use App\Services\ReportService;


class ProcessCostController extends Controller
{
    public function master()
    {
        $bPartner = BusinessPartner::all();
        $cTimes = CycleTime::all();
        $salesQuantity = SalesQuantity::with(['bp', 'item'])->get();
        $wagesDistribution = WagesDistribution::first();

        return Inertia::render("pc/master", [
            'businessPartners' => $bPartner,
            'cycleTimes' => $cTimes,
            'salesQuantities' => $salesQuantity,
            'wagesDistribution' => $wagesDistribution,
        ]);
    }

    public function report(ReportService $service)
    {
        $ctxsq = $service->calculateCTxSQ();
        $processes = $service->getProcessList();
        $base = $service->calculateBaseCost($ctxsq);
        $cpp = $service->calculateCostPerProcess($base);
        $processCost = $service->calculateProcessCost($cpp);

        $total_ctxsq = [];
        $total_base = [];
        $total_cpp = [];

        foreach ($processes as $proc) {
            $total_ctxsq[$proc] = $ctxsq->sum(fn($d) => $d->ctxsq[$proc] ?? 0);
        }

        foreach ($processes as $proc) {
            $total_base[$proc] = round($base->sum(fn($d) => $d->basecost[$proc] ?? 0), 2);
        }

        foreach ($processes as $proc) {
            $total_cpp[$proc] = round($cpp->sum(fn($d) => $d->cpp[$proc] ?? 0), 2);
        }

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

        // dd($maxProcessCost);
        return Inertia::render("pc/report", [
            'ctxsq' => $ctxsq,
            'processes' => $processes,
            'total_ctxsq' => $total_ctxsq,
            'base' => $base,
            'total_base' => $total_base,
            'cpp' => $cpp,
            'total_cpp' => $total_cpp,
            'processCost' => $maxProcessCost,
            'groupings' => $service->getLine(),
            'footerMaxValues' => $footerMaxValues,
        ]);
    }
}
