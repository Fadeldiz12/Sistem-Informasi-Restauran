<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Lengkapi nomor HP & alamat (akun pelanggan yang dibuat lewat Google).
 */
class ProfilController extends Controller
{
    public function edit(Request $request): View|RedirectResponse
    {
        if (! $request->user()->perluLengkapiProfil()) {
            return redirect()->route($request->user()->homeRoute());
        }

        return view('auth.lengkapi-profil');
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'hp' => ['required', 'string', 'max:20', 'regex:/^[0-9+\-\s]+$/'],
            'alamat' => ['nullable', 'string', 'max:1000'],
        ], [
            'hp.required' => 'Nomor HP wajib diisi.',
            'hp.regex' => 'Nomor HP hanya boleh berisi angka.',
        ]);

        $request->user()->update(['no_hp' => $data['hp'], 'alamat' => $data['alamat'] ?? $request->user()->alamat]);

        return redirect()->intended(route($request->user()->homeRoute()))->with('status', 'Profil lengkap. Selamat memesan!');
    }
}
