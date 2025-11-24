<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        $departments = [
            ['name' => 'Information Technology', 'code' => 'IT'],
            ['name' => 'Warehouse', 'code' => 'WH'],
            ['name' => 'Finance', 'code' => 'FIN'],
            ['name' => 'Logistics', 'code' => 'LOG'],
            ['name' => 'Human Resources', 'code' => 'HR'],
        ];

        foreach ($departments as $dept) {
            Department::create($dept);
        }
    }
}