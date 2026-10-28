<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PesertaProgram extends Model
{
    protected $table = 'peserta_program_t';

    protected $primaryKey = 'peserta_program_id';

    public const CREATED_AT = 'dibuat_pada';

    public const UPDATED_AT = null;

    protected $fillable = [
        'siswa_id',
        'program_id',
        'tanggal_mulai',
        'tanggal_selesai',
        'status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tanggal_mulai' => 'date',
            'tanggal_selesai' => 'date',
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
     * @return BelongsTo<Program, $this>
     */
    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class, 'program_id', 'program_id');
    }

    /**
     * @return HasMany<RencanaSarapan, $this>
     */
    public function rencanaSarapan(): HasMany
    {
        return $this->hasMany(RencanaSarapan::class, 'peserta_program_id', 'peserta_program_id');
    }

    /**
     * @return HasMany<FoodRecord, $this>
     */
    public function foodRecord(): HasMany
    {
        return $this->hasMany(FoodRecord::class, 'peserta_program_id', 'peserta_program_id');
    }
}
