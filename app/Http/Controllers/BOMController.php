<?php

namespace App\Http\Controllers;

use App\Models\BillOfMaterial;
use App\Models\ProcessCost;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Material;
use App\Models\Process;
use App\Models\Packing;
use App\Models\BOM_Report;
use Illuminate\Support\Str;

class BOMController extends Controller
{
    public function master(Request $request)
    {
        $materials = Material::with('bom')->get();
        $bom = BillOfMaterial::where('depth', 1)->get();
        $packings = Packing::all();
        $processes = Process::all();

        $componentItems = collect();
        $finishGood = null;

        if ($request->has('component_id')) {
            $all = BillOfMaterial::orderBy('id')->get();
            $mainIndex = $all->search(fn($item) => $item->id == $request->component_id);

            if ($mainIndex !== false) {
                for ($i = $mainIndex + 1; $i < count($all); $i++) {
                    if ($all[$i]->depth == 1) break;
                    $componentItems->push($all[$i]);
                }

                // Ambil finish good-nya berdasarkan posisi index
                $finishGood = $all[$mainIndex];
            }
        }

        return Inertia::render("bom/master", [
            'materials' => $materials,
            'packings' => $packings,
            'billOfMaterials' => $bom,
            'processes' => $processes,
            'finish_good' => $finishGood,
            'component' => $componentItems, // ✅ dikirim ke frontend
        ]);
    }

    public function report(Request $request)
    {
        $bom = BOM_Report::take('10')->get();
        // dd($bom);

        return Inertia::render("bom/report", [
            'bom' => $bom
        ]);
    }

