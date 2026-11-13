<?php

// Akun database untuk setiap peran. Kunci = nilai kolom pengguna.role
return [
    'login' => [
        'username' => env('DB_LOGIN_USERNAME'),
        'password' => env('DB_LOGIN_PASSWORD'),
    ],
    'admin_pusat' => [
        'username' => env('DB_ADMIN_USERNAME'),
        'password' => env('DB_ADMIN_PASSWORD'),
    ],
    'apoteker' => [
        'username' => env('DB_APOTEKER_USERNAME'),
        'password' => env('DB_APOTEKER_PASSWORD'),
    ],
    'kasir' => [
        'username' => env('DB_KASIR_USERNAME'),
        'password' => env('DB_KASIR_PASSWORD'),
    ],
];