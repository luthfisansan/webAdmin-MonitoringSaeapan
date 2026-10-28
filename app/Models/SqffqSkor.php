<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SqffqSkor extends ReadOnlyModel
{
    protected $table = 'sqffq_skor_v';

    protected $primaryKey = 'sqffq_id';

    public const CREATED_AT = null;

    public const UPDATED_AT = null;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'total_skor' => 'decimal:2',
            'jumlah_terisi' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Sqffq, $this>
     */
    public function sqffq(): BelongsTo
    {
        return $this->belongsTo(Sqffq::class, 'sqffq_id', 'sqffq_id');
    }

    /**
     * @return BelongsTo<Siswa, $this>
     */
    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'siswa_id', 'siswa_id');
    }
}
