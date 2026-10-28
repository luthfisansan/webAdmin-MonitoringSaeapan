<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StatusAkun extends Model
{
    protected $table = 'status_akun_m';

    protected $primaryKey = 'status_akun_id';

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
     * @return HasMany<Siswa, $this>
     */
    public function siswa(): HasMany
    {
        return $this->hasMany(Siswa::class, 'status_akun_id', 'status_akun_id');
    }

    /**
     * @return HasMany<RiwayatStatusAkun, $this>
     */
    public function riwayatStatusAkun(): HasMany
    {
        return $this->hasMany(RiwayatStatusAkun::class, 'status_akun_id', 'status_akun_id');
    }
}
