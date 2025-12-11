<?php

namespace Database\Seeders;

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
        // Super Admin
        // $super = User::create([
        //     'name' => 'Super Administrador',
        //     'email' => 'superadmin@juntter.com',
        //     'password' => Hash::make('12345678'),
        // ]);
        // $super->nivel_acesso = 'super_admin';
        // $super->email_verified_at = now();
        // $super->save();

        // Admin
        $admin = User::create([
            'name' => 'Admin DS',
            'email' => 'admin@dsapps.com',
            'password' => Hash::make('@5224106rP'),
        ]);
        $admin->nivel_acesso = 'admin';
        $admin->email_verified_at = now();
        $admin->save();

        // Vendedor
        // $vend = User::create([
        //     'name' => 'Vendedor',
        //     'email' => 'vendedor@juntter.com',
        //     'password' => Hash::make('12345678'),
        // ]);
        // $vend->nivel_acesso = 'vendedor';
        // $vend->email_verified_at = now();
        // $vend->save();
    }
}