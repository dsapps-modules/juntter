<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Vendedor;
use Illuminate\Support\Facades\Hash;

class VendedorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Criar usuário admin de loja DS Aplicativos
        $adminLojaDS = User::create([
            'name' => 'Admin DS Aplicativos',
            'email' => 'admin-ds@teste.com',
            'password' => Hash::make('password'),
            'nivel_acesso' => 'vendedor',
            'email_verified_at' => now()
        ]);

        Vendedor::create([
            'user_id' => $adminLojaDS->id,
            'estabelecimento_id' => '155161', // ID real da API - DS Aplicativos
            'sub_nivel' => 'admin_loja',
            'comissao' => 5.00,
            'meta_vendas' => 50000.00,
            'telefone' => '(11) 99999-9999',
            'endereco' => 'Rua das Flores, 123 - São Paulo/SP',
            'status' => 'ativo'
        ]);

        // Criar usuário vendedor de loja DS Aplicativos
        $vendedorLojaDS = User::create([
            'name' => 'Vendedor DS Aplicativos',
            'email' => 'vendedor-ds@teste.com',
            'password' => Hash::make('password'),
            'nivel_acesso' => 'vendedor',
            'email_verified_at' => now()
        ]);

        Vendedor::create([
            'user_id' => $vendedorLojaDS->id,
            'estabelecimento_id' => '155161', // Mesmo estabelecimento - DS Aplicativos
            'sub_nivel' => 'vendedor_loja',
            'comissao' => 3.00,
            'meta_vendas' => 30000.00,
            'telefone' => '(11) 88888-8888',
            'endereco' => 'Rua das Palmeiras, 456 - São Paulo/SP',
            'status' => 'ativo'
        ]);

        // Criar usuário admin de loja Juntter
        $adminLojaJuntter = User::create([
            'name' => 'Admin Juntter',
            'email' => 'admin-juntter@teste.com',
            'password' => Hash::make('password'),
            'nivel_acesso' => 'vendedor',
            'email_verified_at' => now()
        ]);

        Vendedor::create([
            'user_id' => $adminLojaJuntter->id,
            'estabelecimento_id' => '155102', // ID real da API - Juntter
            'sub_nivel' => 'admin_loja',
            'comissao' => 4.50,
            'meta_vendas' => 40000.00,
            'telefone' => '(11) 77777-7777',
            'endereco' => 'Rua das Margaridas, 789 - São Paulo/SP',
            'status' => 'ativo'
        ]);

        $this->command->info('Usuários vendedores criados com sucesso!');
        $this->command->info('Admin DS Aplicativos: admin-ds@teste.com / password');
        $this->command->info('Vendedor DS Aplicativos: vendedor-ds@teste.com / password');
        $this->command->info('Admin Juntter: admin-juntter@teste.com / password');
    }
}
