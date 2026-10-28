<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SqffqDetail extends Model
{
    protected $table = 'sqffq_detail_t';

    protected $primaryKey = 'sqffq_detail_id';

    public const CREATED_AT = null;

    public const UPDATED_AT = null;

    public $timestamps = false;

    protected $fillable = [
        'sqffq_id',
        'bahan_makanan_id',
        'frekuensi_id',
        'porsi_gram',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'porsi_gram' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<Sqffq, $this>
     */
    public function sqffq(): BelongsTo
    {
        return $this->belongsTo(Sqffq::class, 'sqffq_id', 'sqffq_id');
    }

    /**
     * @return BelongsTo<BahanMakanan, $this>
     */
    public function bahanMakanan(): BelongsTo
    {
        return $this->belongsTo(BahanMakanan::class, 'bahan_makanan_id', 'bahan_makanan_id');
    }

    /**
     * @return BelongsTo<Frekuensi, $this>
     */
    public function frekuensi(): BelongsTo
    {
        return $this->belongsTo(Frekuensi::class, 'frekuensi_id', 'frekuensi_id');
    }
}
