<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    'paths' => ['api/*', '/'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        'https://www.klarifikasi.rj22d.my.id',
        'https://klarifikasi.netlify.app',
        'http://localhost:3000',  // Flutter web port
        'http://localhost:3001',
        'http://localhost:8080',
        'http://localhost:8081',
        'http://127.0.0.1:3000',  // Flutter web port
        'http://127.0.0.1:8080',
        'http://127.0.0.1:8081',
        'http://localhost',
        'http://127.0.0.1',
        
        '*', // Temporary untuk development - HAPUS di production
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => ['*'],

    'max_age' => 0,

    'supports_credentials' => false,

];
