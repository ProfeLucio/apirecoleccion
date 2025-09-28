<?php

return [
    // A qué rutas de tu API se les aplica CORS
    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    // Métodos permitidos
    'allowed_methods' => ['GET','POST','PUT','PATCH','DELETE','OPTIONS'],

    // Orígenes permitidos (dev + device)
    'allowed_origins' => [
        'http://localhost:8100',       // Ionic serve
        'http://127.0.0.1:8100',
        'http://localhost:4200',       // Angular (si lo usas)
        'http://localhost:5173',       // Vite (si lo usas)
        'http://127.0.0.1:5173',
        'capacitor://localhost',       // App nativa (Capacitor)
        'ionic://localhost',           // Algunos WebView antiguos
        'http://localhost',            // Android WebView suele usar esto
    ],
    'allowed_origins_patterns' => [],

    // Headers que tu cliente realmente envía
    'allowed_headers' => [
        'Content-Type', 'Authorization', 'Accept', 'X-Requested-With', 'Origin'
    ],

    // Si necesitas exponer cabeceras al cliente (descargas, etc.)
    'exposed_headers' => ['Content-Disposition', 'Location'],

    // Cache del preflight (en segundos)
    'max_age' => 3600,

    // Si usas cookies de sesión/Sanctum pon true; si usas Bearer/JWT deja false
    'supports_credentials' => false,
];
