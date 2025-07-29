<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Hash;
use App\Models\RequestForService;


class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Roles
        $director = Role::create(['name' => 'Director']);
        $division = Role::create(['name' => 'Deputy Division']);
        $departemen = Role::create(['name' => 'Deputy Department']);
        $staff = Role::create(['name' => 'Staff']);
        $admin = Role::create(['name' => 'Admin']);

        // 2. Permissions
        Permission::create(['name' => 'Approve']);
        Permission::create(['name' => 'Reject']);
        Permission::create(['name' => 'Execute']);
        Permission::create(['name' => 'Finish']);
        Permission::create(['name' => 'CreateRequest']);
        Permission::create(['name' => 'CreateUser']);

        // 3. Assign Permissions
        $director->givePermissionTo(['CreateRequest', 'CreateUser']);
        $division->givePermissionTo(['CreateRequest', 'CreateUser']);
        $departemen->givePermissionTo(['CreateRequest', 'Approve', 'CreateUser']);
        $staff->givePermissionTo(['CreateRequest',]);
        $admin->givePermissionTo(['CreateUser', 'Approve', 'Reject', 'CreateRequest', 'Execute', 'Finish']);

        // 4. Users
        $users = [
            ['name' => 'Tuti', 'npk' => '140025', 'role' => 'Director'],
            ['name' => 'Matsuyama', 'npk' => '240458', 'role' => 'Deputy Division'],
            ['name' => 'Inge', 'npk' => '100101', 'role' => 'Deputy Department'],
            ['name' => 'Rudi', 'npk' => '140023', 'role' => 'Staff'],
            ['name' => 'Setyaningsih', 'npk' => '140207', 'role' => 'Staff'],
            ['name' => 'Ayu', 'npk' => '190349', 'role' => 'Staff'],
            ['name' => 'Fadel', 'npk' => '240473', 'role' => 'Admin'],
        ];

        foreach ($users as $data) {
            $u = User::create([
                'name' => $data['name'],
                'npk' => $data['npk'],
                'password' => Hash::make('qweasd'),
            ]);

            $u->assignRole($data['role']);
        }

        $request = [
            [
                'name' => 'Ayu',
                'npk' => '190349',
                'priority' => 'medium',
                'created_at' => '2024-11-20',
                'description' => 'Macro to process VAT crediting data in E-Faktur.',
                'status' => 'finish',
                'attachment' => null,
                'updated_at' => '2024-11-26',

            ],
            [
                'name' => 'Setyaningsih',
                'npk' => '140207',
                'priority' => 'medium',
                'created_at' => '2024-11-27',
                'description' => 'Macro to process Mass Payment data.',
                'status' => 'finish',
                'attachment' => null,
                'updated_at' => '2024-12-2',

            ],
            [
                'name' => 'Rudi',
                'npk' => '140023',
                'priority' => 'medium',
                'created_at' => '2024-12-3',
                'description' => 'Macro to input tax data into E-Faktur.',
                'status' => 'finish',
                'attachment' => null,
                'updated_at' => '2024-12-13',
            ],
            [
                'name' => 'Rudi',
                'npk' => '140023',
                'priority' => 'high',
                'created_at' => '2024-12-3',
                'description' => 'Macro to automatically generate statement of account.',
                'status' => 'finish',
                'attachment' => null,
                'updated_at' => '2024-12-23',
            ],
            [
                'name' => 'Rudi',
                'npk' => '140023',
                'priority' => 'high',
                'created_at' => '2024-12-9',
                'description' => 'Macro to generate Bill of Material.',
                'status' => 'finish',
                'attachment' => null,
                'updated_at' => '2024-12-26',
            ],
            [
                'name' => 'Rudi',
                'npk' => '140023',
                'priority' => 'high',
                'created_at' => '2025-01-6',
                'description' => 'Develop an application to calculate Process Cost.',
                'status' => 'finish',
                'attachment' => null,
                'updated_at' => '2025-03-27',
            ],
            [
                'name' => 'Ayu',
                'npk' => '190349',
                'priority' => 'medium',
                'created_at' => '2025-02-10',
                'description' => 'Macro to report withholding tax certificates in Coretax.',
                'status' => 'finish',
                'attachment' => null,
                'updated_at' => '2025-02-11',
            ],
            [
                'name' => 'Ayu',
                'npk' => '190349',
                'priority' => 'low',
                'created_at' => '2025-02-10',
                'description' => 'Macro to fetch exchange rates (EUR, JPY, USD) from BI website.',
                'status' => 'finish',
                'attachment' => null,
                'updated_at' => '2025-02-12',
            ],
            [
                'name' => 'Rudi',
                'npk' => '140023',
                'priority' => 'urgent',
                'created_at' => '2025-05-12',
                'description' => 'Develop an application to explode Bill of Material.',
                'status' => 'finish',
                'attachment' => null,
                'updated_at' => '2025-6-2',
            ],
            [
                'name' => 'Setyaningsih',
                'npk' => '140023',
                'priority' => 'high',
                'created_at' => '2025-06-3',
                'description' => 'Develop an internal Request of Service application for Finance.',
                'status' => 'finish',
                'attachment' => null,
                'updated_at' => '2025-07-8',
            ],
            [
                'name' => 'Rudi',
                'npk' => '140023',
                'priority' => 'high',
                'created_at' => '2025-07-12',
                'description' => 'Develop an feature to export BOM per Product.',
                'status' => 'finish',
                'attachment' => null,
                'updated_at' => '2025-07-26',
            ],
            [
                'name' => 'Tuti',
                'npk' => '140025',
                'priority' => 'high',
                'created_at' => '2025-07-29',
                'description' => 'Modify dialog after updating master data.',
                'status' => 'in_progress',
                'attachment' => null,
                'updated_at' => '2025-07-29',
            ],
            [
                'name' => 'Tuti',
                'npk' => '140025',
                'priority' => 'high',
                'created_at' => '2025-07-29',
                'description' => 'Creating new input for OPEX dan Profit Margin in Standart Cost.',
                'status' => 'in_progress',
                'attachment' => null,
                'updated_at' => '2025-07-29',
            ],
        ];


        for ($i = 0; $i < count($request); $i++) {
            $u = RequestForService::create([
                'name' => $request[$i]['name'],
                'npk' => $request[$i]['npk'],
                'priority' => $request[$i]['priority'],
                'created_at' => $request[$i]['created_at'],
                'description' => $request[$i]['description'],
                'status' => $request[$i]['status'],
                'attachment' => $request[$i]['attachment'],
                'updated_at' => $request[$i]['updated_at'],
            ]);
        }
    }
}
