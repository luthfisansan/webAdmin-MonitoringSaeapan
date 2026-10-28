<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SiswaStatus extends ReadOnlyModel
{
    protected $table = 'siswa_status_v';

    protected $primaryKey = 'siswa_id';

    public const CREATED_AT = null;

    public const UPDATED_AT = null;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'pretest_selesai' => 'boolean',
            'posttest_selesai' => 'boolean',
            'materi_wajib_total' => 'integer',
            'materi_wajib_dibaca' => 'integer',
            'hari_sesuai' => 'integer',
            'hari_berjalan' => 'integer',
            'persen_kepatuhan' => 'decimal:1',
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
     * @return BelongsTo<Kelas, $this>
     */
    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class, 'kelas_id', 'kelas_id');
    }

    /**
     * @return BelongsTo<StatusAkun, $this>
     */
    public function statusAkun(): BelongsTo
    {
        return $this->belongsTo(StatusAkun::class, 'status_akun_id', 'status_akun_id');
    }
}
