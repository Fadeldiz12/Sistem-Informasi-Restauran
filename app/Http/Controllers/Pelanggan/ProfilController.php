<?php

namespace App\Http\Controllers\Pelanggan;

use App\Http\Controllers\Controller;
use App\Support\AturanAkun;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * Profil pelanggan: ubah nama, nomor HP, alamat pengantaran, dan password. Email tidak bisa diubah.
 */
class ProfilController extends Controller
{
    public function edit(Request $request): View
    {
        return view('pelanggan.profil', ['u' => $request->user()]);
    }

    public function update(Request $request): RedirectResponse
    {
        $request->merge(['no_hp' => AturanAkun::rapikanHp($request->input('no_hp'))]);
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:100', 'regex:'.AturanAkun::REGEX_NAMA],
            'no_hp' => ['required', 'string', 'max:20', 'regex:'.AturanAkun::REGEX_HP],
            'alamat' => ['nullable', 'string', 'max:1000'],
        ], [
            'nama.required' => 'Nama wajib diisi.',
            'nama.regex' => AturanAkun::PESAN_NAMA,
            'no_hp.required' => 'Nomor HP wajib diisi.',
            'no_hp.regex' => AturanAkun::PESAN_HP,
            'alamat.max' => 'Alamat maksimal 1000 karakter.',
        ]);

        $request->user()->update($data);

        return back()->with('status', 'Profil berhasil diperbarui.');
    }

    public function password(Request $request): RedirectResponse
    {
        $request->validate([
            'password_lama' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::defaults(), 'different:password_lama'],
        ], [
            'password_lama.required' => 'Masukkan password lama.',
            'password_lama.current_password' => 'Password lama tidak sesuai.',
            'password.required' => 'Password baru wajib diisi.',
            'password.confirmed' => 'Konfirmasi password baru tidak sama.',
            'password.different' => 'Password baru harus berbeda dengan password lama.',
        ], [
            'password' => 'Password baru',
        ]);

        $request->user()->update(['password' => $request->input('password')]);

        return back()->with('status', 'Password berhasil diganti.');
    }
}
