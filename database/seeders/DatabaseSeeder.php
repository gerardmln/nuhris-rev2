<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create users first
        User::updateOrCreate([
            'email' => 'test101A@gmail.com',
        ], [
            'name' => 'Test Admin',
            'user_type' => User::TYPE_ADMIN,
            'password' => Hash::make('abcd@1234'),
            'email_verified_at' => now(),
        ]);

        User::updateOrCreate([
            'email' => 'test101HR@gmail.com',
        ], [
            'name' => 'Test HR',
            'user_type' => User::TYPE_HR,
            'password' => Hash::make('abcd@1234'),
            'email_verified_at' => now(),
        ]);

        User::updateOrCreate([
            'email' => 'test101EMP@gmail.com',
        ], [
            'name' => 'Test Employee',
            'user_type' => User::TYPE_EMPLOYEE,
            'password' => Hash::make('abcd@1234'),
            'email_verified_at' => now(),
        ]);

        // Then call other seeders
        $this->call([
            DepartmentSeeder::class,
            EmployeeSeeder::class,
            EmployeePortalSeeder::class,
            AnnouncementSeeder::class,
        ]);
    }
}
