<?php

return [
    'paths' => ['api/*', 'login', 'logout', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        'http://127.0.0.1:5173', // Vite dev server (localhost)
        'http://10.20.40.37:5173', // Vite dev server (network)
        'http://127.0.0.1:8000', // Laravel serve (localhost)
        'http://0.0.0.0:8000', // Laravel serve (network)
        'https://frontend-qgmvggb64-kadxlisms-projects.vercel.app', // Vercel production
    ],

    'allowed_origins_patterns' => [
        '/^https:\/\/frontend-.*\.vercel\.app$/', // All Vercel preview deployments
    ],

    'allowed_headers' => ['*'],

    'supports_credentials' => true,
];

