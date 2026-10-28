<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BacaMateri extends Model
{
    protected $table = 'baca_materi_t';

    protected $primaryKey = 'baca_materi_id';

    public const CREATED_AT = null;

    public const UPDATED_AT = null;

    public $timestamps = false;

    protected $fillable = [
        'siswa_id',
        'materi_edukasi_id',
        'selesai',
        'mulai_pada',
        'selesai_pada',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'selesai' => 'boolean',
            'mulai_pada' => 'datetime',
            'selesai_pada' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Siswa, $this>
     */
    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'siswa_id', 'siswa_id');
    }

    /**
     * @return BelongsTo<MateriEdukasi, $this>
     */
    public function materiEdukasi(): BelongsTo
    {
        return $this->belongsTo(MateriEdukasi::class, 'materi_edukasi_id', 'materi_edukasi_id');
    }
}
