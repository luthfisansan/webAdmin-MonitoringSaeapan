<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FoodRecordDetailView extends ReadOnlyModel
{
    protected $table = 'food_record_detail_v';

    protected $primaryKey = 'food_record_detail_id';

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
            'tidak_sarapan' => 'boolean',
            'jumlah_urt' => 'decimal:2',
            'berat_gram' => 'decimal:2',
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
     * @return BelongsTo<PesertaProgram, $this>
     */
    public function pesertaProgram(): BelongsTo
    {
        return $this->belongsTo(PesertaProgram::class, 'peserta_program_id', 'peserta_program_id');
    }

    /**
     * @return BelongsTo<FoodRecord, $this>
     */
    public function foodRecord(): BelongsTo
    {
        return $this->belongsTo(FoodRecord::class, 'food_record_id', 'food_record_id');
    }

    /**
     * @return BelongsTo<FoodRecordDetail, $this>
     */
    public function foodRecordDetail(): BelongsTo
    {
        return $this->belongsTo(FoodRecordDetail::class, 'food_record_detail_id', 'food_record_detail_id');
    }
}
