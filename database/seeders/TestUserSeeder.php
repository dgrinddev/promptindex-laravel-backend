<?php

namespace Database\Seeders;

use App\Models\User;
//use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TestUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $testUser = User::factory()->create([
            'name' => env('USER_TEST_NAME'),
            'email' => env('USER_TEST_EMAIL'),
            'password' => Hash::make(env('USER_TEST_PASSWORD')),
        ]);
    }
}
