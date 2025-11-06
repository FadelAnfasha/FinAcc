<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

use App\Models\ActualCost;
use App\Models\ActualMaterial;
use App\Models\ActualSalesQuantity;
use App\Models\Valve;
use App\Models\ProcessCost;
use App\Models\BillOfMaterial;


class ActualCostController extends Controller
{
    public function update(Request $request)
    {
        $validatedData = $request->validate([
            'startMonth' => 'required|integer|min:1|max:12', // Angka bulan
            'endMonth' => 'required|integer|min:1|max:12',   // Angka bulan
            'year' => 'required|integer',
        ]);

        $year = $validatedData['year'];
        $month = $validatedData['endMonth'];

        $startMonthNumber = $validatedData['startMonth'];
        $endMonthNumber = $validatedData['endMonth'];

        $monthOrder = [
            'jan',
            'feb',
            'mar',
            'apr',
            'may',
            'jun',
            'jul',
            'aug',
            'sep',
            'oct',
            'nov',
            'dec',
        ];

        $convertToMMM = function ($monthNumber) {
            $dateObj = \DateTime::createFromFormat('!m', $monthNumber);
            return strtolower($dateObj->format('M'));
        };

        // Terapkan konversi ke format MMM
        $startMonth = $convertToMMM($startMonthNumber);
        $endMonth = $convertToMMM($endMonthNumber);

        // Tentukan kolom yang akan di-select secara dinamis
        $startIndex = array_search($startMonth, $monthOrder);
        $endIndex = array_search($endMonth, $monthOrder);
        $monthSlice = array_slice($monthOrder, $startIndex, $endIndex - $startIndex + 1);

        if ($startMonth !== $endMonth) {
            $period = "YTD-" . ucfirst($endMonth) . "'" . $year;
        } else {
            $period = ucfirst($startMonth) . "'" . $year;
        }

        $monthlyColumns = [];
        foreach ($monthSlice as $month) {
            $monthlyColumns[] = $month . '_price';
            $monthlyColumns[] = $month . '_qty';
        }

        // Pastikan item_code dan foreign key ada di selectColumns
        $selectColumns = array_merge(['item_code'], $monthlyColumns);

        $materialPrices = ActualMaterial::select($selectColumns)->get();
        $calculatedPrices = collect();
        foreach ($materialPrices as $material) {
            $totalAmount = 0; // Total Harga
            $totalQuantity = 0; // Total Kuantitas (penyebut)

            // 3. Iterasi dinamis pada pasangan kolom bulan
            foreach ($monthSlice as $month) {
                $priceKey = $month . '_price';
                $qtyKey = $month . '_qty';
                // dump($priceKey, $qtyKey);

                // Ambil nilai harga dan kuantitas. Gunakan null-coalescing untuk menghindari error jika data null.
                // Konversi ke float/int untuk memastikan perhitungan yang benar.
                $price = (float)($material->{$priceKey} ?? 0);
                $qty = (float)($material->{$qtyKey} ?? 0);

                // Tambahkan ke total
                $totalAmount += $price;
                $totalQuantity += $qty;
            }
            // dump($totalAmount, $totalQuantity);


            $weightedAveragePrice = 0;

            // 4. Hitung Rata-Rata Harga Tertimbang
            if ($totalQuantity > 0) {
                $weightedAveragePrice = $totalAmount / $totalQuantity;
            }
            $priceMap = $calculatedPrices->keyBy('item_code');
            // Masukkan hasil perhitungan ke koleksi baru
            $calculatedPrices->push([
                'item_code' => $material->item_code,
                'avg_price' => round($weightedAveragePrice, 4), // Bulatkan untuk kerapihan
            ]);
        }

        // Ambil data BOM
        $bomData = BillOfMaterial::all();

        // Grouping Logika (Disimpan sama)
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

        foreach ($groups as $group) {
            $main = $group->first();
            $typeChar = substr($main->item_code, 3, 1);

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
                    // Perbaikan: Jika hanya ada satu item RS-CD dan typeChar bukan 'D' atau 'N' (misal 'W'),
                    // asumsikan itu adalah cedW.
                    $group->cedW = $cdItems[0];
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
                    $group->tcW = $tcItems[0];
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
            $main->load(['processCost', 'actualMaterial']);

            $disc_item_code = $group->disc?->actualMaterial?->item_code;
            $disc_avg_price = $disc_item_code ? ($priceMap[$disc_item_code]['avg_price'] ?? 0) : 0;
            $disc_price = ceil(($disc_avg_price * ($group->disc?->quantity ?? 0)) * 100) / 100;

            $rim_item_code = $group->rim?->actualMaterial?->item_code;
            $rim_avg_price = $rim_item_code ? ($priceMap[$rim_item_code]['avg_price'] ?? 0) : 0;
            $rim_price = ceil(($rim_avg_price * ($group->rim?->quantity ?? 0)) * 100) / 100;

            $sidering_item_code = $group->sidering?->actualMaterial?->item_code;
            $sidering_avg_price = $sidering_item_code ? ($priceMap[$sidering_item_code]['avg_price'] ?? 0) : 0;
            $sidering_price = ceil(($sidering_avg_price * ($group->sidering?->quantity ?? 0)) * 100) / 100;


            // $disc_price = ceil((($group->disc->actualMaterial->price ?? 0) * $group->disc?->quantity ?? 0) * 100) / 100;
            // $rim_price = ceil((($group->rim->actualMaterial->price ?? 0) * $group->rim?->quantity ?? 0) * 100) / 100;
            // $sidering_price = ceil((($group->sidering->actualMaterial->price ?? 0) * $group->sidering?->quantity ?? 0) * 100) / 100;

            // $disc_price = $group->disc->materialInfo->standardPrice ?? 0;
            // $rim_price = $group->rim->materialInfo->standardPrice ?? 0;
            // $sidering_price = $group->sidering->materialInfo->standardPrice ?? 0;

            // Perbaikan untuk pr_cedW_price
            $pr_cedW_price = 0;
            // Hitung jika cedW item ada DAN item_code-nya bukan '-' atau kosong
            if ($group->cedW && ($group->cedW->item_code !== '-' && !empty($group->cedW->item_code))) {
                $maxOfCed = $main->processCost->max_of_ced ?? 0;
                $pr_cedW_price = ceil(($maxOfCed * 5 / 7) * 100) / 100;
            }

            // Perbaikan untuk pr_cedSR_price
            $pr_cedSR_price = 0;
            // Hitung jika cedSR item ada DAN item_code-nya bukan '-' atau kosong
            if ($group->cedSR && ($group->cedSR->item_code !== '-' && !empty($group->cedSR->item_code))) {
                $maxOfCed = $main->processCost->max_of_ced ?? 0;
                $pr_cedSR_price = ceil(($maxOfCed * 2 / 7) * 100) / 100;
            }

            // $pr_tcW_price = ceil(((($main->processCost->max_of_topcoat ?? null) * 5) / 7) * 100) / 100;

            // $pr_tcW_price = 0;
            // // Hitung jika tcW item ada DAN item_code-nya bukan '-' atau kosong
            // if ($group->tcW && ($group->tcW->item_code !== '-' && !empty($group->tcW->item_code))) {
            //     $maxOfTC = $main->processCost->max_of_topcoat ?? 0;
            //     $pr_tcW_price = ceil(($maxOfTC * 5 / 7) * 100) / 100;
            // }

            // // $pr_tcSR_price = ceil(((($main->processCost->max_of_topcoat ?? null) * 2) / 7) * 100) / 100;
            // $pr_tcSR_price = 0;
            // // Hitung jika tcSR item ada DAN item_code-nya bukan '-' atau kosong
            // if ($group->tcSR && ($group->tcSR->item_code !== '-' && !empty($group->tcSR->item_code))) {
            //     $maxOfTC = $main->processCost->max_of_topcoat ?? 0;
            //     $pr_tcSR_price = ceil(($maxOfTC * 2 / 7) * 100) / 100;
            // }

            // --- Perbaikan untuk pr_tcW_price dan pr_tcSR_price ---
            $pr_tcW_price = 0;
            $pr_tcSR_price = 0;
            $maxOfTopcoat = $main->processCost->max_of_topcoat ?? 0;

            $tcWExists = $group->tcW && ($group->tcW->item_code !== '-' && !empty($group->tcW->item_code));
            $tcSRExists = $group->tcSR && ($group->tcSR->item_code !== '-' && !empty($group->tcSR->item_code));

            if ($tcWExists && $tcSRExists) {
                // Kasus 1: Keduanya ada
                $pr_tcW_price = ceil(($maxOfTopcoat * 5 / 7) * 100) / 100;
                $pr_tcSR_price = ceil(($maxOfTopcoat * 2 / 7) * 100) / 100;
            } elseif ($tcWExists && !$tcSRExists) {
                // Kasus 2: Hanya tcW yang ada
                $pr_tcW_price = ceil(($maxOfTopcoat * 1) * 100) / 100; // 7/7 = 1
                $pr_tcSR_price = 0;
            } elseif (!$tcWExists && $tcSRExists) {
                // Kasus 3: Hanya tcSR yang ada
                $pr_tcW_price = 0;
                $pr_tcSR_price = ceil(($maxOfTopcoat * 1) * 100) / 100; // 7/7 = 1
            }

            $wip_disc_price = ceil((($main->processCost->max_of_disc ?? 0) + ($disc_price ?? 0)) * 100) / 100;
            $wip_rim_price = ceil((($main->processCost->max_of_rim ?? 0) + ($rim_price ?? 0)) * 100) / 100;
            $wip_sidering_price = ceil((($main->processCost->max_of_sidering ?? 0) + ($sidering_price ?? 0)) * 100) / 100;
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
                $wip_valve_price = 0;
            };

            $data = [
                'item_code' => $main->item_code,

                // Disc
                'disc_qty' => $group->disc->quantity ?? 0,
                'disc_code' => $group->disc->item_code ?? '-',
                'disc_price' => $disc_price ?? 0,

                // Rim
                'rim_qty' => $group->rim->quantity ?? 0,
                'rim_code' => $group->rim->item_code ?? '-',
                'rim_price' => $rim_price ?? 0,

                // Sidering
                'sidering_qty' => $group->sidering->quantity ?? 0,
                'sidering_code' => $group->sidering->item_code ?? '-',
                'sidering_price' => $sidering_price ?? 0,

                // pr_*
                'pr_disc' => $group->pr_disc->item_code ?? '-',
                'pr_disc_price' => $main->processCost->max_of_disc ?? 0,

                'pr_rim' => $group->pr_rim->item_code ?? '-',
                'pr_rim_price' => $main->processCost->max_of_rim ?? 0,

                'pr_sidering' => $group->pr_sr->item_code ?? '-',
                'pr_sidering_price' => $main->processCost->max_of_sidering ?? 0,

                'pr_assy' => $group->pr_assy->item_code ?? '-',
                'pr_assy_price' => $main->processCost->max_of_assy ?? 0,

                'pr_cedW' => $group->cedW->item_code ?? '-',
                'pr_cedW_price' => $pr_cedW_price ?? 0,

                'pr_cedSR' => $group->cedSR->item_code ?? '-',
                'pr_cedSR_price' => $pr_cedSR_price ?? 0,

                'pr_tcW' => $group->tcW->item_code ?? '-',
                'pr_tcW_price' => $pr_tcW_price ?? 0,

                'pr_tcSR' => $group->tcSR->item_code ?? '-',
                'pr_tcSR_price' => $pr_tcSR_price ?? 0,

                'pack_price' => $main->processCost->max_of_packaging ?? 0,

                // WIP
                'wip_disc' => $group->wip_disc->item_code ?? '-',
                'wip_disc_price' => $wip_disc_price ?? 0,

                'wip_rim' => $group->wip_rim->item_code ?? '-',
                'wip_rim_price' => $wip_rim_price ?? 0,

                'wip_sidering' => $group->wip_sr->item_code ?? '-',
                'wip_sidering_price' => $wip_sidering_price ?? 0,

                'wip_assy' => $group->wip_assy->item_code ?? '-',
                'wip_assy_price' => $wip_assy_price ?? 0,

                'wip_cedW' => $group->wip_cedW->item_code ?? '-',
                'wip_cedW_price' => $wip_cedW_price ?? 0,

                'wip_cedSR' => $group->wip_cedSR->item_code ?? '-',
                'wip_cedSR_price' => $wip_cedSR_price ?? 0,

                'wip_tcW' => $group->wip_tcW->item_code ?? '-',
                'wip_tcW_price' => $wip_tcW_price ?? 0,

                'wip_tcSR' => $group->wip_tcSR->item_code ?? '-',
                'wip_tcSR_price' => $wip_tcSR_price ?? 0,

                'wip_valve' => $group->wip_valve->item_code ?? '-',
                'wip_valve_price' => $group->wip_valve->valveInfo->price ?? 0,
            ];

            // Hitung total cost
            $totalRawMaterial = collect([
                $data['disc_price'],
                $data['rim_price'],
                $data['sidering_price'],
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
                $data['wip_valve_price']
            ])->sum();

            $data['total_raw_material'] = ceil($totalRawMaterial * 100) / 100;
            $data['total_process'] = ceil($totalProcess * 100) / 100;
            $data['total'] = $data['total_raw_material'] + $data['total_process'];

            // Simpan ke DB
            ActualCost::updateOrCreate(
                [
                    'item_code' => $main->item_code,
                    'period' => $period,
                ],
                $data
            );
        }

