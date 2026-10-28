<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Siswa extends Model
{
    protected $table = 'siswa_m';

    protected $primaryKey = 'siswa_id';

    public const CREATED_AT = 'dibuat_pada';

    public const UPDATED_AT = 'diubah_pada';

    protected $fillable = [
        'pengguna_id',
        'kelas_id',
        'status_akun_id',
        'jenis_kelamin',
        'tanggal_lahir',
        'terverifikasi_pada',
        'terverifikasi_oleh_pengguna_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tanggal_lahir' => 'date',
            'terverifikasi_pada' => 'datetime',
            'dibuat_pada' => 'datetime',
            'diubah_pada' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Pengguna, $this>
     */
    public function pengguna(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'pengguna_id', 'pengguna_id');
    }

    /**
     * @return BelongsTo<Kelas, $this>
     */
    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class, 'kelas_id', 'kelas_id');
    }

    /**
     * @return BelongsTo<StatusAkun, $this>
     */
    public function statusAkun(): BelongsTo
    {
        return $this->belongsTo(StatusAkun::class, 'status_akun_id', 'status_akun_id');
    }

    /**
     * @return BelongsTo<Pengguna, $this>
     */
    public function terverifikasiOleh(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'terverifikasi_oleh_pengguna_id', 'pengguna_id');
    }

    /**
     * @return HasMany<RiwayatStatusAkun, $this>
     */
    public function riwayatStatusAkun(): HasMany
    {
        return $this->hasMany(RiwayatStatusAkun::class, 'siswa_id', 'siswa_id');
    }

    /**
     * @return HasMany<HasilKuesioner, $this>
     */
    public function hasilKuesioner(): HasMany
    {
        return $this->hasMany(HasilKuesioner::class, 'siswa_id', 'siswa_id');
    }

    /**
     * @return HasMany<BacaMateri, $this>
     */
    public function bacaMateri(): HasMany
    {
        return $this->hasMany(BacaMateri::class, 'siswa_id', 'siswa_id');
    }

    /**
     * @return HasMany<Sqffq, $this>
     */
    public function sqffq(): HasMany
    {
        return $this->hasMany(Sqffq::class, 'siswa_id', 'siswa_id');
    }

    /**
     * @return HasMany<PesertaProgram, $this>
     */
    public function pesertaProgram(): HasMany
    {
        return $this->hasMany(PesertaProgram::class, 'siswa_id', 'siswa_id');
    }
}
