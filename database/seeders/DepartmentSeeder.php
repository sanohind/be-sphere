<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        $departments = [
            ['name' => 'Information Technology', 'code' => 'IT', 'description' => 'IT Department'],
            ['name' => 'Warehouse', 'code' => 'WH', 'description' => 'Warehouse Department'],
            ['name' => 'Finance', 'code' => 'FIN', 'description' => 'Finance Department'],
            ['name' => 'Logistics', 'code' => 'LOG', 'description' => 'Logistics Department'],
            ['name' => 'Human Resources', 'code' => 'HR', 'description' => 'HR Department'],
        ];

        foreach ($departments as $dept) {
            Department::create($dept);
        }
    }
}