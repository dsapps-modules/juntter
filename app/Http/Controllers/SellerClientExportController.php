<?php

namespace App\Http\Controllers;

use App\Models\PaytimeTransaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class SellerClientExportController extends Controller
{
    public function export(Request $request): Response
    {
        $user = $request->user();
        $establishmentId = $user?->getEstabelecimentoId();

        abort_unless($user?->isVendedor() && $establishmentId !== null, 403);

        $transactions = PaytimeTransaction::query()
            ->where('establishment_id', (string) $establishmentId)
            ->where(function ($query): void {
                $query->where(function ($nestedQuery): void {
                    $nestedQuery->whereNotNull('customer_document')
                        ->where('customer_document', '!=', '');
                })->orWhere(function ($nestedQuery): void {
                    $nestedQuery->whereNotNull('customer_name')
                        ->where('customer_name', '!=', '');
                });
            })
            ->orderByDesc('created_at')
            ->get();

        $clientes = $this->buildClientes($transactions);

        $clienteColumnWidth = max(
            18,
            $clientes->isNotEmpty()
                ? $clientes->max(fn (array $cliente): int => Str::length($cliente['cliente']))
                : 18
        );

        $fileName = 'clientes-vendedor-'.now()->format('Y-m-d').'.xls';

        return response()
            ->view('seller.clientes.export', compact('clientes', 'clienteColumnWidth'))
            ->header('Content-Type', 'application/vnd.ms-excel; charset=UTF-8')
            ->header('Content-Disposition', "attachment; filename=\"{$fileName}\"");
    }

    /**
     * @return \Illuminate\Support\Collection<int, array{
     *     cliente: string,
     *     documento: string,
     *     transacoes: int,
     *     valor_total: string,
     *     primeira_transacao: string,
     *     ultima_transacao: string
     * }>
     */
    private function buildClientes(Collection $transactions): Collection
    {
        return $transactions
            ->groupBy(function (PaytimeTransaction $transaction): string {
                return $this->resolveClienteKey($transaction);
            })
            ->map(function (Collection $clienteTransactions): array {
                $latestTransaction = $clienteTransactions->first();
                $oldestTransaction = $clienteTransactions->last();
                $cliente = $this->resolveClienteNome($latestTransaction?->customer_name, $latestTransaction?->customer_document);

                return [
                    'cliente' => $cliente,
                    'documento' => (string) ($latestTransaction?->customer_document ?? ''),
                    'transacoes' => $clienteTransactions->count(),
                    'valor_total' => $this->formatMoney((int) $clienteTransactions->sum('amount')),
                    'primeira_transacao' => $oldestTransaction?->created_at ? Carbon::parse($oldestTransaction->created_at)->format('d/m/Y H:i:s') : '',
                    'ultima_transacao' => $latestTransaction?->created_at ? Carbon::parse($latestTransaction->created_at)->format('d/m/Y H:i:s') : '',
                ];
            })
            ->sortBy('cliente')
            ->values();
    }

    private function resolveClienteKey(PaytimeTransaction $transaction): string
    {
        $document = trim((string) ($transaction->customer_document ?? ''));

        if ($document !== '') {
            return 'document:'.Str::lower($document);
        }

        $name = trim((string) ($transaction->customer_name ?? ''));

        if ($name !== '') {
            return 'name:'.Str::lower($name);
        }

        return 'transaction:'.$transaction->id;
    }

    private function resolveClienteNome(?string $customerName, ?string $customerDocument): string
    {
        $name = trim((string) $customerName);

        if ($name !== '') {
            return $name;
        }

        $document = trim((string) $customerDocument);

        return $document !== '' ? 'Cliente '.$document : 'Cliente sem nome';
    }

    private function formatMoney(int $amountInCents): string
    {
        return 'R$ '.number_format($amountInCents / 100, 2, ',', '.');
    }
}
