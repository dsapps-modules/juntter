<x-mail::message>
# Cobrança recorrente

Olá, {{ $recipientName }}.

Segue o link preparado para a cobrança recorrente via {{ $paymentTypeLabel }}.

<x-mail::panel>
Valor: {{ $amount }}

Periodicidade: {{ $frequency }}

Contato: {{ $phoneNumber ?? 'Não informado' }}
</x-mail::panel>

{{ $emailMessage }}

<x-mail::button :url="$paymentLinkUrl">
Abrir link de pagamento
</x-mail::button>

Se preferir, você pode usar este endereço diretamente:

{{ $paymentLinkUrl }}

Atenciosamente,<br>
{{ config('app.name') }}
</x-mail::message>
