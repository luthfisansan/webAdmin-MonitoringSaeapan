<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Kuesioner extends Model
{
    protected $table = 'kuesioner_m';

    protected $primaryKey = 'kuesioner_id';

    public const CREATED_AT = null;

    public const UPDATED_AT = null;

    public $timestamps = false;

    protected $fillable = [
        'jenis',
        'judul',
        'aktif',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'aktif' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Kuesioner $kuesioner): void {
            if ($kuesioner->aktif && filled($kuesioner->jenis)) {
                static::query()
                    ->where('jenis', $kuesioner->jenis)
                    ->when($kuesioner->exists, fn ($query) => $query->whereKeyNot($kuesioner->getKey()))
                    ->where('aktif', true)
                    ->update(['aktif' => false]);
            }
        });
    }

    /**
     * @return HasMany<Soal, $this>
     */
    public function soal(): HasMany
    {
        return $this->hasMany(Soal::class, 'kuesioner_id', 'kuesioner_id');
    }

    /**
     * @return HasMany<HasilKuesioner, $this>
     */
    public function hasilKuesioner(): HasMany
    {
        return $this->hasMany(HasilKuesioner::class, 'kuesioner_id', 'kuesioner_id');
    }
}
