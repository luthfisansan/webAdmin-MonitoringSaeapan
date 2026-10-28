<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HasilKuesioner extends Model
{
    protected $table = 'hasil_kuesioner_t';

    protected $primaryKey = 'hasil_kuesioner_id';

    public const CREATED_AT = null;

    public const UPDATED_AT = null;

    public $timestamps = false;

    protected $fillable = [
        'siswa_id',
        'kuesioner_id',
        'mulai_pada',
        'selesai_pada',
        'jumlah_soal',
        'jumlah_benar',
        'skor',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'mulai_pada' => 'datetime',
            'selesai_pada' => 'datetime',
            'jumlah_soal' => 'integer',
            'jumlah_benar' => 'integer',
            'skor' => 'decimal:2',
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
     * @return BelongsTo<Kuesioner, $this>
     */
    public function kuesioner(): BelongsTo
    {
        return $this->belongsTo(Kuesioner::class, 'kuesioner_id', 'kuesioner_id');
    }

    /**
     * @return HasMany<JawabanKuesioner, $this>
     */
    public function jawabanKuesioner(): HasMany
    {
        return $this->hasMany(JawabanKuesioner::class, 'hasil_kuesioner_id', 'hasil_kuesioner_id');
    }
}
