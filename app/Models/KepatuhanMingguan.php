<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KepatuhanMingguan extends ReadOnlyModel
{
    protected $table = 'kepatuhan_mingguan_v';

    protected $primaryKey = 'peserta_program_id';

    public const CREATED_AT = null;

    public const UPDATED_AT = null;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'minggu_ke' => 'integer',
            'hari_berjalan' => 'integer',
            'hari_tercatat' => 'integer',
            'hari_sesuai' => 'integer',
            'persen_kepatuhan' => 'decimal:1',
        ];
    }

    /**
     * @return BelongsTo<PesertaProgram, $this>
     */
    public function pesertaProgram(): BelongsTo
    {
        return $this->belongsTo(PesertaProgram::class, 'peserta_program_id', 'peserta_program_id');
    }

    /**
     * @return BelongsTo<Siswa, $this>
     */
    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'siswa_id', 'siswa_id');
    }
}
