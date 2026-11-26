<?php

/*
| Informasi restoran yang tidak ada di tabel pengaturan_sistem.
| Ubah lewat file .env tanpa mengubah kode.
*/

return [
    'nama' => env('RESTO_NAMA', 'Resto Flow'),
    'alamat' => env('RESTO_ALAMAT', 'Jl. Gatot Subroto No. 100, Medan'),
    'telepon' => env('RESTO_TELEPON', '0811 6000 1234'),

    // jumlah meja untuk pilihan nomor meja di Input Pesanan kasir
    'jumlah_meja' => (int) env('RESTO_JUMLAH_MEJA', 16),

    // path gambar QRIS di folder public, contoh: images/qris.png
    'qris_gambar' => env('RESTO_QRIS_GAMBAR'),

    // backup database terenkripsi (php artisan resto:backup). Kunci bawaan = APP_KEY; isi BACKUP_KEY (teks bebas,
    // minimal 16 karakter) dan simpan di tempat lain supaya backup tetap bisa dibuka walau file .env hilang.
    'backup' => [
        'kunci' => env('BACKUP_KEY'),
        'simpan_hari' => (int) env('BACKUP_SIMPAN_HARI', 14),
        // folder kedua untuk salinan backup (flashdisk, NAS, folder OneDrive/Google Drive), contoh D:\\Backup-Resto
        'salinan_folder' => env('BACKUP_SALINAN_FOLDER'),
    ],

    // masa simpan data pribadi (UU No. 27/2022 Perlindungan Data Pribadi). Dijalankan "php artisan resto:bersihkan-data-pribadi"
    // (otomatis tiap malam). Angka penjualan & laporan keuangan tidak ikut dihapus.
    'retensi' => [
        // nama/HP/email pemesan, alamat & titik antar, foto bukti kirim dari pesanan yang sudah selesai/batal
        'pesanan_hari' => (int) env('RETENSI_PESANAN_HARI', 365),
        // data wajah staf yang tidak login selama ini (mis. sudah berhenti tapi akunnya belum dihapus)
        'wajah_hari' => (int) env('RETENSI_WAJAH_HARI', 180),
        // akun pelanggan yang tidak memesan selama ini dihapus
        'pelanggan_hari' => (int) env('RETENSI_PELANGGAN_HARI', 730),
    ],

    // Alamat proxy/load balancer hosting yang dipercaya meneruskan IP asli pengunjung (X-Forwarded-For).
    // Kosong = tidak ada proxy (IP diambil langsung). "*" = percaya proxy mana pun di depan server
    // (Cloudflare, Railway, Render, Nginx di server yang sama). JANGAN isi "*" bila server diakses langsung tanpa proxy:
    // pengunjung bisa memalsukan IP dan melewati cek lokasi outlet.
    'proxy_tepercaya' => env('TRUSTED_PROXIES'),

    'rekening' => [
        'bank' => env('RESTO_BANK', 'Bank Transfer'),
        'nomor' => env('RESTO_NO_REKENING', '1234 5678 9012'),
        'atas_nama' => env('RESTO_ATAS_NAMA', 'Resto Flow'),
    ],
];
