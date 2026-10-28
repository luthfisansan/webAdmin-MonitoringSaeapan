<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class AntreanVerifikasi extends ReadOnlyModel
{
    protected $table = 'antrean_verifikasi_v';

    protected $primaryKey = 'siswa_id';

    public const CREATED_AT = null;

    public const UPDATED_AT = null;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'item_terisi' => 'integer',
            'item_total' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Siswa, $this>
     */
    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'siswa_id', 'siswa_id');
    }

    /**
     * @return HasOne<RiwayatStatusAkun, $this>
     */
    public function riwayatTerakhir(): HasOne
    {
        return $this->hasOne(RiwayatStatusAkun::class, 'siswa_id', 'siswa_id')
            ->latestOfMany('riwayat_status_akun_id');
    }
}
