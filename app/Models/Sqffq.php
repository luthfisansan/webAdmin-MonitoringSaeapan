<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sqffq extends Model
{
    protected $table = 'sqffq_t';

    protected $primaryKey = 'sqffq_id';

    public const CREATED_AT = 'dibuat_pada';

    public const UPDATED_AT = 'diubah_pada';

    protected $fillable = [
        'siswa_id',
        'petugas_pengguna_id',
        'tahap',
        'status',
        'tanggal_wawancara',
        'catatan',
        'selesai_pada',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tanggal_wawancara' => 'date',
            'selesai_pada' => 'datetime',
            'dibuat_pada' => 'datetime',
            'diubah_pada' => 'datetime',
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
     * @return BelongsTo<Pengguna, $this>
     */
    public function petugas(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'petugas_pengguna_id', 'pengguna_id');
    }

    /**
     * @return HasMany<SqffqDetail, $this>
     */
    public function sqffqDetail(): HasMany
    {
        return $this->hasMany(SqffqDetail::class, 'sqffq_id', 'sqffq_id');
    }
}
