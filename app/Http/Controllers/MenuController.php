<?php

namespace App\Http\Controllers;

//Helpers
use Illuminate\Support\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Carbon\Carbon;

//Spatie
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

//Models
use App\Models\ActualCost;
use App\Models\ActualBillOfMaterial;
use App\Models\ActualMaterial;
use App\Models\ActualSalesQuantity;
use App\Models\BaseCost;
use App\Models\BusinessPartner;
use App\Models\costPerProcess;
use App\Models\CTxSQ;
use App\Models\CycleTime;
use App\Models\DifferenceCost;
use App\Models\DiffCostXSalesQty;
use App\Models\ProcessCost;
use App\Models\RequestForService;
use App\Models\SalesQuantity;
use App\Models\StandardCost;
use App\Models\StandardConsumable;
use App\Models\StandardMaterial;
use App\Models\StandardBillOfMaterial;
use App\Models\User;
use App\Models\WagesDistribution;

use function Pest\Laravel\json;

class MenuController extends Controller
{
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

    public function Dashboard()
    {
        $lastMonth = Carbon::now()->subMonthNoOverflow()->format('M');
        $Month = Carbon::now()->subMonthNoOverflow()->format('F');
        $lastYear = Carbon::now()->format('Y');
        $columnTopQuantity = strtolower($lastMonth) . '_qty';

        $topActualCost = ActualCost::orderBy('total', 'desc')
            ->where('period', 'like', '%' . $lastMonth . '%')
            ->limit(5)
            ->select('item_code', 'total', 'period')
            ->get();

        $topQuantity = ActualSalesQuantity::orderBy($columnTopQuantity, 'desc')
            ->limit(5)
            ->select('item_code', $columnTopQuantity . ' as quantity')
            ->get();

        $topDifferenceCost = DiffCostXSalesQty::orderBy('total', 'asc')
            ->where('period', 'like',  $lastMonth . '%')
            ->limit(5)
            ->select('item_code', 'quantity', 'total', 'period')
            ->get();

        return Inertia::render('Dashboard', [
            'topActualCost' => $topActualCost,
            'topQuantity' => $topQuantity,
            'topDifferenceCost' => $topDifferenceCost,
            'lastMonth' => $Month . "'" . $lastYear,
        ]);
    }

    public function RFS()
    {
        $services = RequestForService::with('priority', 'status')->get();
        return Inertia::render('rfs/index', [
            'services' => $services,
            'auth' => [
                'user' => Auth::check() ? [
                    'name' => Auth::user()->name,
                    'npk' => Auth::user()->npk,
                    'roles' => Auth::user()->getRoleNames()->toArray(), // Pastikan ini diubah ke array
                    'permissions' => Auth::user()->getAllPermissions()->pluck('name')->toArray(), // Juga pastikan ini dikirim
                ] : null,
            ],
        ]);
    }

    public function Admin()
    {
        $roles = Role::with('permissions')->get()->map(function ($role) {
            return [
                'id' => $role->id,
                'name' => $role->name,
                'permissions' => $role->permissions->pluck('name')->toArray()
            ];
        });

        $permissions = Permission::all()->map(function ($permission) {
            return [
                'id' => $permission->id,
                'name' => $permission->name
            ];
        });

        $users = User::with('roles')->get()->map(function ($user) {
            // Ambil semua nama role dan simpan sebagai array
            $roleNames = $user->roles->pluck('name')->toArray();

            return [
                'id' => $user->id,
                'name' => $user->name,
                'npk' => $user->npk,
                // Key diubah menjadi 'roles' (jamak) untuk menampung array
                // Jika tidak ada role, akan menampilkan array kosong []
                'roles' => $roleNames
            ];
        });

        return Inertia::render('admin/index', [
            'roles' => $roles,
            'permissions' => $permissions,
            'users' => $users
        ]);
    }

    public function ProcessCost_Master()
    {
        $bPartner = BusinessPartner::all();
        $cTimes = CycleTime::all();
        $salesQuantity = SalesQuantity::with(['bp', 'item'])->get();
        $wagesDistribution = WagesDistribution::first();

        $addedItems = Session::get('addedItems', []);
        $invalidItems = Session::get('invalidItems', []);
        return Inertia::render("pc/master", [
            'businessPartners' => $bPartner,
            'cycleTimes' => $cTimes,
            'salesQuantities' => $salesQuantity,
            'wagesDistribution' => $wagesDistribution,
            'importResult' => [
                'addedItems' => $addedItems,
                'invalidItems' => $invalidItems
            ]
        ]);
    }

