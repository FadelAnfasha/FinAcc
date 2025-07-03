<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Hash;
use App\Models\RequestForService;
use Illuminate\Support\Arr;



class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Roles
        $superior = Role::create(['name' => 'Superior']);
        $userRole = Role::create(['name' => 'User']);
        $admin = Role::create(['name' => 'Admin']);

        // 2. Permissions
        Permission::create(['name' => 'Approve']);
        Permission::create(['name' => 'Reject']);
        Permission::create(['name' => 'Execute']);
        Permission::create(['name' => 'Finish']);
        Permission::create(['name' => 'CreateRequest']);
        Permission::create(['name' => 'CreateUser']);

        // 3. Assign Permissions
        $superior->givePermissionTo(['CreateRequest', 'CreateUser']);
        $userRole->givePermissionTo(['CreateRequest',]);
        $admin->givePermissionTo(['CreateUser', 'Approve', 'Reject', 'CreateRequest', 'Execute', 'Finish']);

        // 4. Users
        $users = [
            ['name' => 'Tuti', 'npk' => '140025', 'role' => 'Superior'],
            ['name' => 'Inge', 'npk' => '100101', 'role' => 'User'],
            ['name' => 'Rudi', 'npk' => '140023', 'role' => 'User'],
            ['name' => 'Setyaningsih', 'npk' => '140207', 'role' => 'User'],
            ['name' => 'Ayu', 'npk' => '190349', 'role' => 'User'],
            ['name' => 'Matsuyama', 'npk' => '240458', 'role' => 'User'],
            ['name' => 'Fadel', 'npk' => '240473', 'role' => 'Admin'],
        ];

        foreach ($users as $data) {
            $u = User::create([
                'name' => $data['name'],
                'npk' => $data['npk'],
                'password' => Hash::make('qweasdzxc'),
            ]);

            $u->assignRole($data['role']);
        }

        $request = [
            [
                'name' => 'Ayu',
                'npk' => '190349',
                'priority' => 'medium',
                'input_date' => '2024-11-20',
                'description' => 'Macro to process VAT crediting data in E-Faktur.',
                'status' => 'finish',
                'attachment' => null
            ],
            [
                'name' => 'Setyaningsih',
                'npk' => '140207',
                'priority' => 'medium',
                'input_date' => '2024-11-27',
                'description' => 'Macro to process Mass Payment data.',
                'status' => 'finish',
                'attachment' => null
            ],
            [
                'name' => 'Rudi',
                'npk' => '140023',
                'priority' => 'medium',
                'input_date' => '2024-12-3',
                'description' => 'Macro to input tax data into E-Faktur.',
                'status' => 'finish',
                'attachment' => null
            ],
            [
                'name' => 'Rudi',
                'npk' => '140023',
                'priority' => 'high',
                'input_date' => '2024-12-3',
                'description' => 'Macro to automatically generate statement of account.',
                'status' => 'finish',
                'attachment' => null
            ],
            [
                'name' => 'Rudi',
                'npk' => '140023',
                'priority' => 'high',
                'input_date' => '2024-12-9',
                'description' => 'Macro to generate Bill of Material.',
                'status' => 'finish',
                'attachment' => null
            ],
            [
                'name' => 'Rudi',
                'npk' => '140023',
                'priority' => 'high',
                'input_date' => '2025-01-6',
                'description' => 'Develop an application to calculate Process Cost.',
                'status' => 'finish',
                'attachment' => null
            ],
            [
                'name' => 'Ayu',
                'npk' => '190349',
                'priority' => 'medium',
                'input_date' => '2025-02-10',
                'description' => 'Macro to report withholding tax certificates in Coretax.',
                'status' => 'finish',
                'attachment' => null
            ],
            [
                'name' => 'Ayu',
                'npk' => '190349',
                'priority' => 'low',
                'input_date' => '2025-02-10',
                'description' => 'Macro to fetch exchange rates (EUR, JPY, USD) from BI website.',
                'status' => 'finish',
                'attachment' => null
            ],
            [
                'name' => 'Rudi',
                'npk' => '140023',
                'priority' => 'urgent',
                'input_date' => '2025-05-12',
                'description' => 'Develop an application to explode Bill of Material.',
                'status' => 'finish',
                'attachment' => null
            ],
            [
                'name' => 'Setyaningsih',
                'npk' => '140023',
                'priority' => 'high',
                'input_date' => '2025-06-3',
                'description' => 'Develop an internal Request of Service application for Finance.',
                'status' => 'in_progress',
                'attachment' => null
            ],
        ];


        for ($i = 0; $i < count($request); $i++) {
            $u = RequestForService::create([
                'name' => $request[$i]['name'],
                'npk' => $request[$i]['npk'],
                'priority' => $request[$i]['priority'],
                'input_date' => $request[$i]['input_date'],
                'description' => $request[$i]['description'],
                'status' => $request[$i]['status'],
                'attachment' => $request[$i]['attachment'],
            ]);
        }
    }
}
