<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

use App\Models\ActualCost;
use App\Models\ActualSalesQuantity;
use App\Models\DifferenceCost;
use App\Models\DiffCostXSalesQty;
use App\Models\StandardBillOfMaterial;
use App\Models\StandardCost;


class StandardBillOfMaterialController extends Controller
{
    public function import(Request $request)
    {
        // dd($request->all());
        $request->validate([
            'file' => 'required|mimes:csv,txt'
        ]);

        $file = $request->file('file');

        $addedItems = [];
        $invalidItems = [];
        $codeCounts = [];
        $nameCounts = [];

        $csvData = [];
        if (($handle = fopen($file->getRealPath(), 'r')) !== FALSE) {
            $delimiter = ';';

            $header = fgetcsv($handle, 1000, $delimiter);

            while (($row = fgetcsv($handle, 1000, $delimiter)) !== FALSE) {
                if (count($row) >= 3) {
                    $csvData[] = $row;
                } else {
                    $invalidItems[] = implode(';', $row) . ' (Invalid row format)';
                }
            }

            fclose($handle);
        } else {
            return redirect()->route('sc.master')->withErrors(['file' => 'Failed to open the CSV file.']);
        }

        $total = count($csvData);
        if ($total === 0) {
            return redirect()->route('sc.master')->withErrors(['file' => 'The CSV file is empty.']);
        }

        StandardBillOfMaterial::truncate();

        foreach ($csvData as $index => $row) {
            $itemCode = trim($row[0]);
            $description = trim($row[1]);

            $description = mb_convert_encoding($description, 'UTF-8', 'auto');
            $description = preg_replace('/[^a-zA-Z0-9\s.,()\-\_]/u', ' ', $description);
            $description = preg_replace('/\s+/', ' ', $description);

            StandardBillOfMaterial::create([
                'item_code' => $itemCode,
                'description' => $description,
                'uom' => $row[2],
                'quantity' => $row[3],
                'warehouse' => $row[4],
                'depth' => $row[7],
                'bom_type' => $row[8]
            ]);
            $addedItems[] = $itemCode;

            $progress = intval(($index + 1) / $total * 100);
            Cache::put('import-standard-bom-progress', $progress, now()->addMinutes(5));
        }

        Cache::put('import-standard-bom-progress', 100, now()->addMinutes(5));

        return redirect()->route('sc.master')->with([
            'success' => 'CSV import process completed!',
            'addedItems' => $addedItems,
            'invalidItems' => $invalidItems
        ]);
    }

    public function getPaginated(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $searchTerm = $request->input('search');
        $itemCodeFilter = $request->input('item_code_filter');
        $descriptionFilter = $request->input('description_filter');
        $sortField = $request->input('sort_field', 'item_code'); // Default sort field
        $sortOrder = $request->input('sort_order', 'asc');

        $bomPaginated = StandardBillOfMaterial::where('depth', 1)
            ->when($searchTerm, function ($query, $term) {
                $query->where(function ($q) use ($term) {
                    $q->where('item_code', 'LIKE', '%' . $term . '%');
                    $q->orWhere('description', 'LIKE', '%' . $term . '%');
                });
            })
            ->when($itemCodeFilter, function ($query, $term) {
                $query->where('item_code', 'LIKE', $term . '%');
            })
            ->when($descriptionFilter, function ($query, $term) {
                $query->where('description', 'LIKE', '%' . $term . '%');
            })
            ->orderBy($sortField, $sortOrder)

            ->paginate($perPage);

        return response()->json($bomPaginated);
    }

