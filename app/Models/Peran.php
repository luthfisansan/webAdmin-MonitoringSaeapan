<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Peran extends Model
{
    protected $table = 'peran_m';

    protected $primaryKey = 'peran_id';

    public const CREATED_AT = null;

    public const UPDATED_AT = null;

    public $timestamps = false;

    protected $fillable = [
        'kode',
        'nama',
    ];

    /**
     * @return HasMany<Pengguna, $this>
     */
    public function pengguna(): HasMany
    {
        return $this->hasMany(Pengguna::class, 'peran_id', 'peran_id');
    }
}
