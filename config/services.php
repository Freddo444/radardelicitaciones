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

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'gemini' => [
        'key' => env('GEMINI_API_KEY', ''),
    ],

    'paypal' => [
        'client_id' => env('PAYPAL_CLIENT_ID'),
        'secret' => env('PAYPAL_SECRET'),
        'webhook_id' => env('PAYPAL_WEBHOOK_ID'),
        'plan_id' => env('PAYPAL_PLAN_ID'),
        'annual_plan_id' => env('PAYPAL_ANNUAL_PLAN_ID'),
        'sandbox' => env('PAYPAL_SANDBOX', true),
    ],

    'whatsapp' => [
        'number' => env('WHATSAPP_NUMBER', ''),
    ],

    'support' => [
        'email' => env('SUPPORT_EMAIL', 'info@radardelicitaciones.com'),
        'phone' => env('SUPPORT_PHONE', '+1-809-555-1234'),
        'address_line' => env('BUSINESS_ADDRESS_LINE', 'Dirección comercial registrada'),
        'city' => env('BUSINESS_CITY', 'Santo Domingo'),
        'country' => env('BUSINESS_COUNTRY', 'República Dominicana'),
    ],

    'azul' => [
        'merchant_id' => env('AZUL_MERCHANT_ID'),
        'auth_key' => env('AZUL_AUTH_KEY'),
        'merchant_name' => env('AZUL_MERCHANT_NAME', 'Radar de Licitaciones'),
        'merchant_type' => env('AZUL_MERCHANT_TYPE', 'Comercio electronico'),
        'currency_code' => env('AZUL_CURRENCY_CODE', '$'),
        'payment_page_url' => env(
            'AZUL_PAYMENT_PAGE_URL',
            'https://pruebas.azul.com.do/paymentpage/Default.aspx'
        ),
        'sandbox' => env('AZUL_SANDBOX', true),
        'usd_dop_rate' => (float) env('AZUL_USD_DOP_RATE', 62),
    ],

    'calendly' => [
        'url' => env('CALENDLY_URL', ''),
    ],

    'tawkto' => [
        'widget_url' => env('TAWKTO_WIDGET_URL', ''),
    ],

    'dgcp' => [
        'allowed_document_hosts' => array_values(array_filter(array_map('trim', explode(',', (string) env('DGCP_ALLOWED_DOCUMENT_HOSTS', 'datosabiertos.dgcp.gob.do,dgcp.gob.do,comunidad.comprasdominicana.gob.do'))))),
    ],

];
