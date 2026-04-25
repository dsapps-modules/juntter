<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreVendedorAcessoRequest;
use App\Http\Requests\UpdateVendedorAcessoRequest;
use App\Http\Requests\UpdateVendedorSenhaRequest;
use App\Models\PaytimeEstablishment;
use App\Models\User;
use App\Services\EstabelecimentoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class VendedorController extends Controller
{
    public function __construct(protected EstabelecimentoService $estabelecimentoService) {}

    public function faturamento(Request $request): RedirectResponse
    {
        return redirect('/app/vendedores/faturamento');
    }

    public function acesso(Request $request): RedirectResponse
    {
        return redirect('/app/vendedores/acesso');
    }

    public function searchEstabelecimentosAvailable(Request $request): JsonResponse
    {
        $term = $request->get('q');

        $usedIds = User::query()
            ->where('nivel_acesso', 'vendedor')
            ->with('vendedor')
            ->get()
            ->pluck('vendedor.estabelecimento_id')
            ->filter()
            ->toArray();

        $query = PaytimeEstablishment::query()->whereNotIn('id', $usedIds);

        if ($term) {
            $query->where(function ($builder) use ($term): void {
                $builder->where('fantasy_name', 'like', "%{$term}%")
                    ->orWhere('first_name', 'like', "%{$term}%")
                    ->orWhere('last_name', 'like', "%{$term}%")
                    ->orWhere('document', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%");
            });
        }

        $results = $query->limit(20)->get()->map(function ($item): array {
            $nome = $item->display_name;

            if (empty($nome) || $nome === 'Sem Nome') {
                $nome = $item->document ?? $item->email;
            }

            return [
                'id' => $item->id,
                'text' => $nome.' (ID: '.$item->id.')',
                'email' => $item->email,
                'name_clean' => $nome,
            ];
        });

        return response()->json(['results' => $results]);
    }

    public function storeAcesso(StoreVendedorAcessoRequest $request): JsonResponse|RedirectResponse
    {
        DB::beginTransaction();

        try {
            $user = User::create([
                'name' => $request->validated('name'),
                'email' => $request->validated('email'),
                'password' => Hash::make($request->validated('password')),
                'nivel_acesso' => 'vendedor',
                'email_verified_at' => now(),
            ]);

            $user->vendedor()->create([
                'estabelecimento_id' => $request->validated('establishment_id'),
                'sub_nivel' => 'admin_loja',
                'status' => 'ativo',
            ]);

            DB::commit();

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Acesso criado com sucesso.',
                    'redirect' => '/app/vendedores/acesso',
                ], 201);
            }

            return redirect('/app/vendedores/acesso')->with('success', 'Acesso criado com sucesso.');
        } catch (\Exception $e) {
            DB::rollBack();

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Erro ao criar acesso: '.$e->getMessage(),
                ], 500);
            }

            return back()->with('error', 'Erro ao criar acesso: '.$e->getMessage())->withInput();
        }
    }

    public function updateAcesso(UpdateVendedorAcessoRequest $request, int $id): JsonResponse|RedirectResponse
    {
        $user = User::findOrFail($id);

        if (! $user->isVendedor()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Ação não permitida para este usuário.',
                ], 403);
            }

            return back()->with('error', 'Ação não permitida para este usuário.');
        }

        $user->update([
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Dados do vendedor atualizados com sucesso.',
                'redirect' => '/app/vendedores/acesso',
            ]);
        }

        return redirect('/app/vendedores/acesso')->with('success', 'Dados do vendedor atualizados com sucesso.');
    }

    public function updateSenha(UpdateVendedorSenhaRequest $request, int $id): JsonResponse|RedirectResponse
    {
        $user = User::findOrFail($id);

        if (! $user->isVendedor()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Usuário inválido.',
                ], 403);
            }

            return back()->with('error', 'Usuário inválido.');
        }

        $user->update([
            'password' => Hash::make($request->validated('password')),
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Senha atualizada com sucesso.',
                'redirect' => '/app/vendedores/acesso',
            ]);
        }

        return redirect('/app/vendedores/acesso')->with('success', 'Senha atualizada com sucesso.');
    }

    public function destroyAcesso(Request $request, int $id): JsonResponse|RedirectResponse
    {
        $user = User::findOrFail($id);

        if (! $user->isVendedor()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Ação não permitida para este usuário.',
                ], 403);
            }

            return back()->with('error', 'Ação não permitida para este usuário.');
        }

        $user->delete();

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Acesso removido com sucesso.',
                'redirect' => '/app/vendedores/acesso',
            ]);
        }

        return redirect('/app/vendedores/acesso')->with('success', 'Acesso removido com sucesso.');
    }
}
