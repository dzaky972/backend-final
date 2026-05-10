<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Midtrans Configuration
    |--------------------------------------------------------------------------
    |
    | Dapatkan credentials dari https://dashboard.sandbox.midtrans.com/
    | Settings → Access Keys
    |
    */

    'server_key'   => env('MIDTRANS_SERVER_KEY', 'SB-Mid-server-MGw9C-uzEVQEp0SmEXKsAH6V'),
    'client_key'   => env('MIDTRANS_CLIENT_KEY', 'SB-Mid-client-dPcgea7DR4f8H1VX'),
    'merchant_id'  => env('MIDTRANS_MERCHANT_ID', 'M382521723'),
    'is_production'=> env('MIDTRANS_IS_PRODUCTION', false),
    'is_sanitized' => env('MIDTRANS_IS_SANITIZED', true),
    'is_3ds'       => env('MIDTRANS_IS_3DS', true),
];
