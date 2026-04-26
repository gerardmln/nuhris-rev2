<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $departments = [
            'ASP',
            'SABM - School of Accountancy, Business and Management',
            'SAHS - School of Allied Health and Sciences',
            'SACE - School of Architecture, Computing and Engineering',
            'SHS - Senior High School',
        ];

        foreach ($departments as $name) {
            Department::firstOrCreate(['name' => $name]);
        }
    }
}
