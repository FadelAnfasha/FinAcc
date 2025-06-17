<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Hash;


class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Buat role
        $admin = Role::create(['name' => 'Mythic']);
        $reviewer = Role::create(['name' => 'Legend']);
        $user = Role::create(attributes: ['name' => 'Epic']);

        // Buat permission
        Permission::create(['name' => 'Approve']);
        Permission::create(['name' => 'Reject']);
        Permission::create(['name' => 'View']);


        // Assign permission ke role
        $reviewer->givePermissionTo(['Approve', 'Reject','View']);
        $user->givePermissionTo(['View']);

        $users = [
            ['name' => 'Inge', 'npk' => '100101'],
            ['name' => 'Rudi', 'npk' => '140023'],
            ['name' => 'Setyaningsi', 'npk' => '140207'],
            ['name' => 'Ayu', 'npk' => '190349'],
            ['name' => 'Matsuyama', 'npk' => '240458'],
            ['name' => 'Fadel', 'npk' => '240473'],
        ];

        foreach ($users as $user) {
            User::create([
                'name' => $user['name'],
                'npk' => $user['npk'],
                'password' => Hash::make('qweasdzxc'),
            ]);
        }
    }
}
