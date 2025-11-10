<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            // Level 1: Superadmin
            ['name' => 'Super Admin', 'slug' => 'superadmin', 'level' => 1, 'description' => 'Highest level admin with full access'],
            
            // Level 2: Admin
            ['name' => 'Admin', 'slug' => 'admin', 'level' => 2, 'description' => 'Department Admin'],
            
            // Level 3: Operator
            ['name' => 'Operator', 'slug' => 'operator', 'level' => 3, 'description' => 'Department Operator'],
        ];

        foreach ($roles as $role) {
            Role::create($role);
        }
    }
}