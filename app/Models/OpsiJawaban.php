<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OpsiJawaban extends Model
{
    protected $table = 'opsi_jawaban_m';

    protected $primaryKey = 'opsi_jawaban_id';

    public const CREATED_AT = null;

    public const UPDATED_AT = null;

    public $timestamps = false;

    protected $fillable = [
        'soal_id',
        'kode_opsi',
        'teks_opsi',
        'benar',
    ];

    protected $hidden = [
        'benar',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'benar' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (OpsiJawaban $opsi): void {
            if (filled($opsi->kode_opsi)) {
                $opsi->kode_opsi = strtoupper(trim((string) $opsi->kode_opsi));
            }

            if ($opsi->benar && filled($opsi->soal_id)) {
                static::query()
                    ->where('soal_id', $opsi->soal_id)
                    ->when($opsi->exists, fn ($query) => $query->whereKeyNot($opsi->getKey()))
                    ->where('benar', true)
                    ->update(['benar' => false]);
            }
        });
    }

    /**
     * @return BelongsTo<Soal, $this>
     */
    public function soal(): BelongsTo
    {
        return $this->belongsTo(Soal::class, 'soal_id', 'soal_id');
    }

    /**
     * @return HasMany<JawabanKuesioner, $this>
     */
    public function jawabanKuesioner(): HasMany
    {
        return $this->hasMany(JawabanKuesioner::class, 'opsi_jawaban_id', 'opsi_jawaban_id');
    }
}
