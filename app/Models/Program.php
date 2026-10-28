<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Program extends Model
{
    protected $table = 'program_m';

    protected $primaryKey = 'program_id';

    public const CREATED_AT = null;

    public const UPDATED_AT = null;

    public $timestamps = false;

    protected $fillable = [
        'nama_program',
        'tanggal_mulai',
        'durasi_minggu',
        'aktif',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tanggal_mulai' => 'date',
            'durasi_minggu' => 'integer',
            'aktif' => 'boolean',
        ];
    }

    /**
     * @return HasMany<PesertaProgram, $this>
     */
    public function pesertaProgram(): HasMany
    {
        return $this->hasMany(PesertaProgram::class, 'program_id', 'program_id');
    }
}