    // public function BOM_Master(Request $request)
    // {
    //     $bom = BillOfMaterial::where('depth', 1)->get();
    //     $valve = Valve::all();
    //     $componentItems = collect();
    //     $finishGood = null;

    //     if ($request->has('component_id')) {
    //         $all = BillOfMaterial::orderBy('id')->get();
    //         $mainIndex = $all->search(fn($item) => $item->id == $request->component_id);

    //         if ($mainIndex !== false) {
    //             for ($i = $mainIndex + 1; $i < count($all); $i++) {
    //                 if ($all[$i]->depth == 1) break;
    //                 $componentItems->push($all[$i]);
    //             }

    //             $finishGood = $all[$mainIndex];
    //         }
    //     }

    //     $addedItems = Session::get('addedItems', []);
    //     $invalidItems = Session::get('invalidItems', []);

    //     return Inertia::render("bom/master", [
    //         'billOfMaterials' => $bom,
    //         'type' => 'bom',
    //         'finish_good' => $finishGood,
    //         'valve' => $valve,
    //         'component' => $componentItems,
    //         'importResult' => [
    //             'addedItems' => $addedItems,
    //             'invalidItems' => $invalidItems
    //         ]
    //     ]);
    // }

    public function Standard_Master(Request $request)
    {
        return Inertia::render("sc/master");
    }

    public function Actual_Master(Request $request)
    {
        $lastUpdate = [];
        $latestActualMat = ActualMaterial::latest('updated_at')->first();
        $latestSalesQty = ActualSalesQuantity::latest('updated_at')->first();
        $latestBOM = ActualBillOfMaterial::latest('updated_at')->first();

        if ($latestActualMat) {
            $lastUpdate[] = $latestActualMat->updated_at;
        } else {
            $lastUpdate[] = null;
        }

        if ($latestSalesQty) {
            $lastUpdate[] = $latestSalesQty->updated_at;
        } else {
            $lastUpdate[] = null;
        }

        if ($latestBOM) {
            $lastUpdate[] = $latestBOM->updated_at;
        } else {
            $lastUpdate[] = null;
        }

        return Inertia::render("ac/master", [
            'lastUpdate'   => $lastUpdate,
        ]);
    }

