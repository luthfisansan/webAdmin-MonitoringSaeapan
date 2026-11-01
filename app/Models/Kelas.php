<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Kelas extends Model
{
    protected $table = 'kelas_m';

    protected $primaryKey = 'kelas_id';

    public const CREATED_AT = null;

    public const UPDATED_AT = null;

    public $timestamps = false;

    protected $fillable = [
        'sekolah_id',
        'nama_kelas',
        'tahun_ajaran',
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
        static::creating(function (Kelas $kelas): void {
            if (empty($kelas->sekolah_id)) {
                $kelas->sekolah_id = Sekolah::query()->orderBy('sekolah_id')->value('sekolah_id')
                    ?? Sekolah::query()->create(['nama_sekolah' => 'SMP Negeri 1 (Sekolah Utama)'])->sekolah_id;
            }
        });
    }

    /**
     * @return BelongsTo<Sekolah, $this>
     */
    public function sekolah(): BelongsTo
    {
        return $this->belongsTo(Sekolah::class, 'sekolah_id', 'sekolah_id');
    }

    /**
     * @return HasMany<Siswa, $this>
     */
    public function siswa(): HasMany
    {
        return $this->hasMany(Siswa::class, 'kelas_id', 'kelas_id');
    }
}