    public function getComponent(Request $request)
    {
        // Pastikan Anda mendapatkan ID dari query parameter (misal: /api/standard/get-component?id=123)
        $id = $request->input('id');

        if (!$id) {
            // Jika ID tidak ada, kembalikan response error atau kosong
            return response()->json(['error' => 'Component ID is required'], 400);
        }

        // Optimasi: Jika tabelnya besar, hindari fetch()->get() tanpa kriteria filter
        // Namun, jika struktur BOM Anda adalah tree yang di-flatten, pendekatan ini (fetch all) mungkin perlu dipertahankan.

        // 1. Ambil semua data
        $all = StandardBillOfMaterial::orderBy('id')->get();

        // 2. Cari index dari Item Finish Good yang diklik
        $mainIndex = $all->search(fn($item) => $item->id == $id);

        if ($mainIndex === false) {
            return response()->json([]);
        }

        $components = collect();

        // 3. Iterasi untuk mengambil semua komponen di bawahnya sampai bertemu 'depth' 1 lagi
        for ($i = $mainIndex + 1; $i < count($all); $i++) {
            // Asumsi: depth 1 adalah item level tertinggi (finish good)
            if ($all[$i]->depth == 1) break;

            $components->push($all[$i]);
        }

        // $finishGood = $all[$mainIndex]; // Ini tidak perlu di-return, tapi bagus untuk referensi

        // Mengembalikan data komponen
        return response()->json($components);
    }

    public function getStructuredBOM(Request $request)
    {
        $bomData = StandardBillOfMaterial::all();
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

        $finalReportData = collect();

        foreach ($groups as $group) {
            $main = $group->first();
            $typeChar = substr($main->item_code, 3, 1);
            $group->description = $main->description ?? '-';

            // Properti raw material
            $group->disc = $group->first(function ($item) {
                return substr($item->item_code, 0, 3) === 'RFD';
            });
            $group->rim = $group->first(function ($item) {
                return substr($item->item_code, 0, 3) === 'RFR';
            });
            $group->sidering = $group->first(function ($item) {
                return substr($item->item_code, 0, 3) === 'RFS';
            });

            // Properti process
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

            // Logika filter process CED
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
                    $group->cedW = $cdItems[0];
                    $group->cedSR = null;
                }
            } else {
                $group->cedW = null;
                $group->cedSR = null;
            }

            // Logika filter process TC
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
                    $group->tcW = $tcItems[0];
                    $group->tcSR = null;
                }
            } else {
                $group->tcW = null;
                $group->tcSR = null;
            }

            $group->pr_ta = $group->first(function ($item) {
                return substr($item->item_code, 0, 5) === 'RS-TA';
            });

            // Logika filter WIP
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

            $group->wip_tyre = $group->first(function ($item) {
                return substr($item->item_code, 0, 3) === 'CGP'
                    && (
                        stripos($item->description, 'TYRE') !== false
                        || stripos($item->description, 'tyre') !== false
                    );
            });

            $data = [
                'item_code' => $main->item_code,
                'description' => $group->description,

                // Raw Material
                'disc_qty' => $group->disc->quantity ?? 0,
                'disc_code' => $group->disc->item_code ?? '-',

                'rim_qty' => $group->rim->quantity ?? 0,
                'rim_code' => $group->rim->item_code ?? '-',

                'sidering_qty' => $group->sidering->quantity ?? 0,
                'sidering_code' => $group->sidering->item_code ?? '-',

                // Process 
                'pr_disc' => $group->pr_disc->item_code ?? '-',

                'pr_rim' => $group->pr_rim->item_code ?? '-',

                'pr_sidering' => $group->pr_sr->item_code ?? '-',

                'pr_assy' => $group->pr_assy->item_code ?? '-',

                'pr_cedW' => $group->cedW->item_code ?? '-',

                'pr_cedSR' => $group->cedSR->item_code ?? '-',

                'pr_tcW' => $group->tcW->item_code ?? '-',

                'pr_tcSR' => $group->tcSR->item_code ?? '-',

                'pr_TA' => $group->pr_ta->item_code ?? '-',

                // WIP
                'wip_disc' => $group->wip_disc->item_code ?? '-',

                'wip_rim' => $group->wip_rim->item_code ?? '-',

                'wip_sidering' => $group->wip_sr->item_code ?? '-',

                'wip_assy' => $group->wip_assy->item_code ?? '-',

                'wip_cedW' => $group->wip_cedW->item_code ?? '-',

                'wip_cedSR' => $group->wip_cedSR->item_code ?? '-',

                'wip_tcW' => $group->wip_tcW->item_code ?? '-',

                'wip_tcSR' => $group->wip_tcSR->item_code ?? '-',

                'wip_valve' => $group->wip_valve->item_code ?? '-',

                'wip_tyre' => $group->wip_tyre->item_code ?? '-',

            ];

            $finalReportData->push($data);
        }
        return response()->json($finalReportData);
    }
}