    public function ProcessCost_Report()
    {
        $ctxsq = CTxSQ::with(['bp', 'item'])->get();
        $base = BaseCost::with(['bp', 'item'])->get();
        $cpp = costPerProcess::with(['bp', 'item'])->get();
        $pc = ProcessCost::with(['bom' => function ($query) {
            $query->select('item_code', 'description');
        }])->get();

        $lastUpdate = [];

        // Ambil data CycleTime dengan updated_at paling terbaru
        $latestCycleTime = CycleTime::latest('updated_at')->first();

        // Ambil data SalesQuantity dengan updated_at paling terbaru
        $latestSalesQuantity = SalesQuantity::latest('updated_at')->first();

        $latestWagesDistribution = WagesDistribution::latest('updated_at')->first();

        // Tambahkan updated_at dari CycleTime ke array $lastUpdate jika ada
        if ($latestCycleTime) {
            $lastUpdate[] = $latestCycleTime->updated_at;
        } else {
            $lastUpdate[] = null; // Atau nilai default lain jika tidak ada data
        }

        // Tambahkan updated_at dari SalesQuantity ke array $lastUpdate jika ada
        if ($latestSalesQuantity) {
            $lastUpdate[] = $latestSalesQuantity->updated_at;
        } else {
            $lastUpdate[] = null; // Atau nilai default lain jika tidak ada data
        }

        // Tambahkan updated_at dari WagesDistribution ke array $lastUpdate jika ada
        if ($latestWagesDistribution) {
            $lastUpdate[] = $latestWagesDistribution->updated_at;
        } else {
            $lastUpdate[] = null; // Atau nilai default lain jika tidak ada data
        }

        $NumericFields = [
            'blanking',
            'spinDisc',
            'autoDisc',
            'manualDisc',
            'discLathe',
            'total_disc',
            'rim1',
            'rim2',
            'rim3',
            'total_rim',
            'coiler',
            'forming',
            'total_sidering',
            'assy1',
            'assy2',
            'machining',
            'shotPeening',
            'total_assy',
            'ced',
            'topcoat',
            'total_painting',
            'packing_dom',
            'packing_exp',
            'total_packaging',
            'total'
        ];

        $ProCostFields = [
            'max_of_disc',
            'max_of_rim',
            'max_of_sidering',
            'max_of_assy',
            'max_of_ced',
            'max_of_topcoat',
            'max_of_packaging',
            'max_of_total',
        ];

        // Hitung total tiap kolom
        $ctxsqTotals = [];
        $baseTotals = [];
        $cppTotals = [];
        foreach ($NumericFields as $field) {
            $ctxsqTotals[$field] = $ctxsq->sum($field);
            $baseTotals[$field] = $base->sum($field);
            $cppTotals[$field] = $cpp->sum($field);
        }

        $pcTotals = [];
        foreach ($ProCostFields as $field) {
            $pcTotals[$field] = $pc->max($field);
        }

        return Inertia::render('pc/report', [
            'ctxsq' => $ctxsq,
            'ctxsqTotal' => $ctxsqTotals,
            'base' => $base,
            'baseTotal' => $baseTotals,
            'cpp' => $cpp,
            'cppTotal' => $cppTotals,
            'processCost' => $pc,
            'pcTotal' => $pcTotals,
            'lastUpdate' => $lastUpdate,
            'auth' => [
                'user' => Auth::check() ? [
                    'name' => Auth::user()->name,
                    'npk' => Auth::user()->npk,
                    'roles' => Auth::user()->getRoleNames()->toArray(), // Pastikan ini diubah ke array
                    'permissions' => Auth::user()->getAllPermissions()->pluck('name')->toArray(), // Juga pastikan ini dikirim
                ] : null,
            ],
        ]);
    }

    private function processBOMData($bomData)
    {
        $groups = collect();
        $currentGroup = collect();

        // Logika Pengelompokan Berdasarkan Depth
        foreach ($bomData as $row) {
            if ($row->depth == 1) {
                if ($currentGroup->isNotEmpty()) $groups->push($currentGroup);
                $currentGroup = collect([$row]);
            } else {
                $currentGroup->push($row);
            }
        }
        if ($currentGroup->isNotEmpty()) $groups->push($currentGroup);

        return $groups->map(function ($group) {
            $main = $group->first();
            $typeChar = substr($main->item_code, 3, 1);

            // Filter Helpers (Substr Logic)
            $find = fn($prefix) => $group->first(fn($item) => str_starts_with($item->item_code, $prefix));

            // Logika CED & TC (Multi-items)
            $cdItems = $group->filter(fn($item) => str_starts_with($item->item_code, 'RS-CD'))->values();
            $tcItems = $group->filter(fn($item) => str_starts_with($item->item_code, 'RS-TC'))->values();

            return [
                'item_code'   => $main->item_code,
                'description' => $main->description ?? '-',

                // Raw Material
                'disc_qty'      => $find('RFD')->quantity ?? 0,
                'disc_code'     => $find('RFD')->item_code ?? '-',
                'rim_qty'       => $find('RFR')->quantity ?? 0,
                'rim_code'      => $find('RFR')->item_code ?? '-',
                'sidering_qty'  => $find('RFS')->quantity ?? 0,
                'sidering_code' => $find('RFS')->item_code ?? '-',

                // Process
                'pr_disc'     => $find('RS-DC')->item_code ?? '-',
                'pr_rim'      => $find('RS-RB')->item_code ?? '-',
                'pr_sidering' => $find('RS-SR')->item_code ?? '-',
                'pr_assy'     => $find('RS-AS')->item_code ?? '-',
                'pr_cedW'     => ($cdItems->count() >= 2 || $typeChar !== 'N') ? ($cdItems[0]->item_code ?? '-') : '-',
                'pr_cedSR'    => ($cdItems->count() >= 2) ? $cdItems[1]->item_code : (($typeChar === 'N') ? ($cdItems[0]->item_code ?? '-') : '-'),
                'pr_tcW'      => ($tcItems->count() >= 2 || $typeChar !== 'N') ? ($tcItems[0]->item_code ?? '-') : '-',
                'pr_tcSR'     => ($tcItems->count() >= 2) ? $tcItems[1]->item_code : (($typeChar === 'N') ? ($tcItems[0]->item_code ?? '-') : '-'),
                'pr_TA'       => $find('RS-TA')->item_code ?? '-',

                // WIP Logic (Contoh Refactoring Substr 0,2 & 3,2)
                'wip_disc'    => $group->first(fn($i) => str_starts_with($i->item_code, 'WB') && substr($i->item_code, 3, 2) === 'D-')->item_code ?? '-',
                'wip_rim'     => $group->first(fn($i) => str_starts_with($i->item_code, 'WA') && substr($i->item_code, 3, 2) === 'R-')->item_code ?? '-',
                'wip_sidering' => $group->first(fn($i) => str_starts_with($i->item_code, 'WD') && substr($i->item_code, 3, 2) === 'S-')->item_code ?? '-',
                'wip_assy'    => $group->first(fn($i) => str_starts_with($i->item_code, 'WC') && substr($i->item_code, 3, 2) === 'W-')->item_code ?? '-',
                'wip_cedW'    => $group->first(fn($i) => str_starts_with($i->item_code, 'WE') && substr($i->item_code, 3, 2) === 'W-')->item_code ?? '-',
                'wip_cedSR'   => $group->first(fn($i) => str_starts_with($i->item_code, 'WE') && substr($i->item_code, 3, 2) === 'S-')->item_code ?? '-',
                'wip_tcW'     => $group->first(fn($i) => str_starts_with($i->item_code, 'WF') && substr($i->item_code, 3, 2) === 'W-')->item_code ?? '-',
                'wip_tcSR'    => $group->first(fn($i) => str_starts_with($i->item_code, 'WF') && substr($i->item_code, 3, 2) === 'S-')->item_code ?? '-',

                // Valve & Tyre
                'wip_valve'   => $group->first(fn($i) => str_starts_with($i->item_code, 'CGP') && (stripos($i->description, 'valve') !== false || stripos($i->description, 'VLI') !== false))->item_code ?? '-',
                'wip_tyre'    => $group->first(fn($i) => str_starts_with($i->item_code, 'CGP') && stripos($i->description, 'tyre') !== false)->item_code ?? '-',
            ];
        });
    }

