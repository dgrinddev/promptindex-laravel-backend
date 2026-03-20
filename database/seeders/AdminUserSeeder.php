<?php

namespace Database\Seeders;

use App\Models\User;
//use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $adminUser = User::factory()->create([
            'name' => env('USER_ADMIN_NAME'),
            'email' => env('USER_ADMIN_EMAIL'),
            'password' => Hash::make(env('USER_ADMIN_PASSWORD')),
            'role' => 'admin',
        ]);
    }
}
