<?php

/*
| Batas validasi wajah & liveness (sama dengan FaceSecure Authentication).
| Bagian "quality", "liveness", "flash", dan "blocked_cameras" dikirim ke browser (public/js/face-guard.js)
| dan DICEK ULANG di server (App\Services\WajahService). Bagian "server" hanya dipakai di server.
*/

return [
    'quality' => [
        'min_score' => 0.8,          // skor deteksi SSD minimal (wajah tertutup/masker = skor rendah)
        'margin' => 0.02,            // seluruh wajah harus di dalam frame (jarak dari tepi, 0..1)
        'min_width' => 0.22,         // lebar wajah minimal (terlalu jauh)
        'max_width' => 0.75,         // lebar wajah maksimal (terlalu dekat)
        'max_center' => 0.22,        // seberapa jauh dari tengah frame
        'max_yaw' => 12,             // derajat; saat sampel diambil wajah harus lurus
        'max_roll' => 12,
        'pitch_min' => -15,
        'pitch_max' => 25,
        'cover_max' => 15,           // jarak kroma kulit: >= ini = wajah tertutup (masker/kain)
        'eye_open_max' => 0.4,       // blendshape kedip: > ini = mata tertutup
        'stable_frames' => 8,
        'sample_interval_ms' => 200,
    ],

    'liveness' => [
        'extra_pool' => ['turn_left', 'turn_right', 'open_mouth'],
        'extra_steps' => 2,          // kedip selalu ada + 2 gerakan acak dari pool
        'blink_closed' => 0.45,
        'blink_delta' => 0.3,
        'blink_reopen' => 0.15,
        'turn_deg' => 20,
        'mouth_open' => 0.4,
        'mouth_closed' => 0.15,
        'step_timeout' => 7,         // detik per gerakan
        'react_min_ms' => 300,       // lebih cepat dari ini = bukan reaksi manusia (video rekaman)
        'react_max_ms' => 6000,
        'gap_min_ms' => 500,
        'gap_max_ms' => 1400,
        'total_timeout' => 90,
        'max_lost_ms' => 1500,
        'max_jump' => 0.25,          // wajah "melompat" = ganti orang/foto di tengah jalan
        'challenge_ttl' => 120,      // detik tantangan berlaku
        'min_duration_ms' => 1500,
    ],

    'flash' => [
        'enabled' => (bool) env('FACE_FLASH', true), // layar berkedip warna acak; pantulan di kulit wajah diukur (anti layar HP/video)
        'count' => 6,
        'epoch_ms' => 450,
        'skip_ms' => 150,
        'min_corr' => 0.5,
        'min_amp' => 0.0015,
    ],

    'blocked_cameras' => 'obs|virtual|manycam|xsplit|snap camera|camtwist|e2esoft|vcam|splitcam|youcam|droidcam|iriun|epoccam',

    'server' => [
        'samples_login' => ['before' => 2, 'after' => 2],
        'samples_daftar' => ['before' => 3, 'after' => 3],
        'match_threshold' => 0.5,    // jarak Euclidean descriptor maksimal = orang yang sama
        'duplicate_threshold' => 0.55, // pendaftaran: lebih dekat dari ini ke wajah akun lain = wajah yang sama, ditolak
        'max_failures' => 3,
    ],
];
