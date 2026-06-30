<?php

namespace App\Http\Controllers\Spa;

use App\Http\Controllers\Controller;
use App\Mail\RecorrenciaLinkMail;
use App\Models\Recorrencia;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class RecorrenciaEmailController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'send_via_email' => ['present', 'boolean'],
            'send_via_whatsapp' => ['present', 'boolean'],
            'recipient_name' => ['required', 'string', 'max:255'],
            'payment_type' => ['required', 'in:PIX,BOLETO,CARTAO'],
            'amount' => ['required', 'string', 'max:32'],
            'frequency' => ['required', 'string', 'max:32'],
            'charge_day' => ['nullable', 'integer', 'min:1', 'max:31'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'payment_link_url' => ['required', 'url', 'max:2048'],
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_email' => ['nullable', 'email', 'max:255'],
            'customer_phone' => ['nullable', 'string', 'max:32'],
            'customer_document' => ['nullable', 'string', 'max:32'],
            'recipient_email' => ['nullable', 'email', 'max:255', 'required_if:send_via_email,1'],
            'email_subject' => ['nullable', 'string', 'max:255', 'required_if:send_via_email,1'],
            'email_message' => ['nullable', 'string', 'max:5000', 'required_if:send_via_email,1'],
            'whatsapp_number' => ['nullable', 'string', 'max:32'],
            'pix_key' => ['nullable', 'string', 'max:255'],
            'pix_copy_paste' => ['nullable', 'string', 'max:5000'],
            'pix_expiration_minutes' => ['nullable', 'integer', 'min:1', 'max:1440'],
            'boleto_due_days' => ['nullable', 'integer', 'min:1', 'max:60'],
            'boleto_instructions' => ['nullable', 'string', 'max:5000'],
            'boleto_interest' => ['nullable', 'string', 'max:32'],
            'boleto_fine' => ['nullable', 'string', 'max:32'],
            'card_installments' => ['nullable', 'integer', 'min:1', 'max:18'],
            'card_descriptor' => ['nullable', 'string', 'max:255'],
            'card_capture_mode' => ['nullable', 'in:AUTO,MANUAL'],
        ]);

        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $estabelecimentoId = $user->getEstabelecimentoId();

        if (! $estabelecimentoId) {
            return response()->json([
                'message' => 'Estabelecimento não encontrado.',
            ], 422);
        }

        $amountCentavos = $this->converterValorParaCentavos($validated['amount']);

        if ($amountCentavos < 1) {
            return response()->json([
                'message' => 'O valor da recorrência deve ser de pelo menos R$ 0,01.',
            ], 422);
        }

        $recorrencia = Recorrencia::create([
            'user_id' => $user->id,
            'estabelecimento_id' => (string) $estabelecimentoId,
            'customer_name' => $validated['customer_name'],
            'customer_email' => $validated['customer_email'] ?? null,
            'customer_phone' => $validated['customer_phone'] ?? null,
            'customer_document' => $validated['customer_document'] ?? null,
            'payment_type' => $validated['payment_type'],
            'amount' => $amountCentavos / 100,
            'amount_centavos' => $amountCentavos,
            'frequency' => $validated['frequency'],
            'charge_day' => $validated['charge_day'] ?? null,
            'start_date' => $validated['start_date'] ?? null,
            'end_date' => $validated['end_date'] ?? null,
            'payment_link_url' => $validated['payment_link_url'],
            'send_via_email' => (bool) $validated['send_via_email'],
            'send_via_whatsapp' => (bool) $validated['send_via_whatsapp'],
            'recipient_email' => $validated['recipient_email'] ?? null,
            'email_subject' => $validated['email_subject'] ?? null,
            'email_message' => $validated['email_message'] ?? null,
            'whatsapp_number' => $validated['whatsapp_number'] ?? null,
            'status' => 'PENDENTE',
            'metadata' => [
                'pix' => [
                    'key' => $validated['pix_key'] ?? null,
                    'copy_paste' => $validated['pix_copy_paste'] ?? null,
                    'expiration_minutes' => $validated['pix_expiration_minutes'] ?? null,
                ],
                'boleto' => [
                    'due_days' => $validated['boleto_due_days'] ?? null,
                    'instructions' => $validated['boleto_instructions'] ?? null,
                    'interest' => $validated['boleto_interest'] ?? null,
                    'fine' => $validated['boleto_fine'] ?? null,
                ],
                'card' => [
                    'installments' => $validated['card_installments'] ?? null,
                    'descriptor' => $validated['card_descriptor'] ?? null,
                    'capture_mode' => $validated['card_capture_mode'] ?? null,
                ],
            ],
        ]);

        $emailStatusMessage = 'Recorrência salva com sucesso.';

        if ($validated['send_via_email'] && filled($validated['recipient_email'])) {
            try {
                Mail::to($validated['recipient_email'])->send(new RecorrenciaLinkMail(
                    recipientName: $validated['recipient_name'],
                    paymentType: $validated['payment_type'],
                    amount: $validated['amount'],
                    frequency: $validated['frequency'],
                    paymentLinkUrl: $validated['payment_link_url'],
                    emailMessage: $validated['email_message'] ?? '',
                    phoneNumber: $validated['whatsapp_number'] ?? null,
                ));

                $recorrencia->forceFill(['status' => 'ENVIADA'])->save();
                $emailStatusMessage = 'Recorrência salva e e-mail preparado com sucesso.';
            } catch (\Throwable $throwable) {
                Log::error('Falha ao enviar e-mail de cobrança recorrente.', [
                    'message' => $throwable->getMessage(),
                    'recorrencia_id' => $recorrencia->id,
                ]);

                $emailStatusMessage = 'Recorrência salva, mas não foi possível preparar o e-mail.';
            }
        }

        return response()->json([
            'message' => $emailStatusMessage,
            'recorrencia_id' => $recorrencia->id,
        ]);
    }

    private function converterValorParaCentavos(string $valor): int
    {
        $normalized = preg_replace('/[R$\s]/', '', $valor);

        if (str_contains($normalized, ',')) {
            $normalized = str_replace('.', '', $normalized);
            $normalized = str_replace(',', '.', $normalized);
        }

        $amount = (float) $normalized;

        if ($amount < 0.01) {
            return 0;
        }

        return (int) round($amount * 100);
    }
}
