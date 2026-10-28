<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FoodRecord extends Model
{
    protected $table = 'food_record_t';

    protected $primaryKey = 'food_record_id';

    public const CREATED_AT = 'dibuat_pada';

    public const UPDATED_AT = 'diubah_pada';

    protected $fillable = [
        'peserta_program_id',
        'rencana_sarapan_id',
        'tanggal',
        'jam_makan',
        'tempat_makan_id',
        'tidak_sarapan',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'tidak_sarapan' => 'boolean',
            'dibuat_pada' => 'datetime',
            'diubah_pada' => 'datetime',
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
     * @return BelongsTo<RencanaSarapan, $this>
     */
    public function rencanaSarapan(): BelongsTo
    {
        return $this->belongsTo(RencanaSarapan::class, 'rencana_sarapan_id', 'rencana_sarapan_id');
    }

    /**
     * @return BelongsTo<TempatMakan, $this>
     */
    public function tempatMakan(): BelongsTo
    {
        return $this->belongsTo(TempatMakan::class, 'tempat_makan_id', 'tempat_makan_id');
    }

    /**
     * @return HasMany<FoodRecordDetail, $this>
     */
    public function foodRecordDetail(): HasMany
    {
        return $this->hasMany(FoodRecordDetail::class, 'food_record_id', 'food_record_id');
    }
}
