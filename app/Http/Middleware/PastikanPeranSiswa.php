<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PastikanPeranSiswa
{
    /**
     * Pastikan pengguna terautentikasi adalah akun SISWA aktif yang memiliki profil siswa_m.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $pengguna = $request->user()?->fresh(['peran', 'siswa']);

        if (! $pengguna || ! $pengguna->aktif || $pengguna->peran?->kode !== 'SISWA' || ! $pengguna->siswa) {
            return response()->json([
                'code' => 'AKSES_KHUSUS_SISWA',
                'data' => null,
                'message' => 'Endpoint ini hanya dapat diakses oleh akun siswa aktif.',
            ], 403);
        }

        return $next($request);
    }
}
