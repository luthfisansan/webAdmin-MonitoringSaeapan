<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RencanaSarapanDetail extends Model
{
    protected $table = 'rencana_sarapan_detail_t';

    protected $primaryKey = 'rencana_sarapan_detail_id';

    public const CREATED_AT = null;

    public const UPDATED_AT = null;

    public $timestamps = false;

    protected $fillable = [
        'rencana_sarapan_id',
        'nama_makanan',
        'bahan_makanan_id',
        'urt_id',
        'jumlah_urt',
        'urutan',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'jumlah_urt' => 'decimal:2',
            'urutan' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<RencanaSarapan, $this>
     */
    public function rencanaSarapan(): BelongsTo
    {
        return $this->belongsTo(RencanaSarapan::class, 'rencana_sarapan_id', 'rencana_sarapan_id');
    }

    /**
     * @return BelongsTo<BahanMakanan, $this>
     */
    public function bahanMakanan(): BelongsTo
    {
        return $this->belongsTo(BahanMakanan::class, 'bahan_makanan_id', 'bahan_makanan_id');
    }

    /**
     * @return BelongsTo<Urt, $this>
     */
    public function urt(): BelongsTo
    {
        return $this->belongsTo(Urt::class, 'urt_id', 'urt_id');
    }

    /**
     * @return HasMany<FoodRecordDetail, $this>
     */
    public function foodRecordDetail(): HasMany
    {
        return $this->hasMany(FoodRecordDetail::class, 'rencana_sarapan_detail_id', 'rencana_sarapan_detail_id');
    }
}
