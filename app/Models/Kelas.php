<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Kelas extends Model
{
    protected $table = 'kelas_m';

    protected $primaryKey = 'kelas_id';

    public const CREATED_AT = null;

    public const UPDATED_AT = null;

    public $timestamps = false;

    protected $fillable = [
        'nama_kelas',
        'tahun_ajaran',
        'aktif',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'aktif' => 'boolean',
        ];
    }

    /**
     * @return HasMany<Siswa, $this>
     */
    public function siswa(): HasMany
    {
        return $this->hasMany(Siswa::class, 'kelas_id', 'kelas_id');
    }
}
