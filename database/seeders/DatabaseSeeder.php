<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Hash;
use App\Models\RequestForService;
use Illuminate\Support\Facades\DB;



class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Roles
        $no_role = Role::create(['name' => 'No Role']);
        $director = Role::create(['name' => 'Director']);
        $division = Role::create(['name' => 'Deputy Division']);
        $departemen = Role::create(['name' => 'Deputy Department']);
        $staff = Role::create(['name' => 'Staff']);
        $admin = Role::create(['name' => 'Admin']);
        $ProCost_view = Role::create(['name' => 'Process Cost - View']);
        $ProCost_full = Role::create(['name' => 'Process Cost - Full Access']);
        $BOM_view = Role::create(['name' => 'BOM - View']);
        $BOM_full = Role::create(['name' => 'BOM - Full Access']);

        // 2. Permissions
        Permission::create(['name' => 'Approve']);
        Permission::create(['name' => 'Reject']);
        Permission::create(attributes: ['name' => 'Execute']);
        Permission::create(['name' => 'Finish']);
        Permission::create(['name' => 'CreateRequest']);
        Permission::create(['name' => 'CreateUser']);
        Permission::create(['name' => 'Update_MasterData']);
        Permission::create(['name' => 'Update_Report']);
        Permission::create(['name' => 'View_Report']);

        // 3. Assign Permissions
        $director->givePermissionTo(['CreateRequest', 'CreateUser']);
        $division->givePermissionTo(['CreateRequest', 'CreateUser']);
        $departemen->givePermissionTo(['CreateRequest', 'Approve', 'CreateUser']);
        $staff->givePermissionTo(['CreateRequest',]);
        $admin->givePermissionTo(['CreateUser', 'Approve', 'Reject', 'CreateRequest', 'Execute', 'Finish', 'Update_MasterData', 'Update_Report', 'View_Report']);
        $ProCost_view->givePermissionTo(['View_Report']);
        $ProCost_full->givePermissionTo(['View_Report', 'Update_MasterData', 'Update_Report']);
        $BOM_view->givePermissionTo(['View_Report']);
        $BOM_full->givePermissionTo(['View_Report', 'Update_MasterData', 'Update_Report']);

        // 4. Users
        $users = [
            ['name' => 'Tuti', 'npk' => '140025', 'role' => ['Director', 'Process Cost - Full Access', 'BOM - Full Access']],
            ['name' => 'Matsuyama', 'npk' => '240458', 'role' => ['Deputy Division', 'Process Cost - Full Access', 'BOM - Full Access']],
            ['name' => 'Inge', 'npk' => '100101', 'role' => ['Deputy Department', 'Process Cost - Full Access', 'BOM - Full Access']],
            ['name' => 'Rudi', 'npk' => '140023', 'role' => ['Staff', 'Process Cost - Full Access', 'BOM - Full Access']],
            ['name' => 'Setyaningsih', 'npk' => '140207', 'role' => ['Staff', 'Process Cost - View', 'BOM - View']],
            ['name' => 'Ayu', 'npk' => '190349', 'role' => ['Staff', 'Process Cost - View', 'BOM - View']],
            ['name' => 'Fadel', 'npk' => '240473', 'role' => ['Admin', 'Process Cost - Full Access', 'BOM - Full Access']],
        ];

        foreach ($users as $data) {
            $u = User::create([
                'name' => $data['name'],
                'npk' => $data['npk'],
                'password' => Hash::make('qweasd'),
            ]);

            $u->syncRoles($data['role']);
        }

        // $request = [
        //     [
        //         'name' => 'Ayu',
        //         'npk' => '190349',
        //         'priority' => '2',
        //         'created_at' => '2024-11-20',
        //         'description' => 'Macro to process VAT crediting data in E-Faktur.',
        //         'status' => '4',
        //         'attachment' => null,
        //         'updated_at' => '2024-11-26',

        //     ],
        //     [
        //         'name' => 'Setyaningsih',
        //         'npk' => '140207',
        //         'priority' => '2',
        //         'created_at' => '2024-11-27',
        //         'description' => 'Macro to process Mass Payment data.',
        //         'status' => '4',
        //         'attachment' => null,
        //         'updated_at' => '2024-12-2',

        //     ],
        //     [
        //         'name' => 'Ayu',
        //         'npk' => '190349',
        //         'priority' => '2',
        //         'created_at' => '2024-12-3',
        //         'description' => 'Macro to input tax data into E-Faktur.',
        //         'status' => '4',
        //         'attachment' => null,
        //         'updated_at' => '2024-12-13',
        //     ],
        //     [
        //         'name' => 'Rudi',
        //         'npk' => '140023',
        //         'priority' => '3',
        //         'created_at' => '2024-12-3',
        //         'description' => 'Macro to automatically generate statement of account.',
        //         'status' => '4',
        //         'attachment' => null,
        //         'updated_at' => '2024-12-23',
        //     ],
        //     [
        //         'name' => 'Rudi',
        //         'npk' => '140023',
        //         'priority' => '3',
        //         'created_at' => '2024-12-9',
        //         'description' => 'Macro to generate Bill of Material.',
        //         'status' => '4',
        //         'attachment' => null,
        //         'updated_at' => '2024-12-26',
        //     ],
        //     [
        //         'name' => 'Rudi',
        //         'npk' => '140023',
        //         'priority' => '3',
        //         'created_at' => '2025-01-6',
        //         'description' => 'Develop an application to calculate Process Cost.',
        //         'status' => '4',
        //         'attachment' => null,
        //         'updated_at' => '2025-03-27',
        //     ],
        //     [
        //         'name' => 'Ayu',
        //         'npk' => '190349',
        //         'priority' => '2',
        //         'created_at' => '2025-02-10',
        //         'description' => 'Macro to report withholding tax certificates in Coretax.',
        //         'status' => '4',
        //         'attachment' => null,
        //         'updated_at' => '2025-02-11',
        //     ],
        //     [
        //         'name' => 'Ayu',
        //         'npk' => '190349',
        //         'priority' => '1',
        //         'created_at' => '2025-02-10',
        //         'description' => 'Macro to fetch exchange rates (EUR, JPY, USD) from BI website.',
        //         'status' => '4',
        //         'attachment' => null,
        //         'updated_at' => '2025-02-12',
        //     ],
        //     [
        //         'name' => 'Rudi',
        //         'npk' => '140023',
        //         'priority' => '4',
        //         'created_at' => '2025-05-12',
        //         'description' => 'Develop an application to explode Bill of Material.',
        //         'status' => '4',
        //         'attachment' => null,
        //         'updated_at' => '2025-6-2',
        //     ],
        //     [
        //         'name' => 'Setyaningsih',
        //         'npk' => '140023',
        //         'priority' => '3',
        //         'created_at' => '2025-06-3',
        //         'description' => 'Develop an internal Request of Service application for Finance.',
        //         'status' => '4',
        //         'attachment' => null,
        //         'updated_at' => '2025-07-8',
        //     ],
        //     [
        //         'name' => 'Rudi',
        //         'npk' => '140023',
        //         'priority' => '3',
        //         'created_at' => '2025-07-12',
        //         'description' => 'Develop an feature to export BOM per Product.',
        //         'status' => '4',
        //         'attachment' => null,
        //         'updated_at' => '2025-07-26',
        //     ],
        //     [
        //         'name' => 'Tuti',
        //         'npk' => '140025',
        //         'priority' => '3',
        //         'created_at' => '2025-07-29',
        //         'description' => 'Modify dialog after updating master data.',
        //         'status' => '4',
        //         'attachment' => null,
        //         'updated_at' => '2025-08-05',
        //     ],
        //     [
        //         'name' => 'Tuti',
        //         'npk' => '140025',
        //         'priority' => '3',
        //         'created_at' => '2025-07-29',
        //         'description' => 'Creating new input for OPEX dan Profit Margin in Standart Cost.',
        //         'status' => '4',
        //         'attachment' => null,
        //         'updated_at' => '2025-08-07',
        //     ],

        //     [
        //         'name' => 'Tuti',
        //         'npk' => '140025',
        //         'priority' => '3',
        //         'created_at' => '2025-08-06',
        //         'description' => 'Creating new menu for Actual Cost.',
        //         'status' => '4',
        //         'attachment' => null,
        //         'updated_at' => '2025-08-07',
        //     ],

        //     [
        //         'name' => 'Tuti',
        //         'npk' => '140025',
        //         'priority' => '3',
        //         'created_at' => '2025-08-07',
        //         'description' => 'Create new menu for Calculating Difference between Standard vs Actual Cost.',
        //         'status' => '4',
        //         'attachment' => null,
        //         'updated_at' => '2025-08-13',
        //     ],

        //     [
        //         'name' => 'Setyaningsih',
        //         'npk' => '140023',
        //         'priority' => '1',
        //         'created_at' => '2025-08-14',
        //         'description' => 'Create a cash in/out system in Excel (Please also make a sheet with history report to reconcile the ending balance with SAP balance)',
        //         'status' => '4',
        //         'attachment' => null,
        //         'updated_at' => '2025-08-14',
        //     ],
        //     [
        //         'name' => 'Ayu',
        //         'npk' => '190349',
        //         'priority' => '2',
        //         'created_at' => '2025-08-14',
        //         'description' => 'Create digitialization of Entertainment Form in Personal site.',
        //         'status' => '2',
        //         'attachment' => 'attachment/zmmJtfG3mzE1JqBKvpoSMkdmvk8QxYoGzYWqXjWm.xlsx',
        //         'updated_at' => '2025-08-14',
        //     ],
        // ];


        $requests = [
            [
                'name' => 'Ayu',
                'npk' => '190349',
                'priority_id' => 2,
                'description' => 'Macro to process VAT-In crediting data in E-Faktur.',
                'status_id' => 7,
                'impact' => 'Makes it easier for users when crediting tax invoices into the system.',
                'attachment' => null,
                'created_at' => '2024-11-20',
                'updated_at' => '2024-11-26',
            ],
            [
                'name' => 'Setyaningsih',
                'npk' => '140207',
                'priority_id' => 2,
                'description' => 'Macro to process Mass Payment data.',
                'status_id' => 7,
                'impact' => 'Increase eficiency time when making a batch for upload payment.',
                'attachment' => null,
                'created_at' => '2024-11-27',
                'updated_at' => '2024-12-2',
            ],
            [
                'name' => 'Rudi',
                'npk' => '140023',
                'priority_id' => 2,
                'description' => 'Macro to input tax data VAT-Out into E-Faktur.',
                'status_id' => 7,
                'impact' => 'Reduce worktime and increase data accuracy for reporting tax.',
                'attachment' => null,
                'created_at' => '2024-12-3',
                'updated_at' => '2024-12-13',
            ],
            [
                'name' => 'Rudi',
                'npk' => '140023',
                'priority_id' => 3,
                'description' => 'Macro to automatically generate statement of account.',
                'status_id' => 7,
                'impact' => 'Reduce worktime to create Statement of Account Letter.',
                'attachment' => null,
                'created_at' => '2024-12-3',
                'updated_at' => '2024-12-23',
            ],
            [
                'name' => 'Rudi',
                'npk' => '140023',
                'priority_id' => 3,
                'description' => 'Macro to generate Bill of Material.',
                'status_id' => 7,
                'impact' => 'Reduce worktime to create Bill of Material.',
                'attachment' => null,
                'created_at' => '2024-12-9',
                'updated_at' => '2024-12-26',
            ],
            [
                'name' => 'Rudi',
                'npk' => '140023',
                'priority_id' => 3,
                'description' => 'Develop an application to calculate Process Cost.',
                'status_id' => 7,
                'impact' => 'Reduce worktime and increase data accuracy for creating process cost report.',
                'attachment' => null,
                'created_at' => '2025-01-6',
                'updated_at' => '2025-03-27',
            ],
            [
                'name' => 'Ayu',
                'npk' => '190349',
                'priority_id' => 2,
                'description' => 'Macro to report withholding tax certificates in Coretax.',
                'status_id' => 7,
                'impact' => 'Reduce worktime to create withholding tax report.',
                'attachment' => null,
                'created_at' => '2025-02-10',
                'updated_at' => '2025-02-11',
            ],
            [
                'name' => 'Ayu',
                'npk' => '190349',
                'priority_id' => 1,
                'description' => 'Macro to fetch exchange rates (EUR, JPY, USD) from BI website.',
                'status_id' => 7,
                'impact' => 'Easier to fetch Exchange Rates data from BI Website.',
                'attachment' => null,
                'created_at' => '2025-02-10',
                'updated_at' => '2025-02-12',
            ],
            [
                'name' => 'Rudi',
                'npk' => '140023',
                'priority_id' => 4,
                'description' => 'Develop an application to explode Bill of Material.',
                'status_id' => 7,
                'impact' => 'Reduce worktime and increase data accuracy for explode Bill of Material Raw data.',
                'attachment' => null,
                'created_at' => '2025-05-13',
                'updated_at' => '2025-06-02',
            ],
            [
                'name' => 'Setyaningsih',
                'npk' => '140023',
                'priority_id' => 3,
                'description' => 'Develop an internal Request of Service application for Finance.',
                'status_id' => 7,
                'impact' => 'Tracing finance member request progress.',
                'attachment' => null,
                'created_at' => '2025-06-03',
                'updated_at' => '2025-07-08',
            ],
            [
                'name' => 'Rudi',
                'npk' => '140023',
                'priority_id' => 3,
                'description' => 'Develop an feature to export Standard Cost per Product.',
                'status_id' => 7,
                'impact' => 'Automatically generate Standard Cost per Product.',
                'attachment' => null,
                'created_at' => '2025-07-09',
                'updated_at' => '2025-07-28',
            ],
            [
                'name' => 'Tuti',
                'npk' => '140025',
                'priority_id' => 3,
                'description' => 'Modify dialog after updating master data.',
                'status_id' => 7,
                'impact' => 'User more informed about what happen on the system is it already updated/added/deleted/processed or not.',
                'attachment' => null,
                'created_at' => '2025-07-29',
                'updated_at' => '2025-08-07',
            ],
            [
                'name' => 'Tuti',
                'npk' => '140025',
                'priority_id' => 3,
                'description' => 'Creating new input for OPEX dan Profit Margin in Standart Cost.',
                'status_id' => 7,
                'impact' => 'User can change percentage of OPEX and Profit Margin.',
                'attachment' => null,
                'created_at' => '2025-07-29',
                'updated_at' => '2025-08-07',
            ],
            [
                'name' => 'Tuti',
                'npk' => '140025',
                'priority_id' => 3,
                'description' => 'Creating new menu for Actual Cost.',
                'status_id' => 7,
                'impact' => 'User can add new data for actual material price.',
                'attachment' => null,
                'created_at' => '2025-08-07',
                'updated_at' => '2025-08-09',
            ],
            [
                'name' => 'Tuti',
                'npk' => '140025',
                'priority_id' => 3,
                'description' => 'Create new menu for Calculating Difference between Standard vs Actual Cost.',
                'status_id' => 7,
                'impact' => 'Can calculate automatically difference between Standard vs Actual Cost.',
                'attachment' => null,
                'created_at' => '2025-08-07',
                'updated_at' => '2025-08-14',
            ],
            [
                'name' => 'Setyaningsih',
                'npk' => '140023',
                'priority_id' => 1,
                'description' => 'Create a cash in/out system in Excel (Please also make a sheet with history report to reconcile the ending balance with SAP balance)',
                'status_id' => 7,
                'impact' => 'Actual In/out pettycash based on denomination, it can easily to check the actual cash when cash opname.',
                'attachment' => null,
                'created_at' => '2025-08-14',
                'updated_at' => '2025-08-14',
            ],
            [
                'name' => 'Ayu',
                'npk' => '190349',
                'priority_id' => 2,
                'description' => 'Create digitialization of Entertainment Form in Personal site.',
                'status_id' => 5,
                'impact' => null,
                'attachment' => 'attachment/zmmJtfG3mzE1JqBKvpoSMkdmvk8QxYoGzYWqXjWm.xlsx',
                'created_at' => '2025-08-14',
                'updated_at' => '2025-08-14',
            ],
            [
                'name' => 'Rudi',
                'npk' => '140023',
                'priority_id' => 3,
                'description' => 'Change display of few menu in the system. Wages Rate become Production Cost, Bill of Material become Standard Cost, Adding export feature in BOM without the price (only BOM structur).',
                'status_id' => 5,
                'impact' => '',
                'attachment' => null,
                'created_at' => '2025-09-24',
                'updated_at' => '2025-07-29',
            ],
            [
                'name' => 'Rudi',
                'npk' => '140023',
                'priority_id' => 3,
                'description' => 'Spliting master data between Standard Cost, Actual Cost, and Bill of Material.',
                'status_id' => 5,
                'impact' => '',
                'attachment' => null,
                'created_at' => '2025-09-24',
                'updated_at' => '2025-07-29',
            ]
        ];

        $histories = [
            [
                'rfs_id' => '1',
                'updated_by' => 'Ayu',
                'updated_at' => '2024-11-26'
            ],
            [
                'rfs_id' => '2',
                'updated_by' => 'Setyaningsih',
                'updated_at' => '2024-12-2'
            ],
            [
                'rfs_id' => '3',
                'updated_by' => 'Ayu',
                'updated_at' => '2024-12-13'
            ],
            [
                'rfs_id' => '4',
                'updated_by' => 'Rudi',
                'updated_at' => '2024-12-23'
            ],
            [
                'rfs_id' => '5',
                'updated_by' => 'Rudi',
                'updated_at' => '2024-12-26'
            ],
            [
                'rfs_id' => '6',
                'updated_by' => 'Rudi',
                'updated_at' => '2025-03-27'
            ],
            [
                'rfs_id' => '7',
                'updated_by' => 'Ayu',
                'updated_at' => '2025-02-11'
            ],
            [
                'rfs_id' => '8',
                'updated_by' => 'Ayu',
                'updated_at' => '2025-02-12'
            ],
            [
                'rfs_id' => '9',
                'updated_by' => 'Rudi',
                'updated_at' => '2025-06-02'
            ],
            [
                'rfs_id' => '10',
                'updated_by' => 'Setyaningsih',
                'updated_at' => '2025-07-08'
            ],
            [
                'rfs_id' => '11',
                'updated_by' => 'Rudi',
                'updated_at' => '2025-07-26'
            ],
            [
                'rfs_id' => '12',
                'updated_by' => 'Tuti',
                'updated_at' => '2025-08-05'
            ],
            [
                'rfs_id' => '13',
                'updated_by' => 'Tuti',
                'updated_at' => '2025-08-07'
            ],
            [
                'rfs_id' => '14',
                'updated_by' => 'Tuti',
                'updated_at' => '2025-08-13'
            ],
            [
                'rfs_id' => '15',
                'updated_by' => 'Setyaningsih',
                'updated_at' => '2025-08-14'
            ],
            [
                'rfs_id' => '16',
                'updated_by' => 'Ayu',
                'updated_at' => '2025-08-14'
            ],
        ];

        DB::table('priorities')->insert([
            ['id' => 1, 'priority' => 'low'],
            ['id' => 2, 'priority' => 'medium'],
            ['id' => 3, 'priority' => 'high'],
            ['id' => 4, 'priority' => 'urgent'],
        ]);

        DB::table('statuses')->insert([
            [
                'id' => 1,
                'status' => 'wait_for_review'
            ],
            [
                'id' => 2,
                'status' => 'accepted'
            ],
            [
                'id' => 3,
                'status' => 'rejected'
            ],
            [
                'id' => 4,
                'status' => 'in_progress'
            ],

            [
                'id' => 5,
                'status' => 'user_acceptance'
            ],
            [
                'id' => 6,
                'status' => 'revision'
            ],
            [
                'id' => 7,
                'status' => 'finish'
            ],
        ]);

        DB::table('request_for_services')->insert($requests);
        DB::table('histories_rfs')->insert($histories);

        // for ($i = 0; $i < count($request); $i++) {
        //     $u = RequestForService::create([
        //         'name' => $request[$i]['name'],
        //         'npk' => $request[$i]['npk'],
        //         'priority' => $request[$i]['priority'],
        //         'created_at' => $request[$i]['created_at'],
        //         'description' => $request[$i]['description'],
        //         'status' => $request[$i]['status'],
        //         'attachment' => $request[$i]['attachment'],
        //         'updated_at' => $request[$i]['updated_at'],
        //     ]);
        // }
    }
}
