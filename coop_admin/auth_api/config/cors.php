<?php
return [
    'allowed_origins' => [
        'http://localhost:61915',  // Your local development URL
        'http://localhost:3000',
        'https://yourdomain.com'   // Your production domain
    ],
    'allowed_methods' => ['GET', 'POST', 'OPTIONS'],
    'allowed_headers' => ['Content-Type', 'Authorization', 'X-Requested-With'],
    'max_age' => 86400, // 24 hours
];