<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PastikanSiswaTerverifikasi
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $pengguna = $request->user();
        $siswa = $pengguna?->siswa()->with('statusAkun')->first();

        if (! $siswa || $siswa->statusAkun?->kode !== 'TERVERIFIKASI') {
            return response()->json([
                'code' => 'STATUS_BELUM_TERVERIFIKASI',
                'message' => 'Akun siswa belum berstatus TERVERIFIKASI.',
                'data' => [
                    'status_akun' => $siswa?->statusAkun ? [
                        'kode' => $siswa->statusAkun->kode,
                        'nama' => $siswa->statusAkun->nama,
                    ] : null,
                ],
            ], 403);
        }

        return $next($request);
    }
}

