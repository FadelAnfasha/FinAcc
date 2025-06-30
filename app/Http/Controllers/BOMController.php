<?php

namespace App\Http\Controllers;

use App\Models\BillOfMaterial;
use App\Models\BOM;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Material;
use App\Models\Process;
use App\Models\Packing;
use Illuminate\Support\Str;

class BOMController extends Controller
{
    public function master(Request $request)
    {
        $materials = Material::all();
        $bom = BillOfMaterial::where('depth', 1)->get();
        $packings = Packing::all();
        $processes = Process::all();

        $componentItems = collect();

        if ($request->has('component_id')) {
            $all = BillOfMaterial::orderBy('id')->get();
            $mainIndex = $all->search(fn($item) => $item->id == $request->component_id);

            if ($mainIndex !== false) {
                for ($i = $mainIndex + 1; $i < count($all); $i++) {
                    if ($all[$i]->depth == 1) break;
                    $componentItems->push($all[$i]);
                }
            }
        }

        return Inertia::render("bom/master", [
            'materials' => $materials,
            'packings' => $packings,
            'billOfMaterials' => $bom,
            'processes' => $processes,
            'component' => $componentItems, // ✅ dikirim ke frontend
        ]);
    }

    public function report(Request $request)
    {
        // Ambil semua data BOM yang sudah terurut
        $bomData = BillOfMaterial::all();

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

        // dump($group);
        return Inertia::render("bom/report", [
            'bom' => $groups->map(function ($group) {
                return [
                    'main' => $group->first(),
                    'items' => $group->values(),
                    'type_name' => $group->first()->type_name ?? null,
                    'disc' => $group->disc,
                    'rim' => $group->rim,
                    'sidering' => $group->sidering,
                    'pr_disc' => $group->pr_disc,
                    'pr_rim' => $group->pr_rim,
                    'pr_sr' => $group->pr_sr,
                    'pr_assy' => $group->pr_assy,
                    'ced_w' => $group->cedW,
                    'ced_sr' => $group->cedSR,
                    'tc_w' => $group->tcW,
                    'tc_sr' => $group->tcSR,

                ];
            }),
        ]);
    }
}
