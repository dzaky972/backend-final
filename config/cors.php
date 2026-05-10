<?php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie', 'storage/*'],

    'allowed_methods' => ['*'],

    /*
    | Origin yang diizinkan untuk akses API.
    | Tambah origin tambahan di .env -> FRONTEND_URL,FRONTEND_URL_2,...
    */
    'allowed_origins' => array_filter(array_merge(
        [
            'http://localhost:5173',
            'http://127.0.0.1:5173',
            'http://localhost:3000',
            'http://127.0.0.1:3000',
            env('FRONTEND_URL', 'http://localhost:5173'),
        ],
        explode(',', env('CORS_EXTRA_ORIGINS', ''))
    )),

    /*
    | Pattern untuk LAN/IP dinamis (misal HP/laptop di network sama).
    | Pattern dievaluasi sebagai regex.
    */
    'allowed_origins_patterns' => [
        '#^http://192\.168\.\d+\.\d+(:\d+)?$#',
        '#^http://10\.\d+\.\d+\.\d+(:\d+)?$#',
        '#^http://172\.(1[6-9]|2\d|3[01])\.\d+\.\d+(:\d+)?$#',
    ],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 3600,

    'supports_credentials' => true,
];
