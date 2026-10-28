<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SkorPengetahuan extends ReadOnlyModel
{
    protected $table = 'skor_pengetahuan_v';

    protected $primaryKey = 'siswa_id';

    public const CREATED_AT = null;

    public const UPDATED_AT = null;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'skor_pretest' => 'decimal:2',
            'skor_posttest' => 'decimal:2',
            'selisih' => 'decimal:2',
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
