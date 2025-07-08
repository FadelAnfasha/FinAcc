<?php

namespace App\Http\Controllers;

use App\Models\BaseCost;
use App\Models\BusinessPartner;
use App\Models\costPerProcess;
use App\Models\CycleTime;
use App\Models\ProcessCost;
use App\Models\CTxSQ;
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


    public function report()
    {
        $ctxsq = CTxSQ::with(['bp', 'item'])->get();
        $base = BaseCost::with(['bp', 'item'])->take('10')->get();
        $cpp = costPerProcess::with(['bp', 'item'])->take('10')->get();
        $pc = ProcessCost::all();


        // dd($pc);
        // dd($base);
        // dd($cpp);

        return Inertia::render("pc/report", [
            'ctxsq' => $ctxsq,
            'base' => $base,
            'cpp' => $cpp,
            'processCost' => $pc
        ]);
    }

    public function updateCTxSQ(ReportService $service)
    {
        $ctxsq = $service->calculateCTxSQ();
        CTxSQ::truncate();

        CTxSQ::truncate();

        foreach ($ctxsq as $index => $item) {
            CTxSQ::updateOrCreate(
                [
                    'id' => $index,
                    'bp_code' => $item->bp_code,
                    'item_code' => $item->item_code,
                ],
                [
                    'blanking' => $item->ctxsq['blanking'] ?? 0,
                    'spinDisc' => $item->ctxsq['spinDisc'] ?? 0,
                    'autoDisc' => $item->ctxsq['autoDisc'] ?? 0,
                    'manualDisc' => $item->ctxsq['manualDisc'] ?? 0,
                    'discLathe' => $item->ctxsq['discLathe'] ?? 0,
                    'total_disc' => $item->ctxsq['Total Disc'] ?? 0,
                    'rim1' => $item->ctxsq['rim1'] ?? 0,
                    'rim2' => $item->ctxsq['rim2'] ?? 0,
                    'rim3' => $item->ctxsq['rim3'] ?? 0,
                    'total_rim' => $item->ctxsq['Total Rim'] ?? 0,
                    'coiler' => $item->ctxsq['coiler'] ?? 0,
                    'forming' => $item->ctxsq['forming'] ?? 0,
                    'total_sidering' => $item->ctxsq['Total Sidering'] ?? 0,
                    'assy1' => $item->ctxsq['assy1'] ?? 0,
                    'assy2' => $item->ctxsq['assy2'] ?? 0,
                    'machining' => $item->ctxsq['machining'] ?? 0,
                    'shotPeening' => $item->ctxsq['shotPeening'] ?? 0,
                    'total_assy' => $item->ctxsq['Total Assy'] ?? 0,
                    'ced' => $item->ctxsq['ced'] ?? 0,
                    'topcoat' => $item->ctxsq['topcoat'] ?? 0,
                    'total_painting' => $item->ctxsq['Total Painting'] ?? 0,
                    'packing_dom' => $item->ctxsq['packing_dom'] ?? 0,
                    'packing_exp' => $item->ctxsq['packing_exp'] ?? 0,
                    'total_packaging' => $item->ctxsq['Total Packaging'] ?? 0,
                    'total' => $item->ctxsq['Total'] ?? 0,
                ]
            );
        }

        redirect()->route('pc.master');
    }

    public function updateBaseCost(ReportService $service)
    {
        $ctxsq = $service->calculateCTxSQ();
        $base = $service->calculateBaseCost($ctxsq);

        BaseCost::truncate();

        foreach ($base as $index => $item) {
            BaseCost::updateOrCreate(
                [
                    'id' => $index,
                    'bp_code' => $item->bp_code,
                    'item_code' => $item->item_code,
                ],
                [
                    'blanking' => $item->basecost['blanking'] ?? 0,
                    'spinDisc' => $item->basecost['spinDisc'] ?? 0,
                    'autoDisc' => $item->basecost['autoDisc'] ?? 0,
                    'manualDisc' => $item->basecost['manualDisc'] ?? 0,
                    'discLathe' => $item->basecost['discLathe'] ?? 0,
                    'total_disc' => $item->basecost['Total Disc'] ?? 0,
                    'rim1' => $item->basecost['rim1'] ?? 0,
                    'rim2' => $item->basecost['rim2'] ?? 0,
                    'rim3' => $item->basecost['rim3'] ?? 0,
                    'total_rim' => $item->basecost['Total Rim'] ?? 0,
                    'coiler' => $item->basecost['coiler'] ?? 0,
                    'forming' => $item->basecost['forming'] ?? 0,
                    'total_sidering' => $item->basecost['Total Sidering'] ?? 0,
                    'assy1' => $item->basecost['assy1'] ?? 0,
                    'assy2' => $item->basecost['assy2'] ?? 0,
                    'machining' => $item->basecost['machining'] ?? 0,
                    'shotPeening' => $item->basecost['shotPeening'] ?? 0,
                    'total_assy' => $item->basecost['Total Assy'] ?? 0,
                    'ced' => $item->basecost['ced'] ?? 0,
                    'topcoat' => $item->basecost['topcoat'] ?? 0,
                    'total_painting' => $item->basecost['Total Painting'] ?? 0,
                    'packing_dom' => $item->basecost['packing_dom'] ?? 0,
                    'packing_exp' => $item->basecost['packing_exp'] ?? 0,
                    'total_packaging' => $item->basecost['Total Packaging'] ?? 0,
                    'total' => $item->basecost['Total'] ?? 0,
                ]
            );
        }

        redirect()->route('pc.master');
    }

    public function updateCPP(ReportService $service)
    {
        $ctxsq = $service->calculateCTxSQ();
        $base = $service->calculateBaseCost($ctxsq);
        $cpp = $service->calculateCostPerProcess($base);

        costPerProcess::truncate();

        foreach ($cpp as $index => $item) {
            costPerProcess::updateOrCreate(
                [
                    'id' => $index,
                    'bp_code' => $item->bp_code,
                    'item_code' => $item->item_code,
                ],
                [
                    'blanking' => $item->cpp['blanking'] ?? 0,
                    'spinDisc' => $item->cpp['spinDisc'] ?? 0,
                    'autoDisc' => $item->cpp['autoDisc'] ?? 0,
                    'manualDisc' => $item->cpp['manualDisc'] ?? 0,
                    'discLathe' => $item->cpp['discLathe'] ?? 0,
                    'total_disc' => $item->cpp['Total Disc'] ?? 0,
                    'rim1' => $item->cpp['rim1'] ?? 0,
                    'rim2' => $item->cpp['rim2'] ?? 0,
                    'rim3' => $item->cpp['rim3'] ?? 0,
                    'total_rim' => $item->cpp['Total Rim'] ?? 0,
                    'coiler' => $item->cpp['coiler'] ?? 0,
                    'forming' => $item->cpp['forming'] ?? 0,
                    'total_sidering' => $item->cpp['Total Sidering'] ?? 0,
                    'assy1' => $item->cpp['assy1'] ?? 0,
                    'assy2' => $item->cpp['assy2'] ?? 0,
                    'machining' => $item->cpp['machining'] ?? 0,
                    'shotPeening' => $item->cpp['shotPeening'] ?? 0,
                    'total_assy' => $item->cpp['Total Assy'] ?? 0,
                    'ced' => $item->cpp['ced'] ?? 0,
                    'topcoat' => $item->cpp['topcoat'] ?? 0,
                    'total_painting' => $item->cpp['Total Painting'] ?? 0,
                    'packing_dom' => $item->cpp['packing_dom'] ?? 0,
                    'packing_exp' => $item->cpp['packing_exp'] ?? 0,
                    'total_packaging' => $item->cpp['Total Packaging'] ?? 0,
                    'total' => $item->cpp['Total'] ?? 0,
                ]
            );
        }

        redirect()->route('pc.master');
    }

    // public function updatePC(ReportService $service)
    // {
    //     $ctxsq = $service->calculateCTxSQ();
    //     $processes = $service->getProcessList();
    //     $base = $service->calculateBaseCost($ctxsq);
    //     $cpp = $service->calculateCostPerProcess($base);

    //     $total_ctxsq = [];
    //     $total_base = [];
    //     $total_cpp = [];

    //     foreach ($processes as $proc) {
    //         $total_ctxsq[$proc] = $ctxsq->sum(fn($d) => $d->ctxsq[$proc] ?? 0);
    //     }

    //     foreach ($processes as $proc) {
    //         $total_base[$proc] = round($base->sum(fn($d) => $d->basecost[$proc] ?? 0), 2);
    //     }

    //     foreach ($processes as $proc) {
    //         $total_cpp[$proc] = round($cpp->sum(fn($d) => $d->cpp[$proc] ?? 0), 2);
    //     }

    //     // Group by item_code
    //     $grouped = $processCost->groupBy('item_code');

    //     // Ambil nilai maksimum untuk setiap proses dari item yang sama
    //     $maxProcessCost = $grouped->map(function ($items) {
    //         $first = $items->first(); // untuk info umum seperti bp_code, type, dll

    //         // Ambil semua nama proses (misal "Total Disc", dll)
    //         $allProcessKeys = collect($items)->flatMap(fn($i) => array_keys($i->process_cost))->unique();

    //         // Hitung max untuk masing-masing proses
    //         $maxCosts = [];

    //         // hasil tanpa desimal
    //         foreach ($allProcessKeys as $key) {
    //             $maxValue = collect($items)->max(fn($i) => $i->process_cost[$key] ?? 0);

    //             // Bulatkan ke atas tanpa desimal
    //             $rounded = ceil($maxValue);

    //             $maxCosts[$key] = $rounded;
    //         }

    //         return (object)[
    //             'bp_code' => $first->bp_code,
    //             'bp_name' => $first->bp_name,
    //             'item_code' => $first->item_code,
    //             'type' => $first->type,
    //             'quantity' => $first->quantity, // bisa disesuaikan: sum atau ambil max/first
    //             'process_cost' => $maxCosts,
    //         ];
    //     })->values();

    //     foreach ($maxProcessCost as $item) {
    //         ProcessCost::updateOrCreate(
    //             ['item_code' => $item->item_code],
    //             [
    //                 'max_of_disc'      => $item->process_cost['Max of Disc'] ?? 0,
    //                 'max_of_rim'       => $item->process_cost['Max of Rim'] ?? 0,
    //                 'max_of_sidering'  => $item->process_cost['Max of Sidering'] ?? 0,
    //                 'max_of_assy'      => $item->process_cost['Max of Assy'] ?? 0,
    //                 'max_of_ced'       => $item->process_cost['Max of CED'] ?? 0,
    //                 'max_of_topcoat'   => $item->process_cost['Max of Topcoat'] ?? 0,
    //                 'max_of_packaging' => $item->process_cost['Max of Packaging'] ?? 0,
    //                 'max_of_total'     => $item->process_cost['Max of Total'] ?? 0,
    //             ]
    //         );
    //     }

    //     $footerMaxValues = [];

    //     foreach ($service->getLine() as $group) {
    //         // Langsung gunakan nama key sesuai array: "Max of Disc", dst
    //         $footerMaxValues[$group] = $maxProcessCost->max(fn($item) => $item->process_cost[$group] ?? 0);
    //     }
    // }
}
