<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class RencanaSarapan extends Model
{
    protected $table = 'rencana_sarapan_t';

    protected $primaryKey = 'rencana_sarapan_id';

    public const CREATED_AT = 'dibuat_pada';

    public const UPDATED_AT = 'diubah_pada';

    protected $fillable = [
        'peserta_program_id',
        'tanggal_sarapan',
        'jam_rencana',
        'tempat_makan_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tanggal_sarapan' => 'date',
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
     * @return BelongsTo<TempatMakan, $this>
     */
    public function tempatMakan(): BelongsTo
    {
        return $this->belongsTo(TempatMakan::class, 'tempat_makan_id', 'tempat_makan_id');
    }

    /**
     * @return HasMany<RencanaSarapanDetail, $this>
     */
    public function rencanaSarapanDetail(): HasMany
    {
        return $this->hasMany(RencanaSarapanDetail::class, 'rencana_sarapan_id', 'rencana_sarapan_id');
    }

    /**
     * @return HasOne<FoodRecord, $this>
     */
    public function foodRecord(): HasOne
    {
        return $this->hasOne(FoodRecord::class, 'rencana_sarapan_id', 'rencana_sarapan_id');
    }
}
