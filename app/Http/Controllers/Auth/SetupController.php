<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\LogAktivitas;
use App\Models\User;
use App\Support\AturanAkun;
use App\Support\SesiStaf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * Pemasangan pertama: membuat akun Owner pertama. Hanya bisa dibuka dari komputer server (localhost)
 * dan hanya selama belum ada akun Owner; setelah itu halaman ini hilang (404).
 */
class SetupController extends Controller
{
    public static function boleh(Request $request): bool
    {
        // database belum siap (belum migrate) = jangan tampilkan apa pun
        return in_array($request->ip(), ['127.0.0.1', '::1'], true)
            && rescue(fn () => ! User::where('role', User::ROLE_OWNER)->exists(), false, false);
    }

    public function show(Request $request): View
    {
        abort_unless(self::boleh($request), 404);

        return view('auth.setup');
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(self::boleh($request), 404);

        $request->merge(['email' => Str::lower(trim((string) $request->input('email'))), 'no_hp' => AturanAkun::rapikanHp($request->input('no_hp'))]);
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:100', 'regex:'.AturanAkun::REGEX_NAMA],
            'email' => ['required', 'email', 'max:100', 'unique:users,email'],
            'no_hp' => ['required', 'string', 'max:20', 'regex:'.AturanAkun::REGEX_HP],
            'password' => ['required', 'confirmed', Password::defaults()],
        ], [
            'nama.regex' => AturanAkun::PESAN_NAMA,
            'no_hp.regex' => AturanAkun::PESAN_HP,
            'email.unique' => 'Email sudah dipakai akun lain.',
            'password.confirmed' => 'Konfirmasi password tidak sama.',
        ]);

        $owner = User::create($data + ['role' => User::ROLE_OWNER]);
        $owner->forceFill(['email_verified_at' => now()])->save();
        LogAktivitas::catat('pengguna.tambah', "Akun Owner pertama \"{$owner->nama}\" dibuat lewat halaman pemasangan (setup) dari komputer server", $owner, pelaku: $owner);

        SesiStaf::masukkan($request, $owner, false);

        return to_route('owner.dashboard')->with('status', 'Akun Owner dibuat. Langkah berikutnya: buat akun Admin, Kasir, Dapur & Kurir di menu Pengguna & Role.');
    }
}
