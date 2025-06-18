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
        $reviewer = Role::create(['name' => 'Reviewer']);
        $userRole = Role::create(['name' => 'User']);
        $admin = Role::create(['name' => 'Admin']);

        // 2. Permissions
        Permission::create(['name' => 'Approve']);
        Permission::create(['name' => 'Reject']);
        Permission::create(['name' => 'CreateRequest']);
        Permission::create(['name' => 'CreateUser']);

        // 3. Assign Permissions
        $reviewer->givePermissionTo(['Approve', 'Reject']);
        $userRole->givePermissionTo(['CreateRequest']);
        $admin->givePermissionTo(['CreateUser', 'Approve', 'Reject', 'CreateRequest']);

        // 4. Users
        $users = [
            ['name' => 'Inge', 'npk' => '100101', 'role' => 'Reviewer'],
            ['name' => 'Rudi', 'npk' => '140023', 'role' => 'User'],
            ['name' => 'Setyaningsih', 'npk' => '140207', 'role' => 'User'],
            ['name' => 'Ayu', 'npk' => '190349', 'role' => 'User'],
            ['name' => 'Matsuyama', 'npk' => '240458', 'role' => 'Reviewer'],
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
                'name'=> 'Ayu',
                'npk' => '190349',
                'priority'=> 'medium',
                'input_date'=> '2024-11-20',
                'description'=> 'Macro untuk olah data kreditkan pajak di E-Faktur.',
                'status' => 'finish',
                'attachment' => null
            ],
            [
                'name'=> 'Setyaningsih',
                'npk'=> '140207',
                'priority'=> 'medium',
                'input_date'=> '2024-11-27',
                'description'=> 'Macro untuk olah data Mass Payment.',
                'status'=> 'finish',
                'attachment' => null
            ],
            [
                'name'=> 'Rudi',
                'npk'=>'140023',
                'priority'=>'medium',
                'input_date'=>'2024-12-3',
                'description'=> 'Macro untuk input pajak di E-Faktur.',
                'status'=>'finish',
                'attachment'=> null
            ],
            [
                'name'=> 'Rudi',
                'npk'=>'140023',
                'priority'=>'high',
                'input_date'=>'2024-12-3',
                'description'=> 'Macro untuk generate statement of account otomatis.',
                'status'=>'finish',
                'attachment'=> null
            ],
            [
                'name'=> 'Rudi',
                'npk'=>'140023',
                'priority'=>'high',
                'input_date'=>'2024-12-9',
                'description'=> 'Macro untuk generate Bill of Material.',
                'status'=>'finish',
                'attachment'=> null
            ],
            [
                'name'=> 'Rudi',
                'npk'=>'140023',
                'priority'=>'high',
                'input_date'=>'2025-01-6',
                'description'=> 'Buat aplikasi untuk hitung Process Cost.',
                'status'=>'finish',
                'attachment'=> null
            ],
            [
                'name'=> 'Ayu',
                'npk' => '190349',
                'priority'=> 'medium',
                'input_date'=> '2025-02-10',
                'description'=> 'Macro untuk lapor bukti potong di Coretax.',
                'status' => 'finish',
                'attachment' => null
            ],
            [
                'name'=> 'Ayu',
                'npk' => '190349',
                'priority'=> 'low',
                'input_date'=> '2025-02-10',
                'description'=> 'Macro untuk ambil data Kurs (EUR,JPY,USD) dari website BI.',
                'status' => 'finish',
                'attachment' => null
            ],
            [
                'name'=> 'Rudi',
                'npk'=>'140023',
                'priority'=>'urgent',
                'input_date'=>'2025-05-12',
                'description'=> 'Buat aplikasi untuk explode Bill of Material.',
                'status'=>'finish',
                'attachment'=> null
            ],
            [
                'name'=> 'Setyaningsih',
                'npk'=>'140023',
                'priority'=>'high',
                'input_date'=>'2025-06-3',
                'description'=> 'Buat aplikasi Request of Service internal Finance.',
                'status'=>'in_progress',
                'attachment'=> null
            ],
        ];

        for ($i = 0; $i < count($request); $i++){
                $u = RequestForService::create([
                    'name'=> $request[$i]['name'],
                    'npk'=> $request[$i]['npk'],
                    'priority'=> $request[$i]['priority'],
                    'input_date'=> $request[$i]['input_date'],
                    'description'=> $request[$i]['description'],
                    'status'=> $request[$i]['status'],
                    'attachment'=> $request[$i]['attachment'],
                ]);
            }
        }
}
