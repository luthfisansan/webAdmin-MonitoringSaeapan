<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Soal extends Model
{
    protected $table = 'soal_m';

    protected $primaryKey = 'soal_id';

    public const CREATED_AT = null;

    public const UPDATED_AT = null;

    public $timestamps = false;

    protected $fillable = [
        'kuesioner_id',
        'nomor',
        'teks_soal',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'nomor' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Kuesioner, $this>
     */
    public function kuesioner(): BelongsTo
    {
        return $this->belongsTo(Kuesioner::class, 'kuesioner_id', 'kuesioner_id');
    }

    /**
     * @return HasMany<OpsiJawaban, $this>
     */
    public function opsiJawaban(): HasMany
    {
        return $this->hasMany(OpsiJawaban::class, 'soal_id', 'soal_id');
    }

    /**
     * @return HasMany<JawabanKuesioner, $this>
     */
    public function jawabanKuesioner(): HasMany
    {
        return $this->hasMany(JawabanKuesioner::class, 'soal_id', 'soal_id');
    }
}
