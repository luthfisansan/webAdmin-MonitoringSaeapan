<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class MateriEdukasi extends Model
{
    protected $table = 'materi_edukasi_m';

    protected $primaryKey = 'materi_edukasi_id';

    public const CREATED_AT = 'dibuat_pada';

    public const UPDATED_AT = 'diubah_pada';

    protected $fillable = [
        'judul',
        'ringkasan',
        'konten',
        'file_pdf',
        'urutan',
        'wajib',
        'aktif',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'urutan' => 'integer',
            'wajib' => 'boolean',
            'aktif' => 'boolean',
            'dibuat_pada' => 'datetime',
            'diubah_pada' => 'datetime',
        ];
    }

    /**
     * @return HasMany<BacaMateri, $this>
     */
    public function bacaMateri(): HasMany
    {
        return $this->hasMany(BacaMateri::class, 'materi_edukasi_id', 'materi_edukasi_id');
    }

    public function getFilePdfUrlAttribute(): ?string
    {
        if (! $this->file_pdf) {
            return null;
        }

        return Storage::disk('public')->url($this->file_pdf);
    }
}
