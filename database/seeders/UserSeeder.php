<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $roleSuperadmin = Role::where('slug', 'superadmin')->first();

        User::firstOrCreate(
            ['username' => 'superadmin'],
            [
                'email'        => 'superadmin@besphere.com',
                'password'     => Hash::make('password'),
                'name'         => 'Super Administrator',
                'nik'          => null,
                'phone_number' => '081234567890',
                'role_id'      => $roleSuperadmin?->id,
                'department_id'=> null,
                'created_by'   => null,
                'is_active'    => true,
            ]
        );
    }
}