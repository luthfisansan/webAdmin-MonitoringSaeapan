<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SqffqRekap extends ReadOnlyModel
{
    protected $table = 'sqffq_rekap_v';

    protected $primaryKey = 'sqffq_id';

    public const CREATED_AT = null;

    public const UPDATED_AT = null;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'jumlah_terisi' => 'integer',
            'jumlah_bahan' => 'integer',
            'total_skor' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<Sqffq, $this>
     */
    public function sqffq(): BelongsTo
    {
        return $this->belongsTo(Sqffq::class, 'sqffq_id', 'sqffq_id');
    }

    /**
     * @return BelongsTo<Siswa, $this>
     */
    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'siswa_id', 'siswa_id');
    }

    /**
     * @return BelongsTo<KelompokBahan, $this>
     */
    public function kelompokBahan(): BelongsTo
    {
        return $this->belongsTo(KelompokBahan::class, 'kelompok_bahan_id', 'kelompok_bahan_id');
    }
}
