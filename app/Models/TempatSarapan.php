<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TempatSarapan extends ReadOnlyModel
{
    protected $table = 'tempat_sarapan_v';

    protected $primaryKey = 'tempat_makan_id';

    public const CREATED_AT = null;

    public const UPDATED_AT = null;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'jumlah_catatan' => 'integer',
            'persen' => 'decimal:1',
        ];
    }

    /**
     * @return BelongsTo<Kelas, $this>
     */
    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class, 'kelas_id', 'kelas_id');
    }

    /**
     * @return BelongsTo<TempatMakan, $this>
     */
    public function tempatMakan(): BelongsTo
    {
        return $this->belongsTo(TempatMakan::class, 'tempat_makan_id', 'tempat_makan_id');
    }
}
