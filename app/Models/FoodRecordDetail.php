<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FoodRecordDetail extends Model
{
    protected $table = 'food_record_detail_t';

    protected $primaryKey = 'food_record_detail_id';

    public const CREATED_AT = null;

    public const UPDATED_AT = null;

    public $timestamps = false;

    protected $fillable = [
        'food_record_id',
        'rencana_sarapan_detail_id',
        'status_realisasi_id',
        'nama_makanan',
        'bahan_teks',
        'bahan_makanan_id',
        'urt_id',
        'jumlah_urt',
        'berat_gram',
        'keterangan',
        'urutan',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'jumlah_urt' => 'decimal:2',
            'berat_gram' => 'decimal:2',
            'urutan' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<FoodRecord, $this>
     */
    public function foodRecord(): BelongsTo
    {
        return $this->belongsTo(FoodRecord::class, 'food_record_id', 'food_record_id');
    }

    /**
     * @return BelongsTo<RencanaSarapanDetail, $this>
     */
    public function rencanaSarapanDetail(): BelongsTo
    {
        return $this->belongsTo(RencanaSarapanDetail::class, 'rencana_sarapan_detail_id', 'rencana_sarapan_detail_id');
    }

    /**
     * @return BelongsTo<StatusRealisasi, $this>
     */
    public function statusRealisasi(): BelongsTo
    {
        return $this->belongsTo(StatusRealisasi::class, 'status_realisasi_id', 'status_realisasi_id');
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
}
