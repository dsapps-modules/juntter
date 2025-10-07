<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Criar usuário Super Admin
        User::create([
            'name' => 'Super Administrador',
            'email' => 'superadmin@juntter.com',
            'email_verified_at' => now(),
            'password' => Hash::make('12345678'),
            'nivel_acesso' => 'super_admin',
        ]);

        // Criar usuário Admin
        User::create([
            'name' => 'Administrador',
            'email' => 'admin@juntter.com',
            'email_verified_at' => now(),
            'password' => Hash::make('12345678'),
            'nivel_acesso' => 'admin',
        ]);

        // Criar usuário Vendedor
        User::create([
            'name' => 'Vendedor',
            'email' => 'vendedor@juntter.com',
            'email_verified_at' => now(),
            'password' => Hash::make('12345678'),
            'nivel_acesso' => 'vendedor',
        ]);
    }
}
