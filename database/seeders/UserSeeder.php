<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Superadmin
        $superadmin = User::create([
            'email' => 'superadmin@besphere.com',
            'username' => 'superadmin',
            'password' => Hash::make('password'),
            'name' => 'Super Administrator',
            'nik' => 'SA001',
            'phone_number' => '081234567890',
            'role_id' => 1,
            'department_id' => null,
            'created_by' => null,
            'is_active' => true,
        ]);

        // 2. Admin Warehouse (Zaki)
        $zaki = User::create([
            'email' => 'zaki@besphere.com',
            'username' => 'zaki',
            'password' => Hash::make('password'),
            'name' => 'Zaki',
            'nik' => 'WH001',
            'phone_number' => '081234567891',
            'role_id' => 3, // admin-warehouse
            'department_id' => 2,
            'created_by' => $superadmin->id,
            'is_active' => true,
        ]);

        // 3. Admin Finance (Ahmad)
        $ahmad = User::create([
            'email' => 'ahmad@besphere.com',
            'username' => 'ahmad',
            'password' => Hash::make('password'),
            'name' => 'Ahmad',
            'nik' => 'FIN001',
            'phone_number' => '081234567892',
            'role_id' => 4, // admin-finance
            'department_id' => 3,
            'created_by' => $superadmin->id,
            'is_active' => true,
        ]);

        // 4. Operator Warehouse (Ichwan) - created by Zaki
        User::create([
            'email' => 'ichwan@besphere.com',
            'username' => 'ichwan',
            'password' => Hash::make('password'),
            'name' => 'Ichwan',
            'nik' => 'WH002',
            'phone_number' => '081234567893',
            'role_id' => 7, // operator-warehouse
            'department_id' => 2,
            'created_by' => $zaki->id,
            'is_active' => true,
        ]);
    }
}