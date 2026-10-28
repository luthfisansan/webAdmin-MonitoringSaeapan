<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Kelas;
use App\Models\Pengguna;
use App\Models\Peran;
use App\Models\RiwayatStatusAkun;
use App\Models\Siswa;
use App\Models\StatusAkun;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function kelas(): JsonResponse
    {
        $daftarKelas = Kelas::query()
            ->where('aktif', true)
            ->orderBy('nama_kelas')
            ->get(['kelas_id', 'nama_kelas', 'tahun_ajaran']);

        return response()->json([
            'data' => $daftarKelas,
            'message' => 'Daftar kelas aktif.',
        ]);
    }

    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nama_lengkap' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:150'],
            'password' => ['required', 'string', 'min:6'],
            'kelas_id' => ['required', 'integer', 'exists:kelas_m,kelas_id'],
            'jenis_kelamin' => ['nullable', 'string', 'in:L,P'],
            'tanggal_lahir' => ['nullable', 'date'],
        ]);

        $emailNormalized = strtolower(trim($validated['email']));

        $sudahAda = Pengguna::query()
            ->whereRaw('lower(email) = ?', [$emailNormalized])
            ->exists();

        if ($sudahAda) {
            throw ValidationException::withMessages([
                'email' => ['Email sudah terdaftar.'],
            ]);
        }

        $pengguna = DB::transaction(function () use ($validated, $emailNormalized): Pengguna {
            $peranSiswa = Peran::query()->where('kode', 'SISWA')->firstOrFail();
            $statusPretest = StatusAkun::query()->where('kode', 'PRETEST')->firstOrFail();

            $penggunaBaru = Pengguna::query()->create([
                'peran_id' => $peranSiswa->peran_id,
                'email' => $emailNormalized,
                'kata_sandi_hash' => Hash::make($validated['password']),
                'nama_lengkap' => trim($validated['nama_lengkap']),
                'aktif' => true,
                'terakhir_login_pada' => now(),
            ]);

            $siswaBaru = Siswa::query()->create([
                'pengguna_id' => $penggunaBaru->pengguna_id,
                'kelas_id' => $validated['kelas_id'],
                'status_akun_id' => $statusPretest->status_akun_id,
                'jenis_kelamin' => $validated['jenis_kelamin'] ?? null,
                'tanggal_lahir' => $validated['tanggal_lahir'] ?? null,
            ]);

            RiwayatStatusAkun::query()->create([
                'siswa_id' => $siswaBaru->siswa_id,
                'status_akun_id' => $statusPretest->status_akun_id,
                'diubah_oleh_pengguna_id' => $penggunaBaru->pengguna_id,
                'catatan' => 'Registrasi akun siswa baru',
            ]);

            return $penggunaBaru;
        });

        $token = $pengguna->createToken('mobile')->plainTextToken;

        return response()->json([
            'data' => [
                'token' => $token,
                'user' => $this->formatProfil($pengguna->fresh(['peran', 'siswa.statusAkun', 'siswa.kelas'])),
            ],
            'message' => 'Registrasi berhasil.',
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $emailNormalized = strtolower(trim($validated['email']));

        $pengguna = Pengguna::query()
            ->with(['peran', 'siswa.statusAkun', 'siswa.kelas'])
            ->whereRaw('lower(email) = ?', [$emailNormalized])
            ->first();

        if (! $pengguna || ! Hash::check($validated['password'], $pengguna->kata_sandi_hash)) {
            throw ValidationException::withMessages([
                'email' => ['Email atau kata sandi tidak sesuai.'],
            ]);
        }

        if (! $pengguna->aktif) {
            return response()->json([
                'data' => null,
                'message' => 'Akun pengguna tidak aktif.',
            ], 403);
        }

        $pengguna->update([
            'terakhir_login_pada' => now(),
        ]);

        $token = $pengguna->createToken('mobile')->plainTextToken;

        return response()->json([
            'data' => [
                'token' => $token,
                'user' => $this->formatProfil($pengguna),
            ],
            'message' => 'Login berhasil.',
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();

        return response()->json([
            'data' => null,
            'message' => 'Logout berhasil.',
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        /** @var Pengguna $pengguna */
        $pengguna = $request->user()->load(['peran', 'siswa.statusAkun', 'siswa.kelas']);

        return response()->json([
            'data' => $this->formatProfil($pengguna),
            'message' => 'Data profil pengguna.',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function formatProfil(Pengguna $pengguna): array
    {
        $siswa = $pengguna->siswa;

        return [
            'pengguna_id' => $pengguna->pengguna_id,
            'siswa_id' => $siswa?->siswa_id,
            'nama_lengkap' => $pengguna->nama_lengkap,
            'email' => $pengguna->email,
            'peran' => $pengguna->peran?->kode,
            'jenis_kelamin' => $siswa?->jenis_kelamin,
            'tanggal_lahir' => $siswa?->tanggal_lahir?->toDateString(),
            'status_akun' => $siswa?->statusAkun ? [
                'status_akun_id' => $siswa->statusAkun->status_akun_id,
                'kode' => $siswa->statusAkun->kode,
                'nama' => $siswa->statusAkun->nama,
            ] : null,
            'kelas' => $siswa?->kelas ? [
                'kelas_id' => $siswa->kelas->kelas_id,
                'nama_kelas' => $siswa->kelas->nama_kelas,
                'tahun_ajaran' => $siswa->kelas->tahun_ajaran,
            ] : null,
        ];
    }
}

