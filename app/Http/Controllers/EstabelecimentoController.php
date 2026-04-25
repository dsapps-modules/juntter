<?php

namespace App\Http\Controllers;

use App\Models\PaytimeEstablishment;
use App\Services\EstabelecimentoService;
use App\Services\SplitPreService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class EstabelecimentoController extends Controller
{
    public function __construct(
        protected EstabelecimentoService $estabelecimentoService,
        protected SplitPreService $splitPreService
    ) {}

    public function index()
    {
        return redirect('/app/estabelecimentos');
    }

    public function export(): \Illuminate\Http\Response
    {
        $estabelecimentos = PaytimeEstablishment::query()
            ->orderByRaw("COALESCE(NULLIF(fantasy_name, ''), first_name, 'ZZZ') ASC")
            ->get();
        $estabelecimentoColumnWidth = max(
            18,
            $estabelecimentos->max(fn (PaytimeEstablishment $estabelecimento): int => Str::length($estabelecimento->display_name))
        );

        $fileName = 'estabelecimentos-'.now()->format('Y-m-d').'.xls';

        return response()
            ->view('estabelecimentos.export', compact('estabelecimentos', 'estabelecimentoColumnWidth'))
            ->header('Content-Type', 'application/vnd.ms-excel; charset=UTF-8')
            ->header('Content-Disposition', "attachment; filename=\"{$fileName}\"");
    }

    public function search(Request $request)
    {
        $term = $request->get('q');

        $query = PaytimeEstablishment::query();

        if ($term) {
            $query->where(function ($builder) use ($term): void {
                $builder->where('fantasy_name', 'like', "%{$term}%")
                    ->orWhere('first_name', 'like', "%{$term}%")
                    ->orWhere('last_name', 'like', "%{$term}%")
                    ->orWhere('document', 'like', "%{$term}%");
            });
        }

        $estabelecimentos = $query
            ->orderByRaw("COALESCE(NULLIF(fantasy_name, ''), first_name, 'ZZZ') ASC")
            ->limit(20)
            ->get();

        $results = $estabelecimentos->map(function ($item): array {
            return [
                'id' => $item->id,
                'text' => $item->display_name.' ('.$item->document.')',
            ];
        });

        return response()->json(['results' => $results]);
    }

    public function show($id)
    {
        return redirect('/app/estabelecimentos/'.$id.'/editar');
    }

    public function edit($id)
    {
        return redirect('/app/estabelecimentos/'.$id.'/editar');
    }

    public function update(Request $request, $id): JsonResponse|\Illuminate\Http\RedirectResponse
    {
        try {
            $dados = $request->validate([
                'access_type' => 'required|in:ACQUIRER,BANKING',
                'first_name' => 'nullable|string|max:255',
                'last_name' => 'nullable|string|max:255',
                'phone_number' => 'required|string|max:20',
                'revenue' => 'required|numeric',
                'format' => 'required|in:SS,SC,SPE,LTDA,SA,ME,MEI,EI,EIRELI,SLU,ESI',
                'email' => 'required|email',
                'gmv' => 'nullable|numeric',
                'birthdate' => 'required|date_format:Y-m-d',
            ]);

            $dados['revenue'] = (float) $dados['revenue'];
            if (! empty($dados['gmv'])) {
                $dados['gmv'] = (float) $dados['gmv'];
            } else {
                unset($dados['gmv']);
            }

            $this->estabelecimentoService->atualizarEstabelecimento($id, $dados);

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Estabelecimento atualizado com sucesso!',
                    'redirect' => '/app/estabelecimentos',
                ]);
            }

            return redirect('/app/estabelecimentos/'.$id.'/editar')
                ->with('success', 'Estabelecimento atualizado com sucesso!');
        } catch (\Exception $e) {
            Log::error('Erro ao atualizar estabelecimento: '.$e->getMessage());

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Erro ao atualizar estabelecimento.',
                ], 500);
            }

            return back()->withInput()->with('error', 'Erro ao atualizar estabelecimento.');
        }
    }

    public function criarRegraSplit(Request $request, $id)
    {
        try {
            $dados = $request->validate([
                'title' => 'required|string|max:255',
                'modality' => 'required|in:ALL,CREDIT,DEBIT,PIX',
                'channel' => 'required|in:ALL,CHIP,TAP,SMART,ONLINE',
                'division' => 'required|in:PERCENTAGE,CURRENCY',
                'active' => 'nullable',
                'installment' => 'nullable|integer|min:1|max:12',
                'establishments' => 'required|array|min:1',
                'establishments.*.id' => 'required|integer',
                'establishments.*.value' => 'required|integer|min:1',
                'establishments.*.active' => 'nullable',
            ]);

            $dados = $this->tratarDadosSplit($dados);
            $this->splitPreService->criarRegraSplitPre($id, $dados);

            return redirect()->route('estabelecimentos.show', $id)
                ->with('success', 'Regra de split criada com sucesso!');
        } catch (\Exception $e) {
            Log::error('Erro ao criar regra de split: '.$e->getMessage());

            return back()->withInput()->with('error', 'Erro ao criar regra de split: '.$e->getMessage());
        }
    }

    private function tratarDadosSplit(array $dados): array
    {
        $dados['active'] = isset($dados['active']);

        if (empty($dados['installment'])) {
            unset($dados['installment']);
        }

        foreach ($dados['establishments'] as $key => $establishment) {
            $dados['establishments'][$key]['active'] = isset($establishment['active']);
            $dados['establishments'][$key]['id'] = (int) $establishment['id'];
            $dados['establishments'][$key]['value'] = (int) $establishment['value'];
        }

        return $dados;
    }

    public function consultarRegraSplit($id, $splitId)
    {
        try {
            $estabelecimento = $this->estabelecimentoService->buscarEstabelecimento($id);
            $regra = $this->splitPreService->consultarRegraSplitPre($id, $splitId);

            return view('estabelecimentos.regra-detalhes', compact('estabelecimento', 'regra'));
        } catch (\Exception $e) {
            Log::error('Erro ao consultar regra de split: '.$e->getMessage());

            return redirect()->route('estabelecimentos.show', $id)
                ->with('error', 'Erro ao consultar regra de split.');
        }
    }

    public function deletarRegraSplit($id, $splitId)
    {
        try {
            $this->splitPreService->deletarRegraSplitPre($id, $splitId);

            return redirect()->route('estabelecimentos.show', $id)
                ->with('success', 'Regra de split excluída com sucesso!');
        } catch (\Exception $e) {
            Log::error('Erro ao deletar regra de split: '.$e->getMessage());

            return back()->with('error', 'Erro ao excluir regra de split.');
        }
    }
}
