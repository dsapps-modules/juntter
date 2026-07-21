<x-mail::message>
# {{ $headline }}

Olá, {{ $customerName }}.

{{ $message }}

<x-mail::panel>
Carrinho: {{ $productName }}

Quantidade: {{ $quantity }}

Total: R$ {{ $total }}

Lojista: {{ $sellerName }}
</x-mail::panel>

<x-mail::button :url="$recoveryUrl">
{{ $ctaLabel }}
</x-mail::button>

Se preferir, abra o link abaixo diretamente:

{{ $recoveryUrl }}

Atenciosamente,<br>
{{ config('app.name') }}
</x-mail::message>
