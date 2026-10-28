<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LaporanKelas extends ReadOnlyModel
{
    protected $table = 'laporan_kelas_v';

    protected $primaryKey = 'kelas_id';

    public const CREATED_AT = null;

    public const UPDATED_AT = null;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'jumlah_siswa' => 'integer',
            'rata_pretest' => 'decimal:1',
            'rata_posttest' => 'decimal:1',
            'rata_selisih' => 'decimal:1',
            'kepatuhan_minggu_1' => 'decimal:1',
            'kepatuhan_minggu_2' => 'decimal:1',
            'kepatuhan_minggu_3' => 'decimal:1',
        ];
    }

    /**
     * @return BelongsTo<Kelas, $this>
     */
    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class, 'kelas_id', 'kelas_id');
    }
}
