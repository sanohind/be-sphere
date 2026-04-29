<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        $departments = [
            ['name' => 'Chassis',                    'code' => 'CHS',  'is_active' => true],
            ['name' => 'Brazing',                    'code' => 'BRZ',  'is_active' => true],
            ['name' => 'Nylon',                      'code' => 'NYL',  'is_active' => true],
            ['name' => 'PPIC',                       'code' => 'PPIC', 'is_active' => true],
            ['name' => 'Logistics',                  'code' => 'LOG',  'is_active' => true],
            ['name' => 'Service Parts',              'code' => 'SVP',  'is_active' => true],
            ['name' => 'Quality Control',            'code' => 'QC',   'is_active' => true],
            ['name' => 'Quality Assurance',          'code' => 'QA',   'is_active' => true],
            ['name' => 'Engineering',                'code' => 'ENG',  'is_active' => true],
            ['name' => 'Maintenance',                'code' => 'MNT',  'is_active' => true],
            ['name' => 'Purchasing',                 'code' => 'PUR',  'is_active' => true],
            ['name' => 'Warehouse',                  'code' => 'WH',   'is_active' => true],
            ['name' => 'Marketing',                  'code' => 'MKT',  'is_active' => true],
            ['name' => 'Human Resource Development', 'code' => 'HRD',  'is_active' => true],
            ['name' => 'General Affairs',            'code' => 'GA',   'is_active' => true],
            ['name' => 'Information Technology',     'code' => 'IT',   'is_active' => true],
            ['name' => 'Safety',                     'code' => 'SHE',  'is_active' => true],
            ['name' => 'Accounting',                 'code' => 'ACC',  'is_active' => true],
            ['name' => 'Jishuken',                   'code' => 'JSK',  'is_active' => true],
        ];

        foreach ($departments as $dept) {
            Department::firstOrCreate(['code' => $dept['code']], $dept);
        }
    }
}