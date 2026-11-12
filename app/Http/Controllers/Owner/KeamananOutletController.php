<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\JadwalShift;
use App\Models\KodeDarurat;
use App\Models\LogAktivitas;
use App\Models\PengaturanSistem;
use App\Models\PerangkatOutlet;
use App\Models\User;
use App\Services\AksesOutletService;
use App\Support\PemeriksaanServer;
use Closure;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Owner mengatur akses outlet: IP WiFi & GPS outlet, perangkat terdaftar, jadwal shift, dan kode darurat.
 */
class KeamananOutletController extends Controller
{
    public const DURASI_KODE_JAM = [2, 4, 8, 12, 24];

    public function __construct(private AksesOutletService $akses) {}

    public function index(Request $request): View
    {
        $staf = User::whereIn('role', AksesOutletService::ROLE_SHIFT)->with('jadwalShift')
            ->orderByRaw("case role when 'kasir' then 1 when 'dapur' then 2 when 'kurir' then 3 else 4 end")->orderBy('nama')->get();

        return view('owner.keamanan', [
            'pengaturan' => $this->akses->pengaturan(),
            'ipSaya' => $request->ip(),
            'ipDiOutlet' => $this->akses->ipDiOutlet($request),
            'perangkat' => PerangkatOutlet::with('pendaftar')->latest()->get(),
            'perangkatIni' => $this->akses->perangkat($request),
            'staf' => $staf,
            'tanpaJadwal' => $staf->filter(fn (User $u) => $u->jadwalShift->isEmpty())->count(),
            'shiftPerHari' => $this->shiftPerHari($staf),
            'kode' => KodeDarurat::aktif()->with('owner')->latest()->get(),
            'semuaStaf' => User::where('role', '!=', User::ROLE_PELANGGAN)->with('wajah')->orderBy('role')->orderBy('nama')->get(),
            'cekServer' => PemeriksaanServer::daftar($request),
            'titikDefault' => (float) $this->akses->pengaturan()->lat_resto === PengaturanSistem::LAT_AWAL,
        ]);
    }

    /**
     * Siapa saja yang masuk tiap hari, urut jam mulai.
     *
     * @param  Collection<int, User>  $staf
     * @return array<int, list<array{id: int, nama: string, peran: string, jam: string}>>
     */
    private function shiftPerHari(Collection $staf): array
    {
        $perHari = array_fill_keys(array_keys(JadwalShift::HARI), []);
        foreach ($staf as $u) {
            foreach ($u->jadwalShift as $s) {
                $perHari[$s->hari][] = ['id' => $u->id, 'nama' => $u->nama, 'peran' => $u->roleLabel(), 'jam' => $s->label()];
            }
        }

        return array_map(fn (array $daftar) => collect($daftar)->sortBy(['jam', 'nama'])->values()->all(), $perHari);
    }

