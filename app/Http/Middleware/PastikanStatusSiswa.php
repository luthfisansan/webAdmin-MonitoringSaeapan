<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PastikanStatusSiswa
{
    /**
     * Pastikan status_akun siswa sesuai dengan salah satu kode yang diizinkan (dibaca langsung dari DB).
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$daftarKodeDiizinkan): Response
    {
        $pengguna = $request->user()?->fresh(['peran', 'siswa.statusAkun']);

        if (! $pengguna || ! $pengguna->aktif || $pengguna->peran?->kode !== 'SISWA' || ! $pengguna->siswa) {
            return response()->json([
                'code' => 'AKSES_KHUSUS_SISWA',
                'data' => null,
                'message' => 'Endpoint ini hanya dapat diakses oleh akun siswa aktif.',
            ], 403);
        }

        $kodeStatus = $pengguna->siswa->statusAkun?->kode;
        $daftarKode = array_values(array_filter(array_map('trim', $daftarKodeDiizinkan)));

        if (! in_array($kodeStatus, $daftarKode, true)) {
            return response()->json([
                'code' => 'STATUS_TIDAK_SESUAI',
                'message' => 'Status akun siswa tidak sesuai untuk mengakses endpoint ini.',
                'status_akun' => $kodeStatus,
            ], 403);
        }

        return $next($request);
    }
}
