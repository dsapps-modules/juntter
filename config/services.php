<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    /**
     * Paytime mTLS can be configured explicitly via env vars or discovered
     * automatically from `base_path('.keys')` when the expected client cert
     * and private key are present.
     */
    'paytime' => [
        'base_url' => env('PAYTIME_BASE_URL'),
        'payout_base_url' => env('PAYTIME_PAYOUT_BASE_URL', 'https://banking.paytime.com.br/v1'),
        'integration_key' => env('PAYTIME_INTEGRATION_KEY'),
        'authentication_key' => env('PAYTIME_AUTHENTICATION_KEY'),
        'x_token' => env('PAYTIME_X_TOKEN'),
        'payout_fee_cents' => env('PAYTIME_PAYOUT_FEE_CENTS', 100),
        'mtls_cert_path' => env('PAYTIME_MTLS_CERT_PATH'),
        'mtls_key_path' => env('PAYTIME_MTLS_KEY_PATH'),
        'mtls_ca_path' => env('PAYTIME_MTLS_CA_PATH'),
        'mtls_verify_peer' => env('PAYTIME_MTLS_VERIFY_PEER', true),
        'webhook_user' => env('PAYTIME_WEBHOOK_USER'),
        'webhook_pass' => env('PAYTIME_WEBHOOK_PASS'),
    ],

];