    public function BOM_Report()
    {
        // 1. Ambil Data Costing & Sales
        $relations = ['bom' => fn($q) => $q->select('item_code', 'description')];
        $sc = StandardCost::with($relations)->get();
        $ac = ActualCost::with($relations)->get();
        $dc = DifferenceCost::with($relations)->get();
        $actual_sales = ActualSalesQuantity::with($relations)->get();
        $dcxsq = DiffCostXSalesQty::with($relations)->get();

        // 2. Ambil Data BOM Mentah
        $stdBomRaw = StandardBillOfMaterial::all();
        $actBomRaw = ActualBillOfMaterial::all();

        // 3. Proses Data Menggunakan Helper
        $processedStdBom = $this->processBOMData($stdBomRaw);
        $processedActBom = $this->processBOMData($actBomRaw);

        // 4. Ambil Last Update
        $lastUpdate = [
            StandardBillOfMaterial::latest('updated_at')->value('updated_at'),
            ActualBillOfMaterial::latest('updated_at')->value('updated_at'),
        ];

        return Inertia::render("bom/report", [
            'sc' => $sc,
            'ac' => $ac,
            'dc' => $dc,
            'actual_sales' => $actual_sales,
            'stdBom'       => $processedStdBom, // Data Standard
            'actBom'       => $processedActBom, // Data Actual (Baru)
            'dcxsq'        => $dcxsq,
            'lastUpdate'   => $lastUpdate,
            'auth'         => [
                'user' => Auth::check() ? [
                    'name'        => Auth::user()->name,
                    'npk'         => Auth::user()->npk,
                    'roles'       => Auth::user()->getRoleNames()->toArray(),
                    'permissions' => Auth::user()->getAllPermissions()->pluck('name')->toArray(),
                ] : null,
            ],
        ]);
    }

