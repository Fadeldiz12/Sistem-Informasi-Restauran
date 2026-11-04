<?php

/*
| Informasi restoran yang tidak ada di tabel pengaturan_sistem.
| Ubah lewat file .env tanpa mengubah kode.
*/

return [
    'nama' => env('RESTO_NAMA', 'SIM Resto'),
    'alamat' => env('RESTO_ALAMAT', 'Jl. Gatot Subroto No. 100, Medan'),
    'telepon' => env('RESTO_TELEPON', '0811 6000 1234'),

    // jumlah meja untuk pilihan nomor meja di Input Pesanan kasir
    'jumlah_meja' => (int) env('RESTO_JUMLAH_MEJA', 16),

    // path gambar QRIS di folder public, contoh: images/qris.png
    'qris_gambar' => env('RESTO_QRIS_GAMBAR'),

    'rekening' => [
        'bank' => env('RESTO_BANK', 'Bank Transfer'),
        'nomor' => env('RESTO_NO_REKENING', '1234 5678 9012'),
        'atas_nama' => env('RESTO_ATAS_NAMA', 'SIM Resto'),
    ],
];
