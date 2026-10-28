<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProgresEdukasi extends ReadOnlyModel
{
    protected $table = 'progres_edukasi_v';

    protected $primaryKey = 'siswa_id';

    public const CREATED_AT = null;

    public const UPDATED_AT = null;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'total_wajib' => 'integer',
            'dibaca' => 'integer',
            'selesai' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Siswa, $this>
     */
    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'siswa_id', 'siswa_id');
    }
}
