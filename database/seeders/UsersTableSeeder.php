<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('users')->insert([
            [
                'name' => 'Admin User',
                'email' => 'admin@news.com',
                'password' => Hash::make('password'), // Encrypt password
                'is_admin' => true,
            ],
            [
                'name' => 'Regular User',
                'email' => 'user@news.com',
                'password' => Hash::make('password'),
                'is_admin' => false,
            ],
        ]);
    }
}
