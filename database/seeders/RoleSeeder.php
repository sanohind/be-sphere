<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            // Level 1: Superadmin — hak penuh, bypass akses
            ['name' => 'Super Admin',          'slug' => 'superadmin',         'level' => 1, 'is_active' => true],

            // Level 2–8: Jabatan struktural (pengguna biasa)
            ['name' => 'President Director',   'slug' => 'president-director', 'level' => 2, 'is_active' => true],
            ['name' => 'Division Head',        'slug' => 'division-head',      'level' => 3, 'is_active' => true],
            ['name' => 'General Manager',      'slug' => 'general-manager',    'level' => 4, 'is_active' => true],
            ['name' => 'Manager',              'slug' => 'manager',            'level' => 5, 'is_active' => true],
            ['name' => 'Supervisor',           'slug' => 'supervisor',         'level' => 6, 'is_active' => true],
            ['name' => 'Leader',               'slug' => 'leader',             'level' => 7, 'is_active' => true],
            ['name' => 'Staff',                'slug' => 'staff',              'level' => 8, 'is_active' => true],
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(['slug' => $role['slug']], $role);
        }
    }
}