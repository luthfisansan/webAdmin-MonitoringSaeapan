<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StatusRealisasi extends Model
{
    protected $table = 'status_realisasi_m';

    protected $primaryKey = 'status_realisasi_id';

    public const CREATED_AT = null;

    public const UPDATED_AT = null;

    public $timestamps = false;

    protected $fillable = [
        'kode',
        'nama',
    ];

    /**
     * @return HasMany<FoodRecordDetail, $this>
     */
    public function foodRecordDetail(): HasMany
    {
        return $this->hasMany(FoodRecordDetail::class, 'status_realisasi_id', 'status_realisasi_id');
    }
}
