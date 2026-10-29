<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Kelas;
use App\Models\Pengguna;
use App\Models\Peran;
use App\Models\Siswa;
use App\Models\StatusAkun;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
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
            'email' => ['required', 'string', 'email', 'max:150'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'kelas_id' => [
                'required',
                'integer',
                Rule::exists('kelas_m', 'kelas_id')->where('aktif', true),
            ],
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

            Siswa::query()->create([
                'pengguna_id' => $penggunaBaru->pengguna_id,
                'kelas_id' => $validated['kelas_id'],
                'status_akun_id' => $statusPretest->status_akun_id,
                'jenis_kelamin' => $validated['jenis_kelamin'] ?? null,
                'tanggal_lahir' => $validated['tanggal_lahir'] ?? null,
            ]);

            return $penggunaBaru;
        });

        $pengguna->load(['peran', 'siswa.statusAkun', 'siswa.kelas']);
        $token = $pengguna->createToken('mobile')->plainTextToken;

        return response()->json([
            'data' => [
                'token' => $token,
                'user' => $this->formatMe($pengguna),
            ],
            'message' => 'Registrasi berhasil.',
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $emailNormalized = strtolower(trim($validated['email']));

        $pengguna = Pengguna::query()
            ->with(['peran', 'siswa.statusAkun', 'siswa.kelas'])
            ->whereRaw('lower(email) = ?', [$emailNormalized])
            ->first();

        if (! $pengguna || ! Hash::check($validated['password'], $pengguna->kata_sandi_hash)) {
            return response()->json([
                'data' => null,
                'message' => 'Email atau kata sandi salah.',
            ], 401);
        }

        if (! $pengguna->aktif) {
            return response()->json([
                'data' => null,
                'message' => 'Akun pengguna tidak aktif.',
            ], 403);
        }

        if ($pengguna->peran?->kode !== 'SISWA' || ! $pengguna->siswa) {
            return response()->json([
                'data' => null,
                'message' => 'Login API hanya untuk akun siswa.',
            ], 403);
        }

        $pengguna->update([
            'terakhir_login_pada' => now(),
        ]);

        $token = $pengguna->createToken('mobile')->plainTextToken;

        return response()->json([
            'data' => [
                'token' => $token,
                'user' => $this->formatMe($pengguna),
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
        $pengguna = $request->user()->fresh(['peran', 'siswa.statusAkun', 'siswa.kelas']);

        if (! $pengguna || ! $pengguna->aktif) {
            return response()->json([
                'data' => null,
                'message' => 'Akun pengguna tidak aktif.',
            ], 403);
        }

        return response()->json([
            'data' => $this->formatMe($pengguna),
            'message' => 'Data profil pengguna.',
        ]);
    }

    /**
     * Format representasi profil sesuai spesifikasi GET /api/me.
     *
     * @return array{
     *     pengguna_id: int,
     *     nama_lengkap: string,
     *     email: string,
     *     siswa_id: int|null,
     *     kelas: array{kelas_id: int, nama_kelas: string}|null,
     *     status_akun: array{kode: string, nama: string}|null,
     *     terverifikasi_pada: string|null
     * }
     */
    protected function formatMe(Pengguna $pengguna): array
    {
        $siswa = $pengguna->siswa;

        return [
            'pengguna_id' => $pengguna->pengguna_id,
            'nama_lengkap' => $pengguna->nama_lengkap,
            'email' => $pengguna->email,
            'siswa_id' => $siswa?->siswa_id,
            'kelas' => $siswa?->kelas ? [
                'kelas_id' => $siswa->kelas->kelas_id,
                'nama_kelas' => $siswa->kelas->nama_kelas,
            ] : null,
            'status_akun' => $siswa?->statusAkun ? [
                'kode' => $siswa->statusAkun->kode,
                'nama' => $siswa->statusAkun->nama,
            ] : null,
            'terverifikasi_pada' => $siswa?->terverifikasi_pada?->toIso8601String(),
        ];
    }
}
