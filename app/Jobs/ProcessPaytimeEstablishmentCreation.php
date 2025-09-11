<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\User;
use App\Models\Vendedor;

class ProcessPaytimeEstablishmentCreation implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    public $payload;

    public function __construct(array $payload)
    {
        $this->payload = $payload;
    }

    public function handle(): void
    {
        $event = $this->payload['event'] ?? null;
        $data = $this->payload['data'] ?? [];

        if ($event !== 'new-establishment') {
            Log::warning("Webhook ignorado: evento não reconhecido", ['event' => $event]);
            return;
        }

        // Verificação única: Estabelecimento já existe
        $vendedorExistente = Vendedor::where('estabelecimento_id', $data['id'])->first();
        if ($vendedorExistente) {
            Log::info("Estabelecimento já processado", [
                'estabelecimento_id' => $data['id'],
                'vendedor_id' => $vendedorExistente->id
            ]);
            return; // Já foi processado
        }

        // Processar criação
        DB::transaction(function () use ($data) {
            // Criar usuário
            $user = User::create([
                'name' => $data['first_name'] . ' ' . $data['last_name'],
                'email' => $data['email'],
                'password' => Hash::make($data['id']), // Senha = ID da loja
                'nivel_acesso' => 'vendedor',
                'email_verified_at' => now(),
            ]);

            // Criar vendedor
            Vendedor::create([
                'user_id' => $user->id,
                'estabelecimento_id' => $data['id'],
                'sub_nivel' => 'admin_loja',
                'status' => 'ativo',
                'telefone' => $data['phone_number'],
                'endereco' => json_encode($data['address']),
            ]);

            Log::info("vendedor criado", [
                'estabelecimento_id' => $data['id'],
                'user_id' => $user->id,
                'email' => $data['email']
            ]);
        });
    }
}