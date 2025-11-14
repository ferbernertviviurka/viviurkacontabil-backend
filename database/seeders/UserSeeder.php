<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Master User (Viviurka)
        User::create([
            'name' => 'Viviurka',
            'email' => 'viviurka@contabil.com',
            'password' => Hash::make('password'),
            'role' => 'master',
            'company_id' => null,
        ]);

        // Create Normal Users (without company_id initially)
        User::create([
            'name' => 'João Silva',
            'email' => 'joao@example.com',
            'password' => Hash::make('password'),
            'role' => 'normal',
            'company_id' => null,
        ]);

        User::create([
            'name' => 'Maria Santos',
            'email' => 'maria@example.com',
            'password' => Hash::make('password'),
            'role' => 'normal',
            'company_id' => null,
        ]);

        User::create([
            'name' => 'Pedro Oliveira',
            'email' => 'pedro@example.com',
            'password' => Hash::make('password'),
            'role' => 'normal',
            'company_id' => null,
        ]);
    }
}
