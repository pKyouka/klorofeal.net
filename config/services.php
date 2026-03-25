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

    'midtrans' => [
        'server_key' => env('MIDTRANS_SERVER_KEY'),
        'client_key' => env('MIDTRANS_CLIENT_KEY'),
        'is_production' => (bool) env('MIDTRANS_IS_PRODUCTION', false),
        'mock_success' => (bool) env('MIDTRANS_MOCK_SUCCESS', false),
        'qris_acquirer' => env('MIDTRANS_QRIS_ACQUIRER', 'gopay'),
        'qris_acquirers' => array_values(array_filter(array_map('trim', explode(',', (string) env('MIDTRANS_QRIS_ACQUIRERS', ''))))),
        'qris_expiry_minutes' => (int) env('MIDTRANS_QRIS_EXPIRY_MINUTES', 15),
    ],

    'openfoodfacts' => [
        'search_url' => env('OPENFOODFACTS_SEARCH_URL', 'https://world.openfoodfacts.org/cgi/search.pl'),
        'max_pages' => (int) env('OPENFOODFACTS_MAX_PAGES', 300),
        'page_size' => (int) env('OPENFOODFACTS_PAGE_SIZE', 100),
    ],

    'github_datasets' => [
        'indonesia_barcodes_xlsx_url' => env(
            'GITHUB_INDONESIA_BARCODES_XLSX_URL',
            'https://raw.githubusercontent.com/wpangestu/list-barcode-product-indonesia/master/List%20barcode%201000%20Product.xlsx'
        ),
    ],

];
