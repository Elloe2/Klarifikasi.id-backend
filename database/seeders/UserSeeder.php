<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create test user
        User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'birth_date' => '2000-01-01',
            'education_level' => 'kuliah',
            'institution' => 'Universitas Test',
        ]);

        // Create additional test users
        User::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => Hash::make('password123'),
            'birth_date' => '1995-05-15',
            'education_level' => 'sma',
            'institution' => 'SMA Negeri 1 Jakarta',
        ]);

        User::create([
            'name' => 'Jane Smith',
            'email' => 'jane@example.com',
            'password' => Hash::make('password123'),
            'birth_date' => '1998-08-20',
            'education_level' => 'kuliah',
            'institution' => 'Institut Teknologi Bandung',
        ]);
    }
}
