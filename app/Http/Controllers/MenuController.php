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
use App\Models\ActualMaterial;
use App\Models\ActualSalesQuantity;
use App\Models\BaseCost;
use App\Models\BillOfMaterial;
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
use App\Models\StandardMaterial;
use App\Models\User;
use App\Models\Valve;
use App\Models\WagesDistribution;


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

    public function BOM_Master(Request $request)
    {
        $bom = BillOfMaterial::where('depth', 1)->get();
        $valve = Valve::all();
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

                $finishGood = $all[$mainIndex];
            }
        }

        $addedItems = Session::get('addedItems', []);
        $invalidItems = Session::get('invalidItems', []);

        return Inertia::render("bom/master", [
            'billOfMaterials' => $bom,
            'type' => 'bom',
            'finish_good' => $finishGood,
            'valve' => $valve,
            'component' => $componentItems,
            'importResult' => [
                'addedItems' => $addedItems,
                'invalidItems' => $invalidItems
            ]
        ]);
    }

    public function Standard_Master(Request $request)
    {
        $standardMaterial = StandardMaterial::with('bom')->get();
        $addedItems = Session::get('addedItems', []);
        $invalidItems = Session::get('invalidItems', []);

        return Inertia::render("sc/master", [
            'standardMaterial' => $standardMaterial,
            'type' => 'standardMaterial',
            'importResult' => [
                'addedItems' => $addedItems,
                'invalidItems' => $invalidItems
            ]
        ]);
    }

    public function Actual_Master(Request $request)
    {
        $actualMaterial = ActualMaterial::with('bom')->get();
        $addedItems = Session::get('addedItems', []);
        $invalidItems = Session::get('invalidItems', []);
        $actualSalesQuantities = ActualSalesQuantity::with('bom')->get();

        return Inertia::render("ac/master", [
            'actualMaterial' => $actualMaterial,
            'actualSalesQty' => $actualSalesQuantities,
            'type' => 'actualMaterial',
            'importResult' => [
                'addedItems' => $addedItems,
                'invalidItems' => $invalidItems
            ]
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

        // Tambahkan created_at dari CycleTime ke array $lastUpdate jika ada
        if ($latestCycleTime) {
            $lastUpdate[] = $latestCycleTime->created_at;
        } else {
            $lastUpdate[] = null; // Atau nilai default lain jika tidak ada data
        }

        // Tambahkan created_at dari SalesQuantity ke array $lastUpdate jika ada
        if ($latestSalesQuantity) {
            $lastUpdate[] = $latestSalesQuantity->created_at;
        } else {
            $lastUpdate[] = null; // Atau nilai default lain jika tidak ada data
        }

        // Tambahkan created_at dari WagesDistribution ke array $lastUpdate jika ada
        if ($latestWagesDistribution) {
            $lastUpdate[] = $latestWagesDistribution->created_at;
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

    public function BOM_Report()
    {
        $sc = StandardCost::with(['bom' => function ($query) {
            $query->select('item_code', 'description');
        }])->get();

        $ac = ActualCost::with(['bom' => function ($query) {
            $query->select('item_code', 'description');
        }])->get();

        $dc = DifferenceCost::with(['bom' => function ($query) {
            $query->select('item_code', 'description');
        }])->get();

        $actual_sales = ActualSalesQuantity::with(['bom' => function ($query) {
            $query->select('item_code', 'description');
        }])->get();

        $dcxsq = DiffCostXSalesQty::with(['bom' => function ($query) {
            $query->select('item_code', 'description');
        }])->get();

        $lastUpdate = [];
        $latestBOM = BillOfMaterial::latest('updated_at')->first();

        if ($latestBOM) {
            $lastUpdate[] = $latestBOM->created_at;
        } else {
            $lastUpdate[] = null;
        }

        $bomData = BillOfMaterial::all();
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

        return Inertia::render("bom/report", [
            'sc' => $sc,
            'ac' => $ac,
            'dc' => $dc,
            'actual_sales' => $actual_sales,
            'bom' => $finalReportData,
            'dcxsq' => $dcxsq,
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

    public function Standard_Report()
    {
        $sc = StandardCost::with(['bom' => function ($query) {
            $query->select('item_code', 'description');
        }])->get();

        $lastUpdate = [];
        $latestStandardMat = StandardMaterial::latest('updated_at')->first();
        $latestValve = Valve::latest('updated_at')->first();
        $latestProcessCost = ProcessCost::latest('updated_at')->first();
        $latestBOM = BillOfMaterial::latest('updated_at')->first();


        if ($latestStandardMat) {
            $lastUpdate[] = $latestStandardMat->created_at;
        } else {
            $lastUpdate[] = null;
        }

        if ($latestValve) {
            $lastUpdate[] = $latestValve->created_at;
        } else {
            $lastUpdate[] = null;
        }

        if ($latestProcessCost) {
            $lastUpdate[] = $latestProcessCost->created_at;
        } else {
            $lastUpdate[] = null;
        }

        if ($latestBOM) {
            $lastUpdate[] = $latestBOM->created_at;
        } else {
            $lastUpdate[] = null;
        }

        return Inertia::render("sc/report", [
            'sc' => $sc,
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
    }

    public function reportActual()
    {
        $ac = ActualCost::with(['bom' => function ($query) {
            $query->select('item_code', 'description');
        }])->get();

        $acPeriod = ActualCost::distinct()->pluck('period');
        $acPeriod = $this->sortPeriods($acPeriod);



        $lastUpdate = [];
        $latestActualMat = ActualMaterial::latest('updated_at')->first();
        $latestValve = Valve::latest('updated_at')->first();
        $latestProcessCost = ProcessCost::latest('updated_at')->first();
        $latestBOM = BillOfMaterial::latest('updated_at')->first();

        if ($latestActualMat) {
            $lastUpdate[] = $latestActualMat->created_at;
        } else {
            $lastUpdate[] = null;
        }

        if ($latestValve) {
            $lastUpdate[] = $latestValve->created_at;
        } else {
            $lastUpdate[] = null;
        }

        if ($latestBOM) {
            $lastUpdate[] = $latestBOM->created_at;
        } else {
            $lastUpdate[] = null;
        }

        if ($latestProcessCost) {
            $lastUpdate[] = $latestProcessCost->created_at;
        } else {
            $lastUpdate[] = null;
        }

        return Inertia::render("ac/report", [
            'ac' => $ac,
            'acPeriod' => $acPeriod,
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
