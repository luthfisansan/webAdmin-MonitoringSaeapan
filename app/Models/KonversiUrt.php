<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KonversiUrt extends Model
{
    protected $table = 'konversi_urt_m';

    protected $primaryKey = 'konversi_urt_id';

    public const CREATED_AT = null;

    public const UPDATED_AT = null;

    public $timestamps = false;

    protected $fillable = [
        'bahan_makanan_id',
        'urt_id',
        'berat_gram',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'berat_gram' => 'decimal:2',
        ];
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
