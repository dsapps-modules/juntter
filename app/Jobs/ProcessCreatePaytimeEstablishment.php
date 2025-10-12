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

class ProcessCreatePaytimeEstablishment implements ShouldQueue
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
            Log::info("createEstablishment blocked: unknown event", ['event' => $event]);
            return;
        }

        // Verificação única: Estabelecimento já existe
        $vendedorExistente = Vendedor::where('estabelecimento_id', $data['id'])->first();
        if ($vendedorExistente) {
            Log::info("createEstablishment blocked: user already exists", [
                'estabelecimento_id' => $data['id'],
                'vendedor_id' => $vendedorExistente->id
            ]);
            return; // Já foi processado
        }

        // Processar criação
        try{
            DB::transaction(function () use ($data) {
                // Criar usuário
                $user = User::create([
                    'name' => $data['first_name'] . ' ' . $data['last_name'],
                    'email' => $data['email'],
                    'password' => Hash::make($data['id']), // Senha = ID da loja
                ]);
                $user->nivel_acesso = 'vendedor';
                $user->email_verified_at = now();
                $user->save();
    
                // Criar vendedor
                Vendedor::create([
                    'user_id' => $user->id,
                    'estabelecimento_id' => $data['id'],
                    'sub_nivel' => 'admin_loja',
                    'status' => 'ativo',
                    'telefone' => $data['phone_number'],
                    'endereco' => json_encode($data['address']),
                    'must_change_password' => true, 
                ]);
    
                Log::info("createEstablishment job finished succesfully", [
                    'estabelecimento_id' => $data['id'],
                    'user_id' => $user->id,
                    'email' => $data['email']
                ]);
            });
        } catch(\Throwable $e) {
            Log::error('createEstablishment failed: ' . $e->getMessage());
        }
    }
}

/*

Payload enviado via webhook quando um vendedor/estabelecimento é registrado no painel da Paytime

{
    "event":"new-establishment",
    "event_date":"2025-10-11T15:09:12.356Z",
    "data":{
        "id":155463,
        "type":"INDIVIDUAL",
        "first_name":"SONIA DE CASSIA SANTOS PRADO",
        "last_name":null,
        "document":"25984303876",
        "birthdate":"1976-03-24",
        "phone_number":"11920012001",
        "active":true,
        "revenue":"10000000",
        "format":null,
        "email":"soneca@home.com",
        "risk":"PENDING",
        "status":"RISK_ANALYSIS",
        "representative":null,
        "address":{
            "zip_code":"07174010",
            "street":"Rua Senador Nilo Coelho",
            "number":"111",
            "neighborhood":"Residencial Parque Cumbica",
            "city":"Guarulhos",
            "state":"SP",
            "complement":"(Cj Inocoop-Bonsucesso)"
        },
        "category":"Serviços Profissionais, Categorias Especiais - Outros",
        "code":"SONL8YRB",
        "created_at":"2025-10-11T15:09:00.000Z",
        "updated_at":"2025-10-11T15:09:02.000Z",
        "deleted_at":null,
        "responsible":{
            "id":433,
            "first_name":"SONIA DE CASSIA SANTOS PRADO",
            "email":"soneca@home.com",
            "document":"25984303876",
            "phone":"11920012001",
            "birthdate":"1976-03-24"
        },
        "gateways":[],
        "plans":[]
    }
} 

*/