    public function Standard_Report()
    {
        // $sc = StandardCost::with(['bom' => function ($query) {
        //     $query->select('item_code', 'description');
        // }])->get();

        $lastUpdate = [];
        $latestStandardMat = StandardMaterial::latest('updated_at')->first();
        $latestConsumable = StandardConsumable::latest('updated_at')->first();
        $latestProcessCost = ProcessCost::latest('updated_at')->first();
        $latestBOM = StandardBillOfMaterial::latest('updated_at')->first();
        $latestStandardCost = StandardCost::latest('updated_at')->first();


        if ($latestStandardMat) {
            $lastUpdate[] = $latestStandardMat->updated_at;
        } else {
            $lastUpdate[] = null;
        }

        if ($latestConsumable) {
            $lastUpdate[] = $latestConsumable->updated_at;
        } else {
            $lastUpdate[] = null;
        }

        if ($latestProcessCost) {
            $lastUpdate[] = $latestProcessCost->updated_at;
        } else {
            $lastUpdate[] = null;
        }

        if ($latestBOM) {
            $lastUpdate[] = $latestBOM->updated_at;
        } else {
            $lastUpdate[] = null;
        }

        if ($latestStandardCost) {
            $lastUpdate[] = $latestStandardCost->updated_at;
        } else {
            $lastUpdate[] = null;
        }

        // return Inertia::render("sc/report", [
        //     'sc' => $sc,
        // 'lastMaster' => $lastUpdate,
        //     'auth' => [
        //         'user' => Auth::check() ? [
        //             'name' => Auth::user()->name,
        //             'npk' => Auth::user()->npk,
        //             'roles' => Auth::user()->getRoleNames()->toArray(), // Pastikan ini diubah ke array
        //             'permissions' => Auth::user()->getAllPermissions()->pluck('name')->toArray(), // Juga pastikan ini dikirim
        //         ] : null,
        //     ],
        // ]);
        return Inertia::render("sc/report", [
            'lastMaster' => $lastUpdate,
        ]);
    }

    public function Actual_Report()
    {
        // $ac = ActualCost::with(['bom' => function ($query) {
        //     $query->select('item_code', 'description');
        // }])->get();

        // $acPeriod = ActualCost::distinct()->pluck('period');
        // $acPeriod = $this->sortPeriods($acPeriod);

        $lastUpdate = [];
        $latestActualMat = ActualMaterial::latest('updated_at')->first();
        $latestConsumable = StandardConsumable::latest('updated_at')->first();
        $latestProcessCost = ProcessCost::latest('updated_at')->first();
        $latestBOM = ActualBillOfMaterial::latest('updated_at')->first();

        if ($latestActualMat) {
            $lastUpdate[] = $latestActualMat->updated_at;
        } else {
            $lastUpdate[] = null;
        }

        if ($latestConsumable) {
            $lastUpdate[] = $latestConsumable->updated_at;
        } else {
            $lastUpdate[] = null;
        }

        if ($latestBOM) {
            $lastUpdate[] = $latestBOM->updated_at;
        } else {
            $lastUpdate[] = null;
        }

        if ($latestProcessCost) {
            $lastUpdate[] = $latestProcessCost->updated_at;
        } else {
            $lastUpdate[] = null;
        }

        return Inertia::render("ac/report", [
            // 'ac' => $ac,
            // 'acPeriod' => $acPeriod,
            'lastMaster' => $lastUpdate,
            'auth' => [
                'user' => Auth::check() ? [
                    'name' => Auth::user()->name,
                    'npk' => Auth::user()->npk,
                    'roles' => Auth::user()->getRoleNames()->toArray(), // Pastikan ini diubah ke array
                    'permissions' => Auth::user()->getAllPermissions()->pluck('name')->toArray(), // Juga pastikan ini dikirim
                ] : null,
            ],
        ]);

        // return Inertia::render("ac/report");
    }

