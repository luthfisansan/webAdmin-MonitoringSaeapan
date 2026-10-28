<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Urt extends Model
{
    protected $table = 'urt_m';

    protected $primaryKey = 'urt_id';

    public const CREATED_AT = null;

    public const UPDATED_AT = null;

    public $timestamps = false;

    protected $fillable = [
        'nama_urt',
    ];

    /**
     * @return HasMany<KonversiUrt, $this>
     */
    public function konversiUrt(): HasMany
    {
        return $this->hasMany(KonversiUrt::class, 'urt_id', 'urt_id');
    }

    /**
     * @return HasMany<RencanaSarapanDetail, $this>
     */
    public function rencanaSarapanDetail(): HasMany
    {
        return $this->hasMany(RencanaSarapanDetail::class, 'urt_id', 'urt_id');
    }

    /**
     * @return HasMany<FoodRecordDetail, $this>
     */
    public function foodRecordDetail(): HasMany
    {
        return $this->hasMany(FoodRecordDetail::class, 'urt_id', 'urt_id');
    }
}
