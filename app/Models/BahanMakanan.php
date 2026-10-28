<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BahanMakanan extends Model
{
    protected $table = 'bahan_makanan_m';

    protected $primaryKey = 'bahan_makanan_id';

    public const CREATED_AT = null;

    public const UPDATED_AT = null;

    public $timestamps = false;

    protected $fillable = [
        'kelompok_bahan_id',
        'nama_bahan',
        'urutan',
        'porsi_standar_gram',
        'aktif',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'urutan' => 'integer',
            'porsi_standar_gram' => 'decimal:2',
            'aktif' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<KelompokBahan, $this>
     */
    public function kelompokBahan(): BelongsTo
    {
        return $this->belongsTo(KelompokBahan::class, 'kelompok_bahan_id', 'kelompok_bahan_id');
    }

    /**
     * @return HasMany<KonversiUrt, $this>
     */
    public function konversiUrt(): HasMany
    {
        return $this->hasMany(KonversiUrt::class, 'bahan_makanan_id', 'bahan_makanan_id');
    }

    /**
     * @return HasMany<SqffqDetail, $this>
     */
    public function sqffqDetail(): HasMany
    {
        return $this->hasMany(SqffqDetail::class, 'bahan_makanan_id', 'bahan_makanan_id');
    }

    /**
     * @return HasMany<RencanaSarapanDetail, $this>
     */
    public function rencanaSarapanDetail(): HasMany
    {
        return $this->hasMany(RencanaSarapanDetail::class, 'bahan_makanan_id', 'bahan_makanan_id');
    }

    /**
     * @return HasMany<FoodRecordDetail, $this>
     */
    public function foodRecordDetail(): HasMany
    {
        return $this->hasMany(FoodRecordDetail::class, 'bahan_makanan_id', 'bahan_makanan_id');
    }
}
