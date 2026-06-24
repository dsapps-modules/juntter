<x-mail::message>
# Código de verificação

Recebemos uma solicitação para {{ $purpose }}.

Use o código abaixo para concluir a ação:

<x-mail::panel>
{{ $code }}
</x-mail::panel>

Se você não reconhece essa solicitação, ignore este e-mail.

Atenciosamente,<br>
{{ config('app.name') }}
</x-mail::message>
