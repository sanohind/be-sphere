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
            ['name' => 'Super Admin', 'slug' => 'superadmin', 'level' => 1, 'department_id' => null, 'description' => 'Highest level admin with full access'],
            
            // Level 2: Department Admins
            ['name' => 'Admin IT', 'slug' => 'admin-it', 'level' => 2, 'department_id' => 1, 'description' => 'IT Department Admin'],
            ['name' => 'Admin Warehouse', 'slug' => 'admin-warehouse', 'level' => 2, 'department_id' => 2, 'description' => 'Warehouse Department Admin'],
            ['name' => 'Admin Finance', 'slug' => 'admin-finance', 'level' => 2, 'department_id' => 3, 'description' => 'Finance Department Admin'],
            ['name' => 'Admin Logistics', 'slug' => 'admin-logistics', 'level' => 2, 'department_id' => 4, 'description' => 'Logistics Department Admin'],
            
            // Level 3: Department Operators
            ['name' => 'Operator IT', 'slug' => 'operator-it', 'level' => 3, 'department_id' => 1, 'description' => 'IT Operator'],
            ['name' => 'Operator Warehouse', 'slug' => 'operator-warehouse', 'level' => 3, 'department_id' => 2, 'description' => 'Warehouse Operator'],
            ['name' => 'Operator Finance', 'slug' => 'operator-finance', 'level' => 3, 'department_id' => 3, 'description' => 'Finance Operator'],
            ['name' => 'Operator Logistics', 'slug' => 'operator-logistics', 'level' => 3, 'department_id' => 4, 'description' => 'Logistics Operator'],
            
            // Level 4: Basic User
            ['name' => 'User', 'slug' => 'user', 'level' => 4, 'department_id' => null, 'description' => 'Basic User'],
        ];

        foreach ($roles as $role) {
            Role::create($role);
        }
    }
}