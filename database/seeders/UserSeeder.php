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
            'password' => Hash::make('123456'),
            'nivel_acesso' => 'super_admin',
        ]);

        // Criar usuário Admin
        User::create([
            'name' => 'Administrador',
            'email' => 'admin@juntter.com',
            'password' => Hash::make('123456'),
            'nivel_acesso' => 'admin',
        ]);

        // Criar usuário Vendedor
        User::create([
            'name' => 'Vendedor',
            'email' => 'vendedor@juntter.com',
            'password' => Hash::make('123456'),
            'nivel_acesso' => 'vendedor',
        ]);

        // Criar usuário Comprador
        User::create([
            'name' => 'Comprador',
            'email' => 'comprador@juntter.com',
            'password' => Hash::make('123456'),
            'nivel_acesso' => 'comprador',
        ]);
    }
}