    private function getCombinedDiffCost($sc, $ac, $dc)
    {
        // Fungsi untuk membersihkan Item Code
        $cleanItemCode = function ($value) {
            return trim((string) $value);
        };

        // Fungsi untuk mengekstrak TAHUN (YYYY) dari string periode (misal: "YTD-Jul'2025")
        $extractYear = function ($periodString) {
            if (empty($periodString)) return '';
            $parts = explode("'", (string) $periodString);
            if (count($parts) > 1) {
                $yearPart = trim($parts[1]);
                // Amankan dari format tahun 2 digit (misalnya '25' diubah menjadi '2025')
                if (strlen($yearPart) === 2) {
                    $yearPart = '20' . $yearPart;
                }
                return $yearPart;
            }
            // Jika formatnya hanya tahun (seperti di SC), kembalikan langsung
            return trim((string) $periodString);
        };

        // --- Pembuatan Map Standard Cost (Kunci: TAHUN-ItemCode) ---
        // SC.period = TAHUN (mis: '2025')
        $standardCostMap = collect($sc)->keyBy(function ($item) use ($cleanItemCode) {
            return "{$cleanItemCode($item->period)}-{$cleanItemCode($item->item_code)}";
        });

        // --- Pembuatan Map Actual Cost (Kunci: PERIODE PENUH-ItemCode) ---
        // AC.period = PERIODE PENUH (mis: 'YTD-Sep'2025')
        $actualCostMap = collect($ac)->keyBy(function ($item) use ($cleanItemCode) {
            return "{$cleanItemCode($item->period)}-{$cleanItemCode($item->item_code)}";
        });

        $defaultCost = ['total_raw_material' => 0, 'total_process' => 0, 'total' => 0];

        // Urutkan $dc berdasarkan item_code (sama seperti di JS)
        $sortedDc = collect($dc)->sortBy(fn($item) => $cleanItemCode($item->item_code));

        // Lakukan penggabungan data
        $combined = $sortedDc->map(function ($dcItem, $index) use ($extractYear, $cleanItemCode, $standardCostMap, $actualCostMap, $defaultCost) {
            $period = $dcItem->period; // PERIODE DC (Penuh, mis: 'YTD-Jul'2025')
            $itemCode = $cleanItemCode($dcItem->item_code);

            // 1. KUNCI STANDARD COST: Ambil Tahun dari periode DC
            $yearFromDCPeriod = $extractYear($period); // -> '2025'
            $standardKey = "{$yearFromDCPeriod}-{$itemCode}";

            // 2. KUNCI ACTUAL COST: Gunakan periode DC yang PENUH
            $actualKey = "{$cleanItemCode($period)}-{$itemCode}";

            // Ambil data cost dari Map (menggunakan ->get($key) pada Collection Map)
            $standardCostItem = $standardCostMap->get($standardKey);
            $actualCostItem = $actualCostMap->get($actualKey);

            $descriptionFromBom = optional($dcItem->bom)->description ?: '-';

            return [
                'no' => $index + 1,
                'item_code' => $dcItem->item_code,
                'description' => $descriptionFromBom,
                'period' => $dcItem->period,
                // Gunakan array() untuk memastikan konsistensi dengan $defaultCost
                'standard_cost' => $standardCostItem ? $standardCostItem->toArray() : $defaultCost,
                'actual_cost' => $actualCostItem ? $actualCostItem->toArray() : $defaultCost,
                'difference_cost' => $dcItem->toArray(), // DC adalah data utamanya
            ];
        });

        // Urutkan hasil akhir berdasarkan item_code
        return $combined->sortBy(fn($item) => $item['item_code'])->values()->toArray();
    }

