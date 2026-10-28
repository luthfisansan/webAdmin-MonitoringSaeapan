<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KelompokBahan extends Model
{
    protected $table = 'kelompok_bahan_m';

    protected $primaryKey = 'kelompok_bahan_id';

    public const CREATED_AT = null;

    public const UPDATED_AT = null;

    public $timestamps = false;

    protected $fillable = [
        'kode',
        'nama',
        'urutan',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'urutan' => 'integer',
        ];
    }

    /**
     * @return HasMany<BahanMakanan, $this>
     */
    public function bahanMakanan(): HasMany
    {
        return $this->hasMany(BahanMakanan::class, 'kelompok_bahan_id', 'kelompok_bahan_id');
    }
}
