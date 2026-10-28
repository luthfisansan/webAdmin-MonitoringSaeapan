<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PolaKonsumsiKelompok extends ReadOnlyModel
{
    protected $table = 'pola_konsumsi_kelompok_v';

    protected $primaryKey = 'kelompok_bahan_id';

    public const CREATED_AT = null;

    public const UPDATED_AT = null;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'jumlah_responden' => 'integer',
            'jumlah_sering' => 'integer',
            'persen_sering_3x_minggu' => 'decimal:1',
        ];
    }

    /**
     * @return BelongsTo<Kelas, $this>
     */
    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class, 'kelas_id', 'kelas_id');
    }

    /**
     * @return BelongsTo<KelompokBahan, $this>
     */
    public function kelompokBahan(): BelongsTo
    {
        return $this->belongsTo(KelompokBahan::class, 'kelompok_bahan_id', 'kelompok_bahan_id');
    }
}