    public function updateBOM(Request $request)
    {
        // Ambil semua data BOM yang sudah terurut
        $bomData = BillOfMaterial::all();

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

        foreach ($groups as $group) {
            $main = $group->first(); // FG
            $main->load(['processCost', 'materialInfo']);

            $pr_cedW_price = ceil(((($main->processCost->max_of_ced ?? null) * 5) / 7) * 100) / 100;
            $pr_cedSR_price = ceil(((($main->processCost->max_of_ced ?? null) * 2) / 7) * 100) / 100;
            $pr_tcW_price = ceil(((($main->processCost->max_of_topcoat ?? null) * 5) / 7) * 100) / 100;
            $pr_tcSR_price = ceil(((($main->processCost->max_of_topcoat ?? null) * 2) / 7) * 100) / 100;


            $wip_disc_price = ceil((($main->processCost->max_of_disc ?? 0) + ($group->disc->materialInfo->price ?? 0)) * 100) / 100;
            $wip_rim_price = ceil((($main->processCost->max_of_rim ?? 0) + ($group->rim->materialInfo->price ?? 0)) * 100) / 100;
            $wip_sidering_price = ceil((($main->processCost->max_of_sidering ?? 0) + ($group->sidering->materialInfo->price ?? 0)) * 100) / 100;
            $wip_assy_price = ceil((($main->processCost->max_of_assy ?? 0) + ($wip_disc_price ?? 0) + ($wip_rim_price ?? 0)) * 100) / 100;
            $wip_cedW_price = ceil((($pr_cedW_price ?? 0) +  ($wip_assy_price ?? 0)) * 100) / 100;
            $wip_cedSR_price = ceil((($pr_cedSR_price ?? 0) +  ($wip_sidering_price ?? 0)) * 100) / 100;
            $wip_tcW_price = ceil((($wip_cedW_price ?? 0) + ($pr_tcW_price ?? 0)) * 100) / 100;
            $wip_tcSR_price = ceil((($wip_cedSR_price ?? 0) + ($pr_tcSR_price ?? 0)) * 100) / 100;
            if (isset($group->wip_valve) && $group->wip_valve->item_code === 'CGP089') {
                $wip_valve_price = 25815;
            } elseif (isset($group->wip_valve) && $group->wip_valve->item_code === 'CGP064') {
                $wip_valve_price = 14985;
            } elseif (isset($group->wip_valve) && $group->wip_valve->item_code === 'CGP090') {
                $wip_valve_price = 10000;
            } else {
                $wip_valve_price = null;
            };

            $data = [
                'item_code' => $main->item_code,

                // Disc
                'disc_qty' => $group->disc->qty ?? 0,
                'disc_code' => $group->disc->item_code ?? null,
                'disc_price' => $group->disc->materialInfo->price ?? null,

                // Rim
                'rim_qty' => $group->rim->qty ?? 0,
                'rim_code' => $group->rim->item_code ?? null,
                'rim_price' => $group->rim->materialInfo->price ?? null,

                // Sidering
                'sidering_qty' => $group->sidering->qty ?? 0,
                'sidering_code' => $group->sidering->item_code ?? null,
                'sidering_price' => $group->sidering->materialInfo->price ?? null,

                // pr_*
                'pr_disc' => $group->pr_disc->item_code ?? null,
                'pr_disc_price' => $main->processCost->max_of_disc ?? null,

                'pr_rim' => $group->pr_rim->item_code ?? null,
                'pr_rim_price' => $main->processCost->max_of_rim ?? null,

                'pr_sidering' => $group->pr_sr->item_code ?? null,
                'pr_sidering_price' => $main->processCost->max_of_sidering ?? null,

                'pr_assy' => $group->pr_assy->item_code ?? null,
                'pr_assy_price' => $main->processCost->max_of_assy ?? null,

                'pr_cedW' => $group->cedW->item_code ?? null,
                'pr_cedW_price' => $pr_cedW_price ?? null,

                'pr_cedSR' => $group->cedSR->item_code ?? null,
                'pr_cedSR_price' => $pr_cedSR_price ?? null,

                'pr_tcW' => $group->tcW->item_code ?? null,
                'pr_tcW_price' => $pr_tcW_price ?? null,

                'pr_tcSR' => $group->tcSR->item_code ?? null,
                'pr_tcSR_price' => $pr_tcSR_price ?? null,

                'pack_price' => $main->processCost->max_of_packaging ?? null,

                // WIP
                'wip_disc' => $group->wip_disc->item_code ?? null,
                'wip_disc_price' => $wip_disc_price,

                'wip_rim' => $group->wip_rim->item_code ?? null,
                'wip_rim_price' => $wip_rim_price,

                'wip_sidering' => $group->wip_sr->item_code ?? null,
                'wip_sidering_price' => $wip_sidering_price,

                'wip_assy' => $group->wip_assy->item_code ?? null,
                'wip_assy_price' => $wip_assy_price,

                'wip_cedW' => $group->wip_cedW->item_code ?? null,
                'wip_cedW_price' => $wip_cedW_price ?? null,

                'wip_cedSR' => $group->wip_cedSR->item_code ?? null,
                'wip_cedSR_price' => $wip_cedSR_price ?? null,

                'wip_tcW' => $group->wip_tcW->item_code ?? null,
                'wip_tcW_price' => $wip_tcW_price ?? null,

                'wip_tcSR' => $group->wip_tcSR->item_code ?? null,
                'wip_tcSR_price' => $wip_tcSR_price ?? null,

                'wip_valve' => $group->wip_valve->item_code ?? null,
                'wip_valve_price' => $wip_valve_price,
            ];

            // Hitung total cost
            $totalRawMaterial = collect([
                $data['disc_qty'] * $data['disc_price'],
                $data['rim_qty'] * $data['rim_price'],
                $data['sidering_qty'] * $data['sidering_price'],
            ])->sum();

            $totalProcess = collect([
                $data['pr_disc_price'],
                $data['pr_rim_price'],
                $data['pr_sidering_price'],
                $data['pr_assy_price'],
                $data['pr_cedW_price'],
                $data['pr_cedSR_price'],
                $data['pr_tcW_price'],
                $data['pr_tcSR_price'],
                $data['pack_price'],
            ])->sum();

            $data['total_raw_material'] = ceil($totalRawMaterial * 100) / 100;
            $data['total_process'] = ceil($totalProcess * 100) / 100;
            $data['total'] = $data['total_raw_material'] + $data['total_process'];

            // Simpan ke DB
            BOM_Report::updateOrCreate(
                ['item_code' => $main->item_code],
                $data
            );
        }
        return redirect()->route('bom.report');
    }
}
