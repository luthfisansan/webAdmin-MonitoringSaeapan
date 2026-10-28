<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TempatMakan extends Model
{
    protected $table = 'tempat_makan_m';

    protected $primaryKey = 'tempat_makan_id';

    public const CREATED_AT = null;

    public const UPDATED_AT = null;

    public $timestamps = false;

    protected $fillable = [
        'kode',
        'nama',
    ];

    /**
     * @return HasMany<RencanaSarapan, $this>
     */
    public function rencanaSarapan(): HasMany
    {
        return $this->hasMany(RencanaSarapan::class, 'tempat_makan_id', 'tempat_makan_id');
    }

    /**
     * @return HasMany<FoodRecord, $this>
     */
    public function foodRecord(): HasMany
    {
        return $this->hasMany(FoodRecord::class, 'tempat_makan_id', 'tempat_makan_id');
    }
}
