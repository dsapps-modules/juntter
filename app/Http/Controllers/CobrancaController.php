<?php

namespace App\Http\Controllers;

use App\Services\TransacaoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CobrancaController extends Controller
{
    protected $transacaoService;

    public function __construct(TransacaoService $transacaoService)
    {
        $this->transacaoService = $transacaoService;
    }

    /**
     * Página principal de cobrança única
     */
    public function index(Request $request)
    {
        try {
            // Buscar todas as transações do estabelecimento atual
            $filtros = [
                'perPage' => 100, // Trazer mais registros para o DataTable
                'page' => 1
            ];

            $transacoes = $this->transacaoService->listarTransacoes($filtros);

            return view('cobranca.index', compact('transacoes'));
        } catch (\Exception $e) {
            Log::error('Erro ao listar transações: ' . $e->getMessage());
            return view('cobranca.index')->with('error', 'Erro ao carregar transações.');
        }
    }
}
