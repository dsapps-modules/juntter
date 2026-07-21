<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $checkoutLink->name }} | Checkout Juntter</title>
    @foreach(($checkoutSpaAssets['css'] ?? []) as $checkoutSpaCss)
        <link rel="stylesheet" href="{{ $checkoutSpaCss }}">
    @endforeach
    @if($checkoutLink->allow_credit_card)
        <script src="https://assets.pagseguro.com.br/checkout-sdk-js/rc/dist/browser/pagseguro.min.js"></script>
    @endif
</head>
<body class="checkout-spa-body">
    @php
        $checkoutSpaData = [
            'checkoutLink' => $checkoutLink,
            'checkoutSession' => $checkoutSession,
            'order' => $order,
            'paymentTransaction' => $paymentTransaction,
            'sellerBrand' => $sellerBrand,
            'checkoutPageMode' => $checkoutPageMode ?? 'spa',
            'threeDsEnv' => app()->environment('local') ? 'SANDBOX' : 'PROD',
            'currentStep' => $checkoutSession->current_step,
            'shippingOptions' => $shippingOptions,
            'urls' => [
                'createSession' => route('checkout.public.session', $checkoutLink->public_token),
                'identify' => route('checkout.public.identification', $checkoutSession->session_token),
                'quantity' => route('checkout.public.quantity', $checkoutSession->session_token),
                'delivery' => route('checkout.public.delivery', $checkoutSession->session_token),
                'choosePaymentMethod' => route('checkout.public.payment.choose', $checkoutSession->session_token),
                'startPayment' => route('checkout.public.payment', $checkoutSession->session_token),
                'cnpjLookupTemplate' => route('checkout.public.cnpj.lookup', ['cnpj' => '__CNPJ__']),
                'status' => route('checkout.public.status', $checkoutSession->session_token),
                'thankYou' => route('checkout.public.thank-you', $checkoutSession->session_token),
                'paymentDetails' => route('checkout.public.payment.details', $checkoutSession->session_token),
                'antifraudAuthTemplate' => route('checkout.public.payment.antifraud-auth', [
                    $checkoutSession->session_token,
                    '__TRANSACTION_ID__',
                ]),
            ],
            'product' => [
                'name' => $checkoutLink->product->name,
                'description' => $checkoutLink->product->description,
                'short_description' => $checkoutLink->product->short_description,
            ],
        ];
    @endphp

    <div id="checkout-spa-data" data-checkout-spa-data hidden>@json($checkoutSpaData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)</div>
    <div id="checkout-spa-root"></div>
    @if(!empty($checkoutSpaAssets['js']))
        <script type="module" src="{{ $checkoutSpaAssets['js'] }}"></script>
    @endif
</body>
</html>
