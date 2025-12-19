<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\EstabelecimentoService;
use App\Models\PaytimeEstablishment;
use Illuminate\Support\Facades\Log;

class SyncPaytimeEstablishments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'paytime:sync-establishments';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sincroniza dados de estabelecimentos da API Paytime para banco local';

    /**
     * Execute the console command.
     */
    public function handle(EstabelecimentoService $service)
    {
        $this->info('Iniciando sincronização de estabelecimentos...');

        try {
            // A API de listarEstabelecimentos pode ou não ser paginada.
            // Pelo código anterior, parecia retornar tudo em 'data'.
            // Vamos assumir que retorna tudo ou tratar paginação simples se houver links.
            // O EstabelecimentoService::listarEstabelecimentos chama GET marketplace/establishments

            $page = 1;
            $limit = 100; // Aumentar limite para eficiência
            $totalSynced = 0;

            $this->info("Iniciando sincronização (limit {$limit}/pág)...");

            do {
                $this->info("Buscando página {$page}...");

                $response = $service->listarEstabelecimentos($page, $limit);
                $items = $response['data'] ?? []; // Ajuste conforme estrutura real

                // Se a API retornar "data" vazio ou não retornar data, paramos
                if (empty($items)) {
                    break;
                }

                $count = count($items);
                $this->info("Encontrados {$count} registros na página {$page}. Processando...");

                $bar = $this->output->createProgressBar($count);
                $bar->start();

                foreach ($items as $item) {
                    $id = $item['id'] ?? ($item['_id'] ?? null);
                    if (!$id) {
                        $bar->advance();
                        continue;
                    }

                    PaytimeEstablishment::updateOrCreate(
                        ['id' => $id],
                        [
                            'type' => $item['type'] ?? null,
                            'first_name' => $item['first_name'] ?? null,
                            'last_name' => $item['last_name'] ?? null,
                            'fantasy_name' => $item['fantasy_name'] ?? null,
                            'document' => $item['document'] ?? null,
                            'email' => $item['email'] ?? null,
                            'phone_number' => $item['phone_number'] ?? null,
                            'active' => $item['active'] ?? true,
                            'status' => $item['status'] ?? null,
                            'risk' => $item['risk'] ?? null,
                            'category' => $item['category'] ?? null,
                            'code' => $item['code'] ?? null,
                            'revenue' => $item['revenue'] ?? null,
                            'address_json' => $item['address'] ?? null,
                            'responsible_json' => $item['responsible'] ?? null,
                        ]
                    );

                    $totalSynced++;
                    $bar->advance();
                }

                $bar->finish();
                $this->newLine();

                // Se retornou menos que o limite, provavelmente é a última página
                if ($count < $limit) {
                    break;
                }

                $page++;
                // Sleep curto para evitar rate limit se necessário.
                // usleep(200000); 

            } while (true);

            $this->info("Sincronização concluída! Total: {$totalSynced} estabelecimentos.");

        } catch (\Exception $e) {
            Log::error("Erro na sync de estabelecimentos: " . $e->getMessage());
            $this->error("Erro: " . $e->getMessage());
        }
    }
}
