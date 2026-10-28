<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RiwayatStatusAkun extends Model
{
    protected $table = 'riwayat_status_akun_t';

    protected $primaryKey = 'riwayat_status_akun_id';

    public const CREATED_AT = 'dibuat_pada';

    public const UPDATED_AT = null;

    protected $fillable = [
        'siswa_id',
        'status_akun_id',
        'diubah_oleh_pengguna_id',
        'catatan',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'dibuat_pada' => 'datetime',
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
     * @return BelongsTo<StatusAkun, $this>
     */
    public function statusAkun(): BelongsTo
    {
        return $this->belongsTo(StatusAkun::class, 'status_akun_id', 'status_akun_id');
    }

    /**
     * @return BelongsTo<Pengguna, $this>
     */
    public function diubahOleh(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'diubah_oleh_pengguna_id', 'pengguna_id');
    }
}
