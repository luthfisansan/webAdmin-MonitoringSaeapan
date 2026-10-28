<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Frekuensi extends Model
{
    protected $table = 'frekuensi_m';

    protected $primaryKey = 'frekuensi_id';

    public const CREATED_AT = null;

    public const UPDATED_AT = null;

    public $timestamps = false;

    protected $fillable = [
        'kode',
        'label',
        'urutan',
        'kali_per_minggu',
        'skor',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'urutan' => 'integer',
            'kali_per_minggu' => 'decimal:2',
            'skor' => 'decimal:2',
        ];
    }

    /**
     * @return HasMany<SqffqDetail, $this>
     */
    public function sqffqDetail(): HasMany
    {
        return $this->hasMany(SqffqDetail::class, 'frekuensi_id', 'frekuensi_id');
    }
}
