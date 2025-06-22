<?php

namespace Database\Seeders;

use Carbon\Carbon;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'name' => 'Admin',
            'email' => 'admin@admin.com',
            'password' => bcrypt('12345678'),
            'role' => 'admin',
            'email_verified_at' => Carbon::now(),
        ]);
        User::create([
            'name' => 'User',
            'email' => 'user@user.com',
            'password' => bcrypt('12345678'),
            'role' => 'user',
            'email_verified_at' => Carbon::now(),
        ]);
        //receptionist
        User::create([
            'name' => 'Receptionist',
            'email' => 'receptionist@receptionist.com',
            'password' => bcrypt('12345678'),
            'role' => 'receptionist',
            'email_verified_at' => Carbon::now(),
        ]);
    }
}