        return redirect()->route('ac.report');
    }

    public function preview(Request $request, string $item_code)
    {
        // $previewData = ActualCost::where('item_code', $item_code)->with('bom')->first();
        $opex = $request->query('opex');
        $progin = $request->query('progin');
        $previewData = ActualCost::where('item_code', $item_code)
            ->with([
                'bom', // Deskripsi utama F15W02
                'discWIP',
                'discMaterial',
                'discProcess',
                'rimWIP',
                'rimMaterial',
                'rimProcess',
                'sideringWIP',
                'sideringMaterial',
                'sideringProcess',
            ])
            ->first();

        if (!$previewData) {
            abort(404, 'Data BOM Report tidak ditemukan untuk Item Code: ' . $item_code);
        }

        $createPeriodsData = function ($qty, $price, $total, $columnIndex = 0) {
            $periods = array_fill(0, 4, ['qty' => '', 'price' => '', 'total' => '']); // 4 kolom kosong

            $validColumnIndex = max(0, min(3, $columnIndex));

            // Perhatikan perubahan kondisi di sini: hanya perlu salah satu tidak null
            if ($qty !== null || $price !== null || $total !== null) {
                $periods[$validColumnIndex] = [
                    'qty' => ($qty !== null) ? number_format($qty, 2, ',', '.') : '',
                    'price' => ($price !== null) ? number_format($price, 0, ',', '.') : '',
                    'total' => ($total !== null) ? number_format($total, 0, ',', '.') : '',
                ];
            }
            return $periods;
        };

        $cycleTimeAttributes = [
            'blanking',
            'spinDisc',
            'autoDisc',
            'manualDisc',
            'discLathe',

            'rim1',
            'rim2',
            'rim3',

            'coiler',
            'forming',

            'assy1',
            'assy2',
            'machining',
            'shotPeening',

            'ced',
            'topcoat',

            'packing_dom',
            'packing_exp',
        ];

        // ===========================================
        // PERHITUNGAN TOTAL CYCLE TIME BARU
        // ===========================================
        $totalCycleTime = 0;
        $totalCTData = $previewData->ct; // Mengambil objek CT data

        if ($totalCTData) { // Pastikan objek $previewData->ct itu sendiri tidak null
            foreach ($cycleTimeAttributes as $attribute) {
                // Karena Anda menjamin tidak ada nilai null, kita bisa langsung mengakses properti
                // Tapi tetap baik untuk memastikan properti ada, meskipun nilainya 0
                if (isset($totalCTData->{$attribute})) {
                    $totalCycleTime += $totalCTData->{$attribute};
                }
            }
        }
        // ===========================================
        // SEGMENT: FINISH GOOD 
        // ===========================================

        $packingChildren = [];
        $fgItems = [];

        if ($previewData->pack_price > 0) {
            $packProcessLines = [];
            $packLinesMapping = [
                'pack' => ['ct_prop' => 'pack_ct'],
            ];

            $cycleTimeData = $previewData->ct;

            if ($cycleTimeData) {
                foreach ($packLinesMapping as $lineKey => $props) {

                    if (isset($cycleTimeData->pack_ct_dom) && $cycleTimeData->pack_ct_dom == 0) {
                        $cycleTimeValue = $cycleTimeData->packing_exp ?? null;
                    } else {
                        $cycleTimeValue = $cycleTimeData->packing_dom ?? null;
                    }


                    if ($cycleTimeValue > 0) {
                        $displayName = 'Packing';
                        $packProcessLines[] = [
                            'name' => $displayName,
                            'cycle_time' => ($cycleTimeValue !== null) ? number_format($cycleTimeValue, 1, ',', '.') : ''
                        ];
                    }
                }
            }

            $packingChildren[] = [
                'description' => 'Packing',
                'type' => 'PR',
                'lines' => $packProcessLines,
                'periods_data' => $createPeriodsData(
                    1.0,
                    $previewData->pack_price ?? null,
                    ($previewData->pack_price ?? 0) * 1.0,
                    3
                ),
                'level' => 1
            ];
        }

        if (($previewData->wip_valve_price ?? 0) > 0) {
            $packingChildren[] = [
                'description' => 'Valve',
                'type' => 'PR',
                'periods_data' => $createPeriodsData(
                    1.0,
                    $previewData->wip_valve_price ?? null,
                    ($previewData->wip_valve_price ?? 0) * 1.0,
                    3
                ),
                'level' => 1
            ];
        }

        if (!empty(trim($previewData->item_code))) {
            if ($previewData->item_code !== '-' && (!empty($packingChildren) || (isset($previewData->total) && ($previewData->total ?? 0) > 0))) {
                $fgItems[] = [
                    'item_code' => $previewData->item_code,
                    'description' => $previewData->bom->description ?? null,
                    'type' => 'PR',
                    'wip_info' => 'Finish Good',
                    'periods_data' => $createPeriodsData(
                        null,
                        null,
                        $previewData->total ?? null,
                        3
                    ),
                    'level' => 0,
                    'children' => $packingChildren
                ];
            }
        }
        // ===========================================
        // SEGMENT: PAINTING 
        // ===========================================
        $topcoatItems = [];

        $tcWChildren = [];
        if ($previewData->pr_tcW !== '-' && $previewData->pr_tcW !== null) {
            $tcWProcessLines = [];
            $tcWLinesMapping = [
                'topcoat' => ['eff_prop' => 'topcoat_eff', 'ct_prop' => 'topcoat'],
            ];

            $cycleTimeData = $previewData->ct;

            if ($cycleTimeData) {
                foreach ($tcWLinesMapping as $lineKey => $props) {
                    $efficiencyProp = $props['eff_prop'];
                    $cycleTimeProp = $props['ct_prop'];

                    $efficiency = $cycleTimeData->{$efficiencyProp} ?? 0;

                    $cycleTimeValue = ($cycleTimeData->{$cycleTimeProp} ?? 0) * 5 / 7;

                    $formattedPercentage = number_format($efficiency * 100, 0);

                    if ($efficiency > 0) {
                        $displayName = ucfirst($lineKey);

                        $tcWProcessLines[] = [
                            'name' => $displayName,
                            'percentage' => $formattedPercentage . '%',
                            'cycle_time' => ($cycleTimeValue !== null) ? number_format($cycleTimeValue, 1, ',', '.') : ''
                        ];
                    }
                }
            }

            $tcWChildren[] = [
                'item_code' => $previewData->pr_tcW->item_code ?? $previewData->pr_tcW,
                'description' => $previewData->tcWProcess->description ?? 'N/A',
                'type' => 'PR',
                'lines' => $tcWProcessLines,
                'periods_data' => $createPeriodsData(
                    1.0,
                    $previewData->pr_tcW_price ?? null,
                    $previewData->pr_tcW_price ?? null,
                    2
                ),
                'level' => 1
            ];
        }
        if (!empty(trim($previewData->wip_tcW))) {

            if ($previewData->wip_tcW !== '-' && (!empty($tcWChildren) || (isset($previewData->wip_tcW_price) && $previewData->wip_tcW_price !== null && $previewData->wip_tcW_price >= 0))) {
                $topcoatItems[] = [
                    'item_code' => $previewData->wip_tcW,
                    'description' => $previewData->tcWWIP->description ?? null,
                    'type' => 'PR',
                    'wip_info' => 'tcW',
                    'periods_data' => $createPeriodsData(
                        null,
                        null,
                        $previewData->wip_tcW_price ?? null,
                        2
                    ),
                    'level' => 0,
                    'children' => $tcWChildren
                ];
            }
        }

        $tcSRChildren = [];
        if ($previewData->pr_tcSR !== '-' && $previewData->pr_tcSR !== null) {
            $tcSRProcessLines = [];
            $tcSRLinesMapping = [
                'topcoat' => ['eff_prop' => 'topcoat_eff', 'ct_prop' => 'topcoat'],
            ];

            $cycleTimeData = $previewData->ct;

            if ($cycleTimeData) {
                foreach ($tcSRLinesMapping as $lineKey => $props) {
                    $efficiencyProp = $props['eff_prop'];
                    $cycleTimeProp = $props['ct_prop'];

                    $efficiency = $cycleTimeData->{$efficiencyProp} ?? 0;

                    $cycleTimeValue = ($cycleTimeData->{$cycleTimeProp} ?? 0) * 2 / 7;

                    $formattedPercentage = number_format($efficiency * 100, 0);

                    if ($efficiency > 0) {
                        $displayName = ucfirst($lineKey);

                        $tcSRProcessLines[] = [
                            'name' => $displayName,
                            'percentage' => $formattedPercentage . '%',
                            'cycle_time' => ($cycleTimeValue !== null) ? number_format($cycleTimeValue, 1, ',', '.') : ''
                        ];
                    }
                }
            }

            $tcSRChildren[] = [
                'item_code' => $previewData->pr_tcSR->item_code ?? $previewData->pr_tcSR,
                'description' => $previewData->tcSRProcess->description ?? 'N/A',
                'type' => 'PR',
                'lines' => $tcSRProcessLines,
                'periods_data' => $createPeriodsData(
                    1.0,
                    $previewData->pr_tcSR_price ?? null,
                    $previewData->pr_tcSR_price ?? null,
                    2
                ),
                'level' => 1
            ];
        }
        if (!empty(trim($previewData->wip_tcSR))) {

            if ($previewData->wip_tcSR !== '-' && (!empty($tcSRChildren) || (isset($previewData->wip_tcSR_price) && $previewData->wip_tcSR_price !== null && $previewData->wip_tcSR_price >= 0))) {
                $topcoatItems[] = [
                    'item_code' => $previewData->wip_tcSR,
                    'description' => $previewData->tcSRWIP->description ?? null,
                    'type' => 'PR',
                    'wip_info' => 'tcSR',
                    'periods_data' => $createPeriodsData(
                        null,
                        null,
                        $previewData->wip_tcSR_price ?? null,
                        2
                    ),
                    'level' => 0,
                    'children' => $tcSRChildren
                ];
            }
        }


        $cedItems = [];
        $cedWChildren = [];
        if ($previewData->pr_cedW !== '-' && $previewData->pr_cedW !== null) {
            $cedWProcessLines = [];
            $cedWLinesMapping = [
                'ced' => ['eff_prop' => 'ced_eff', 'ct_prop' => 'ced'],
            ];

            $cycleTimeData = $previewData->ct;

            if ($cycleTimeData) {
                foreach ($cedWLinesMapping as $lineKey => $props) {
                    $efficiencyProp = $props['eff_prop'];
                    $cycleTimeProp = $props['ct_prop'];

                    $efficiency = $cycleTimeData->{$efficiencyProp} ?? 0;

                    $cycleTimeValue = ($cycleTimeData->{$cycleTimeProp} ?? 0) * 5 / 7;

                    $formattedPercentage = number_format($efficiency * 100, 0);

                    if ($efficiency > 0) {
                        $displayName = ucfirst($lineKey);

                        $cedWProcessLines[] = [
                            'name' => $displayName,
                            'percentage' => $formattedPercentage . '%',
                            'cycle_time' => ($cycleTimeValue !== null) ? number_format($cycleTimeValue, 1, ',', '.') : ''
                        ];
                    }
                }
            }

            $cedWChildren[] = [
                'item_code' => $previewData->pr_cedW->item_code ?? $previewData->pr_cedW,
                'description' => $previewData->cedWProcess->description ?? 'N/A',
                'type' => 'PR',
                'lines' => $cedWProcessLines,
                'periods_data' => $createPeriodsData(
                    1.0,
                    $previewData->pr_cedW_price ?? null,
                    $previewData->pr_cedW_price ?? null,
                    2
                ),
                'level' => 1
            ];
        }

        if (!empty(trim($previewData->wip_cedW))) {
            if ($previewData->wip_cedW !== '-' && (!empty($cedWChildren) || (isset($previewData->wip_cedW_price) && $previewData->wip_cedW_price !== null && $previewData->wip_cedW_price >= 0))) {
                $cedItems[] = [
                    'item_code' => $previewData->wip_cedW,
                    'description' => $previewData->cedWWIP->description ?? null,
                    'type' => 'PR',
                    'wip_info' => 'cedW',
                    'periods_data' => $createPeriodsData(
                        null,
                        null,
                        $previewData->wip_cedW_price ?? null,
                        2
                    ),
                    'level' => 0,
                    'children' => $cedWChildren
                ];
            }
        }


        $cedSRChildren = [];
        if ($previewData->pr_cedSR !== '-' && $previewData->pr_cedSR !== null) {
            $cedSRProcessLines = [];
            $cedSRLinesMapping = [
                'ced' => ['eff_prop' => 'ced_eff', 'ct_prop' => 'ced'],
            ];

            $cycleTimeData = $previewData->ct;

            if ($cycleTimeData) {
                foreach ($cedSRLinesMapping as $lineKey => $props) {
                    $efficiencyProp = $props['eff_prop'];
                    $cycleTimeProp = $props['ct_prop'];

                    $efficiency = $cycleTimeData->{$efficiencyProp} ?? 0;

                    $cycleTimeValue = ($cycleTimeData->{$cycleTimeProp} ?? 0) * 2 / 7;

                    $formattedPercentage = number_format($efficiency * 100, 0);

                    if ($efficiency > 0) {
                        $displayName = ucfirst($lineKey);

                        $cedSRProcessLines[] = [
                            'name' => $displayName,
                            'percentage' => $formattedPercentage . '%',
                            'cycle_time' => ($cycleTimeValue !== null) ? number_format($cycleTimeValue, 1, ',', '.') : ''
                        ];
                    }
                }
            }

            $cedSRChildren[] = [
                'item_code' => $previewData->pr_cedSR->item_code ?? $previewData->pr_cedSR,
                'description' => $previewData->cedSRProcess->description ?? 'N/A',
                'type' => 'PR',
                'lines' => $cedSRProcessLines,
                'periods_data' => $createPeriodsData(
                    1.0,
                    $previewData->pr_cedSR_price ?? null,
                    $previewData->pr_cedSR_price ?? null,
                    2
                ),
                'level' => 1
            ];
        }

        if (!empty(trim($previewData->wip_cedSR))) {

            if ($previewData->wip_cedSR !== '-' && (!empty($cedSRChildren) || (isset($previewData->wip_cedSR_price) && $previewData->wip_cedSR_price !== null && $previewData->wip_cedSR_price >= 0))) {
                $cedItems[] = [
                    'item_code' => $previewData->wip_cedSR,
                    'description' => $previewData->cedSRWIP->description ?? null,
                    'type' => 'PR',
                    'wip_info' => 'cedSR',
                    'periods_data' => $createPeriodsData(
                        null,
                        null,
                        $previewData->wip_cedSR_price ?? null,
                        2
                    ),
                    'level' => 0,
                    'children' => $cedSRChildren
                ];
            }
        }

        // ===========================================
        // SEGMENT: ASSEMBLY 
        // ===========================================
        $assyItems = [];
        $assyChildren = [];
        if ($previewData->pr_assy !== '-' && $previewData->pr_assy !== null) {
            $assyProcessLines = [];
            $assyLinesMapping = [
                'assy1'   => ['eff_prop' => 'assy1_eff', 'ct_prop' => 'assy1'],
                'assy2'   => ['eff_prop' => 'assy2_eff', 'ct_prop' => 'assy2'],
                'machining'   => ['eff_prop' => 'machining_eff', 'ct_prop' => 'machining'],
                'shotPeening'   => ['eff_prop' => 'shotPeening_eff', 'ct_prop' => 'shotPeening'],
            ];

            $cycleTimeData = $previewData->ct;

            if ($cycleTimeData) {
                foreach ($assyLinesMapping as $lineKey => $props) {
                    $efficiencyProp = $props['eff_prop'];
                    $cycleTimeProp = $props['ct_prop'];

                    $efficiency = $cycleTimeData->{$efficiencyProp} ?? 0;

                    $cycleTimeValue = $cycleTimeData->{$cycleTimeProp} ?? 0;

                    $formattedPercentage = number_format($efficiency * 100, 0);

                    if ($efficiency > 0) {
                        $displayName = ucfirst($lineKey);

                        $assyProcessLines[] = [
                            'name' => $displayName,
                            'percentage' => $formattedPercentage . '%',
                            'cycle_time' => ($cycleTimeValue !== null) ? number_format($cycleTimeValue, 1, ',', '.') : ''
                        ];
                    }
                }
            }

            $assyChildren[] = [
                'item_code' => $previewData->pr_assy->item_code ?? $previewData->pr_assy,
                'description' => $previewData->assyProcess->description ?? 'N/A',
                'type' => 'PR',
                'lines' => $assyProcessLines,
                'periods_data' => $createPeriodsData(
                    1.0,
                    $previewData->pr_assy_price ?? null,
                    $previewData->pr_assy_price ?? null,
                    1
                ),
                'level' => 1
            ];
        }
        if (!empty(trim($previewData->wip_assy))) {

            if ($previewData->wip_assy !== '-' && (!empty($assyChildren) || (isset($previewData->wip_assy_price) && $previewData->wip_assy_price !== null && $previewData->wip_assy_price >= 0))) {
                $assyItems[] = [
                    'item_code' => $previewData->wip_assy,
                    'description' => $previewData->assyWIP->description ?? null,
                    'type' => 'PR',
                    'wip_info' => 'Assy',
                    'periods_data' => $createPeriodsData(
                        null,
                        null,
                        $previewData->wip_assy_price ?? null,
                        1
                    ),
                    'level' => 0,
                    'children' => $assyChildren
                ];
            }
        }

        // ===========================================
        // SEGMENT: PROCESS
        // ===========================================
        $processItems = [];
        #Sidering
        $sideringChildren = [];
        if ($previewData->sidering_code !== null && $previewData->sidering_code !== '-') {
            $sideringChildren[] = [
                'item_code' => $previewData->sidering_code,
                'description' => $previewData->sideringMaterial->description ?? null,
                'type' => 'RM',
                'periods_data' => $createPeriodsData(
                    $previewData->sidering_qty ?? null,
                    $previewData->sidering_price ?? null,
                    $previewData->sidering_price ?? null,
                    0
                ),
                'level' => 1
            ];
        }

        if ($previewData->pr_sidering !== '-' || $previewData->pr_sidering !== null) {
            $sideringProcessLines = [];
            $sideringLinesMapping = [
                'coiler' => ['eff_prop' => 'coiler_eff', 'ct_prop' => 'coiler'],
                'forming' => ['eff_prop' => 'forming_eff', 'ct_prop' => 'forming'],
            ];

            $cycleTimeData = $previewData->ct;

            if ($cycleTimeData) {
                foreach ($sideringLinesMapping as $lineKey => $props) {
                    $efficiencyProp = $props['eff_prop'];
                    $cycleTimeProp = $props['ct_prop'];

                    $efficiency = $cycleTimeData->{$efficiencyProp} ?? 0;
                    $cycleTimeValue = $cycleTimeData->{$cycleTimeProp} ?? null;

                    $formattedPercentage = number_format($efficiency * 100, 0);

                    if ($efficiency > 0) {
                        $displayName = ucfirst($lineKey);

                        $sideringProcessLines[] = [
                            'name' => $displayName,
                            'percentage' => $formattedPercentage . '%',
                            'cycle_time' => ($cycleTimeValue !== null) ? number_format($cycleTimeValue, 1, ',', '.') : ''
                        ];
                    }
                }
            }

            $sideringChildren[] = [
                'item_code' => $previewData->pr_sidering->item_code ?? $previewData->pr_sidering,
                'description' => $previewData->sideringProcess->description ?? 'N/A',
                'type' => 'PR',
                'lines' => $sideringProcessLines,
                'periods_data' => $createPeriodsData(
                    1.0,
                    $previewData->pr_sidering_price ?? null,
                    $previewData->pr_sidering_price ?? null,
                    0
                ),
                'level' => 1
            ];
        }

        if (!empty(trim($previewData->wip_sidering))) {
            if (
                ($previewData->wip_sidering === '-' && isset($previewData->wip_sidering_price) && $previewData->wip_sidering_price > 0)
                ||
                ($previewData->wip_sidering !== '-' && (
                    !empty($sideringChildren) ||
                    (isset($previewData->wip_sidering_price) && $previewData->wip_sidering_price !== null && $previewData->wip_sidering_price >= 0)
                ))
            ) {
                $processItems[] = [
                    'item_code' => $previewData->wip_sidering,
                    'description' => $previewData->sideringWIP->description ?? null,
                    'type' => 'PR',
                    'wip_info' => 'Sidering',
                    'periods_data' => $createPeriodsData(
                        null,
                        null,
                        $previewData->wip_sidering_price ?? null,
                        0
                    ),
                    'level' => 0,
                    'children' => $sideringChildren
                ];
            }
        }

        #Rim
        $rimChildren = [];
        if ($previewData->rim_code !== null && $previewData->rim_code !== '-') {
            $rimChildren[] = [
                'item_code' => $previewData->rim_code,
                'description' => $previewData->rimMaterial->description ?? null,
                'type' => 'RM',
                'periods_data' => $createPeriodsData(
                    $previewData->rim_qty ?? null,
                    $previewData->rim_price ?? null,
                    $previewData->rim_price ?? null,
                    0
                ),
                'level' => 1
            ];
        }

        if ($previewData->pr_rim !== '-' || $previewData->pr_rim !== null) {
            $rimProcessLines = [];
            $rimLinesMapping = [
                'rim1' => ['eff_prop' => 'rim1_eff', 'ct_prop' => 'rim1'],
                'rim2' => ['eff_prop' => 'rim2_eff', 'ct_prop' => 'rim2'],
                'rim2insp' => ['eff_prop' => 'rim2insp_eff', 'ct_prop' => 'rim2insp'],
                'rim3' => ['eff_prop' => 'rim3_eff', 'ct_prop' => 'rim3'],

            ];

            $cycleTimeData = $previewData->ct;

            if ($cycleTimeData) {
                foreach ($rimLinesMapping as $lineKey => $props) {
                    $efficiencyProp = $props['eff_prop'];
                    $cycleTimeProp = $props['ct_prop'];

                    $efficiency = $cycleTimeData->{$efficiencyProp} ?? 0;
                    $cycleTimeValue = $cycleTimeData->{$cycleTimeProp} ?? null;

                    $formattedPercentage = number_format($efficiency * 100, 0);

                    if ($efficiency > 0) {
                        $displayName = ucfirst($lineKey);

                        $rimProcessLines[] = [
                            'name' => $displayName,
                            'percentage' => $formattedPercentage . '%',
                            'cycle_time' => ($cycleTimeValue !== null) ? number_format($cycleTimeValue, 1, ',', '.') : ''
                        ];
                    }
                }
            }

            $rimChildren[] = [
                'item_code' => $previewData->pr_rim->item_code ?? $previewData->pr_rim,
                'description' => $previewData->rimProcess->description ?? 'N/A',
                'type' => 'PR',
                'lines' => $rimProcessLines,
                'periods_data' => $createPeriodsData(
                    1.0,
                    $previewData->pr_rim_price ?? null,
                    $previewData->pr_rim_price ?? null,
                    0
                ),
                'level' => 1
            ];
        }

        if (!empty(trim($previewData->wip_rim))) {
            if (
                ($previewData->wip_rim === '-' && isset($previewData->wip_rim_price) && $previewData->wip_rim_price > 0)
                ||
                ($previewData->wip_rim !== '-' && (
                    !empty($rimChildren) ||
                    (isset($previewData->wip_rim_price) && $previewData->wip_rim_price !== null && $previewData->wip_rim_price >= 0)
                ))
            ) {
                $processItems[] = [
                    'item_code' => $previewData->wip_rim,
                    'description' => $previewData->rimWIP->description ?? null,
                    'type' => 'PR',
                    'wip_info' => 'Rim',
                    'periods_data' => $createPeriodsData(
                        null,
                        null,
                        $previewData->wip_rim_price ?? null,
                        0
                    ),
                    'level' => 0,
                    'children' => $rimChildren
                ];
            }
        }

        #Disc
        $discChildren = [];
        if ($previewData->disc_code !== null && $previewData->disc_code !== '-') {
            $discChildren[] = [
                'item_code' => $previewData->disc_code,
                'description' => $previewData->discMaterial->description ?? null,
                'type' => 'RM',
                'periods_data' => $createPeriodsData(
                    $previewData->disc_qty ?? null,
                    $previewData->disc_price ?? null,
                    $previewData->disc_price ?? null,
                    0
                ),
                'level' => 1
            ];
        }

        if ($previewData->pr_disc !== '-' || $previewData->pr_disc !== null) {
            $discProcessLines = [];
            $discLinesMapping = [
                'blanking'   => ['eff_prop' => 'blanking_eff', 'ct_prop' => 'blanking'],
                'spinDisc'   => ['eff_prop' => 'spinDisc_eff', 'ct_prop' => 'spinDisc'],
                'autoDisc'   => ['eff_prop' => 'autoDisc_eff', 'ct_prop' => 'autoDisc'],
                'manualDisc' => ['eff_prop' => 'manualDisc_eff', 'ct_prop' => 'manualDisc'],
                'c3_sn'      => ['eff_prop' => 'c3_sn_eff',      'ct_prop' => 'c3_sn'],
                'repairC3'   => ['eff_prop' => 'repairC3_eff',   'ct_prop' => 'repairC3'],
                'discLathe'  => ['eff_prop' => 'discLathe_eff',  'ct_prop' => 'discLathe'],
            ];

            $cycleTimeData = $previewData->ct;

            if ($cycleTimeData) {
                foreach ($discLinesMapping as $lineKey => $props) {
                    $efficiencyProp = $props['eff_prop'];
                    $cycleTimeProp = $props['ct_prop'];

                    $efficiency = $cycleTimeData->{$efficiencyProp} ?? 0;
                    $cycleTimeValue = $cycleTimeData->{$cycleTimeProp} ?? null;

                    $formattedPercentage = number_format($efficiency * 100, 0);

                    if ($efficiency > 0) {
                        $displayName = ucfirst($lineKey);

                        $discProcessLines[] = [
                            'name' => $displayName,
                            'percentage' => $formattedPercentage . '%',
                            'cycle_time' => ($cycleTimeValue !== null) ? number_format($cycleTimeValue, 1, ',', '.') : ''
                        ];
                    }
                }
            }

            $discChildren[] = [
                'item_code' => $previewData->pr_disc->item_code ?? $previewData->pr_disc,
                'description' => $previewData->discProcess->description ?? null,
                'type' => 'PR',
                'lines' => $discProcessLines,
                'periods_data' => $createPeriodsData(
                    1.0,
                    $previewData->pr_disc_price ?? null,
                    $previewData->pr_disc_price ?? null,
                    0
                ),
                'level' => 1
            ];
        }

        if (!empty(trim($previewData->wip_disc))) {
            if (
                ($previewData->wip_disc === '-' && isset($previewData->wip_disc_price) && $previewData->wip_disc_price > 0)
                ||
                ($previewData->wip_disc !== '-' && (
                    !empty($discChildren) ||
                    (isset($previewData->wip_disc_price) && $previewData->wip_disc_price !== null && $previewData->wip_disc_price >= 0)
                ))
            ) {
                $processItems[] = [
                    'item_code' => $previewData->wip_disc,
                    'description' => $previewData->discWIP->description ?? null,
                    'type' => 'PR',
                    'wip_info' => 'Disc',
                    'periods_data' => $createPeriodsData(
                        null,
                        null,
                        $previewData->wip_disc_price ?? null,
                        0
                    ),
                    'level' => 0,
                    'children' => $discChildren
                ];
            }
        }


        $reportData['categories'][] = [
            'name' => 'FinishGood',
            'items' => $fgItems,
        ];

        $reportData['categories'][] = [
            'name' => 'Topcoat',
            'items' => $topcoatItems,
        ];

        $reportData['categories'][] = [
            'name' => 'CED',
            'items' => $cedItems,
        ];

        $reportData['categories'][] = [
            'name' => 'Assembly',
            'items' => $assyItems,
        ];

        $reportData['categories'][] = [
            'name' => 'Process',
            'items' => $processItems,
        ];

        $mfgCost = $previewData->total;
        $opexCost = $previewData->total * ($opex / 100);
        $totalCost = $mfgCost + $opexCost;
        $margin = $totalCost * ($progin / 100);
        $sellingPrice = $totalCost + $margin;

        return Inertia::render('bom/preview', [
            'previewData' => $previewData,
            'reportData' => $reportData,
            'totalCT' => number_format($totalCycleTime, 0, ',', '.'),
            'mfgCost' => number_format($mfgCost, 0, ',', '.'),
            'opexCost' => number_format($opexCost, 0, ',', '.'),
            'totalCost' => number_format($totalCost, 0, ',', '.'),
            'margin' => number_format($margin, 0, ',', '.'),
            'sellingPrice' => number_format($sellingPrice, 0, ',', '.'),
            'total_rm' => number_format(($previewData->total_raw_material), 0, ',', '.'),
            'total_pr' => number_format(($previewData->total_process), 0, ',', '.'),
            'opex' => $opex,
            'progin' => $progin
        ]);
    }
}