    public function simpanAturan(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'ip_outlet' => ['nullable', 'string', 'max:2000', function (string $attr, mixed $nilai, Closure $fail) {
                foreach (array_filter(array_map('trim', preg_split('/[\r\n,]+/', (string) $nilai) ?: [])) as $baris) {
                    [$ip, $mask] = array_pad(explode('/', $baris, 2), 2, null);
                    $versi6 = filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false;
                    if (filter_var($ip, FILTER_VALIDATE_IP) === false || ($mask !== null && (! ctype_digit($mask) || (int) $mask > ($versi6 ? 128 : 32)))) {
                        $fail("\"{$baris}\" bukan alamat IP yang valid. Tulis satu IP per baris, contoh 103.25.10.7 atau 103.25.10.0/24.");

                        return;
                    }
                }
            }],
            'cek_gps' => ['boolean'],
            'radius_outlet_m' => ['required', 'integer', 'min:20', 'max:1000'],
            'wajib_perangkat' => ['boolean'],
            'wajib_shift' => ['boolean'],
            'wajib_wajah' => ['boolean'],
        ], [
            'radius_outlet_m.min' => 'Radius outlet minimal 20 meter.',
            'radius_outlet_m.max' => 'Radius outlet maksimal 1.000 meter.',
        ]);
        $data += ['cek_gps' => false, 'wajib_perangkat' => false, 'wajib_shift' => false, 'wajib_wajah' => false];
        $data['ip_outlet'] = implode("\n", array_filter(array_map('trim', preg_split('/[\r\n,]+/', (string) ($data['ip_outlet'] ?? '')) ?: []))) ?: null;

        if ($data['wajib_perangkat'] && ! PerangkatOutlet::exists()) {
            return back()->withInput()->withErrors(['wajib_perangkat' => 'Daftarkan minimal satu perangkat outlet dulu sebelum mewajibkan perangkat terdaftar.']);
        }

        PengaturanSistem::aktif()->update($data);

        return back()->with('status', 'Aturan akses outlet disimpan. Berlaku untuk login berikutnya dan sesi staf yang sedang aktif.');
    }

    public function daftarPerangkat(Request $request): RedirectResponse
    {
        $data = $request->validate(['nama' => ['required', 'string', 'max:60']], ['nama.required' => 'Beri nama perangkat ini, contoh "PC Kasir 1".']);

        if ($ada = $this->akses->perangkat($request)) {
            return back()->withErrors(['nama' => "Perangkat ini sudah terdaftar sebagai \"{$ada->nama}\"."]);
        }

        $token = Str::random(64);
        PerangkatOutlet::create([
            'nama' => $data['nama'],
            'token_hash' => hash('sha256', $token),
            'id_pendaftar' => $request->user()->id,
            'keterangan_perangkat' => (new LogAktivitas(['perangkat' => $request->userAgent()]))->perangkatSingkat(),
        ]);

        // cookie 5 tahun; menghapus data browser = perangkat perlu didaftarkan ulang
        return back()->with('status', "Perangkat ini terdaftar sebagai \"{$data['nama']}\".")
            ->withCookie(cookie(PerangkatOutlet::COOKIE, $token, 60 * 24 * 365 * 5, httpOnly: true, sameSite: 'lax'));
    }

    public function hapusPerangkat(PerangkatOutlet $perangkat): RedirectResponse
    {
        $perangkat->delete();
        $sisa = PerangkatOutlet::count();
        if (! $sisa && PengaturanSistem::aktif()->wajib_perangkat) {
            PengaturanSistem::aktif()->update(['wajib_perangkat' => false]);

            return back()->with('status', "Perangkat \"{$perangkat->nama}\" dihapus. Tidak ada perangkat tersisa, jadi aturan wajib perangkat dimatikan.");
        }

        return back()->with('status', "Perangkat \"{$perangkat->nama}\" dihapus. Staf tidak bisa lagi masuk dari perangkat itu.");
    }

    public function simpanShift(Request $request, User $user): RedirectResponse
    {
        abort_unless(in_array($user->role, AksesOutletService::ROLE_SHIFT, true), 404);

        $data = $request->validate([
            'hari' => ['nullable', 'array'],
            'hari.*.aktif' => ['nullable', 'boolean'],
            'hari.*.mulai' => ['nullable', 'required_if_accepted:hari.*.aktif', 'date_format:H:i'],
            'hari.*.selesai' => ['nullable', 'required_if_accepted:hari.*.aktif', 'date_format:H:i', 'different:hari.*.mulai'],
        ], [
            'hari.*.mulai.required_if_accepted' => 'Isi jam mulai untuk hari yang dicentang.',
            'hari.*.selesai.required_if_accepted' => 'Isi jam selesai untuk hari yang dicentang.',
            'hari.*.selesai.different' => 'Jam selesai tidak boleh sama dengan jam mulai.',
            'hari.*.mulai.date_format' => 'Format jam tidak valid.',
            'hari.*.selesai.date_format' => 'Format jam tidak valid.',
        ]);

        $baris = collect($data['hari'] ?? [])->filter(fn ($h, $hari) => ! empty($h['aktif']) && isset(JadwalShift::HARI[$hari]))
            ->map(fn ($h, $hari) => ['hari' => (int) $hari, 'jam_mulai' => $h['mulai'], 'jam_selesai' => $h['selesai']]);

        DB::transaction(function () use ($user, $baris) {
            $user->jadwalShift()->delete();
            $user->jadwalShift()->createMany($baris->values()->all());
        });
        $user->load('jadwalShift');
        LogAktivitas::catat('shift.ubah', "Jadwal shift {$user->nama} diatur: ".(AksesOutletService::ringkasJadwal($user) ?: 'tanpa jadwal (bebas)'), $user);

        return back()->with('status', "Jadwal shift {$user->nama} disimpan.");
    }

    public function buatKode(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'alasan' => ['required', 'string', 'max:200'],
            'durasi' => ['required', 'integer', Rule::in(self::DURASI_KODE_JAM)],
        ], ['alasan.required' => 'Tulis alasannya, contoh "WiFi outlet mati, kasir pakai hotspot HP".']);

        $kode = str_pad((string) random_int(0, 99_999_999), 8, '0', STR_PAD_LEFT);
        $k = KodeDarurat::create([
            'kode_hash' => Hash::make($kode),
            'id_owner' => $request->user()->id,
            'alasan' => $data['alasan'],
            'berlaku_sampai' => now()->addHours((int) $data['durasi']),
        ]);
        LogAktivitas::catat('darurat.buat', "Kode darurat dibuat, berlaku {$data['durasi']} jam sampai {$k->berlaku_sampai->format('d/m H.i')}. Alasan: {$data['alasan']}", $k);

        return back()->with('status', 'Kode darurat dibuat. Berikan hanya kepada staf yang membutuhkan.')
            ->with('kode_baru', substr($kode, 0, 4).'-'.substr($kode, 4));
    }

    public function cabutKode(KodeDarurat $kode): RedirectResponse
    {
        $kode->update(['dicabut_at' => now()]);
        LogAktivitas::catat('darurat.cabut', "Kode darurat (alasan: {$kode->alasan}) dicabut setelah dipakai {$kode->dipakai} kali", $kode);

        return back()->with('status', 'Kode darurat dicabut. Staf yang masuk dengan kode ini akan keluar otomatis.');
    }
}
