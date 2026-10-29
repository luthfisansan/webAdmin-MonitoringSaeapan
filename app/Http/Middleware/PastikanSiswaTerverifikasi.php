<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PastikanSiswaTerverifikasi
{
    /**
     * Pastikan akun siswa sudah berstatus TERVERIFIKASI (dibaca langsung dari DB).
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
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

        if ($kodeStatus !== 'TERVERIFIKASI') {
            return response()->json([
                'code' => 'STATUS_BELUM_TERVERIFIKASI',
                'message' => 'Akun siswa belum berstatus TERVERIFIKASI.',
                'status_akun' => $kodeStatus,
            ], 403);
        }

        return $next($request);
    }
}
