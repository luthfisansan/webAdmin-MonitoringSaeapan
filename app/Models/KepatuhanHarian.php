<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KepatuhanHarian extends ReadOnlyModel
{
    protected $table = 'kepatuhan_harian_v';

    protected $primaryKey = 'peserta_program_id';

    public const CREATED_AT = null;

    public const UPDATED_AT = null;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'minggu_ke' => 'integer',
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

    /**
     * @return BelongsTo<FoodRecord, $this>
     */
    public function foodRecord(): BelongsTo
    {
        return $this->belongsTo(FoodRecord::class, 'food_record_id', 'food_record_id');
    }
}