    private function getCombinedDiffCostXSQuantity($sc, $ac, $dc, $dcxsq)
    {
        $cleanItemCode = function ($value) {
            return trim((string) $value);
        };

        $extractYear = function ($periodString) {
            if (empty($periodString)) return '';
            $parts = explode("'", (string) $periodString);
            if (count($parts) > 1) {
                $yearPart = trim($parts[1]);
                // Amankan dari format tahun 2 digit
                if (strlen($yearPart) === 2) {
                    $yearPart = '20' . $yearPart;
                }
                return $yearPart;
            }
            return trim((string) $periodString);
        };

        $standardCostMap = collect($sc)->keyBy(function ($item) use ($cleanItemCode) {
            return "{$cleanItemCode($item->period)}-{$cleanItemCode($item->item_code)}";
        });

        $actualCostMap = collect($ac)->keyBy(function ($item) use ($cleanItemCode) {
            return "{$cleanItemCode($item->period)}-{$cleanItemCode($item->item_code)}";
        });

        $differenceCostMap = collect($dc)->keyBy(function ($item) use ($cleanItemCode) {
            return "{$cleanItemCode($item->period)}-{$cleanItemCode($item->item_code)}";
        });

        $defaultCost = ['total_raw_material' => 0, 'total_process' => 0, 'total' => 0];
        $sortedDcXsq = collect($dcxsq)->sortBy(fn($item) => $cleanItemCode($item->item_code));
        $combined = $sortedDcXsq->map(function ($dcxsqItem, $index) use ($extractYear, $cleanItemCode, $standardCostMap, $actualCostMap, $differenceCostMap, $defaultCost) {
            $fullDcPeriod = $dcxsqItem->period;
            $itemCode = $cleanItemCode($dcxsqItem->item_code);

            $cleanedPeriod = $fullDcPeriod ? trim(explode(' / ', (string) $fullDcPeriod)[0]) : '';

            $yearFromDCPeriod = $extractYear($cleanedPeriod);
            $standardKey = "{$yearFromDCPeriod}-{$itemCode}";
            $actualKey = "{$cleanItemCode($cleanedPeriod)}-{$itemCode}";

            $standardCostItem = $standardCostMap->get($standardKey);
            $actualCostItem = $actualCostMap->get($actualKey);
            $differenceCostItem = $differenceCostMap->get($actualKey);

            $descriptionFromBom = optional($dcxsqItem->bom)->description ?: '-';

            return [
                'no' => $index + 1,
                'item_code' => $dcxsqItem->item_code,
                'description' => $descriptionFromBom,
                'period' => $fullDcPeriod,
                'standard_cost' => $standardCostItem ? $standardCostItem->toArray() : $defaultCost,
                'actual_cost' => $actualCostItem ? $actualCostItem->toArray() : $defaultCost,
                'difference_cost' => $differenceCostItem ? $differenceCostItem->toArray() : $defaultCost,
                'dcxsq' => $dcxsqItem->toArray(),
            ];
        });

        // Urutkan hasil akhir berdasarkan item_code
        return $combined->sortBy(fn($item) => $item['item_code'])->values()->toArray();
    }

    public function DiffCost_Report()
    {
        $scPeriod = StandardCost::distinct()->pluck('period');
        $acPeriod = ActualCost::distinct()->pluck('period');
        $dcPeriod = DifferenceCost::distinct()->pluck('period');
        $dcxsqPeriod = DiffCostXSalesQty::distinct()->pluck('period');
        $dcRemark = DifferenceCost::distinct()->pluck('remark');

        $scPeriod = $this->sortPeriods($scPeriod);
        $acPeriod = $this->sortPeriods($acPeriod);
        $dcPeriod = $this->sortPeriods($dcPeriod);
        $dcxsqPeriod = $this->sortPeriods($dcxsqPeriod);

        $sc = StandardCost::with(['bom' => function ($query) {
            $query->select('item_code', 'description');
        }])->get();

        $ac = ActualCost::with(['bom' => function ($query) {
            $query->select('item_code', 'description');
        }])->get();

        $dc = DifferenceCost::with(['bom' => function ($query) {
            $query->select('item_code', 'description');
        }])->get();

        $dcxsq = DiffCostXSalesQty::with(['bom' => function ($query) {
            $query->select('item_code', 'description');
        }])->where('quantity', '!=', 0)->get();

        $actual_sales = ActualSalesQuantity::with(['bom' => function ($query) {
            $query->select('item_code', 'description');
        }])->get();

        $combinedDC = $this->getCombinedDiffCost($sc, $ac, $dc);
        $combinedDCxSQ = $this->getCombinedDiffCostXSQuantity($sc, $ac, $dc, $dcxsq);
        return Inertia::render("dc/report", [
            'sc' => $sc,
            'ac' => $ac,
            'scPeriod' => $scPeriod,
            'acPeriod' => $acPeriod,
            'dcPeriod' => $dcPeriod,
            'dcRemark' => $dcRemark,
            'dcxsqPeriod' => $dcxsqPeriod,
            'dc' => $combinedDC,
            'dcxsq' => $combinedDCxSQ,
            'actual_sales' => $actual_sales,
            'auth' => [
                'user' => Auth::check() ? [
                    'name' => Auth::user()->name,
                    'npk' => Auth::user()->npk,
                    'roles' => Auth::user()->getRoleNames()->toArray(), // Pastikan ini diubah ke array
                    'permissions' => Auth::user()->getAllPermissions()->pluck('name')->toArray(), // Juga pastikan ini dikirim
                ] : null,
            ],
        ]);
    }
}
