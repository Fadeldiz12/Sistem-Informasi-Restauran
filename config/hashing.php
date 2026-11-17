<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Algoritma Hash Password
    |--------------------------------------------------------------------------
    |
    | Argon2id (pemenang Password Hashing Competition) lebih tahan terhadap
    | penebakan password memakai GPU dibanding bcrypt karena butuh banyak memori.
    | Bila PHP di server tidak mendukung Argon2id, otomatis memakai bcrypt.
    | Password lama (bcrypt) tetap bisa dipakai masuk dan otomatis diubah ke
    | Argon2id saat pemiliknya berhasil login.
    |
    */

    'driver' => env('HASH_DRIVER', defined('PASSWORD_ARGON2ID') ? 'argon2id' : 'bcrypt'),

    'bcrypt' => [
        'rounds' => env('BCRYPT_ROUNDS', 12),
        'verify' => env('HASH_VERIFY', true),
        'limit' => env('BCRYPT_LIMIT', null),
    ],

    /*
    | Biaya Argon2id: 64 MB memori, 4 iterasi (bawaan Laravel, sesuai rekomendasi OWASP).
    | "verify" dimatikan supaya hash bcrypt lama masih bisa dicek sebelum diperbarui.
    */

    'argon' => [
        'memory' => env('ARGON_MEMORY', 65536),
        'threads' => env('ARGON_THREADS', 1),
        'time' => env('ARGON_TIME', 4),
        'verify' => false,
    ],

    'rehash_on_login' => true,

];
