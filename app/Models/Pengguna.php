<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasName;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Pengguna extends Authenticatable implements FilamentUser, HasName
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'pengguna_m';

    protected $primaryKey = 'pengguna_id';

    public const CREATED_AT = 'dibuat_pada';

    public const UPDATED_AT = 'diubah_pada';

    protected $authPasswordName = 'kata_sandi_hash';

    protected $rememberTokenName = '';

    protected $fillable = [
        'peran_id',
        'email',
        'kata_sandi_hash',
        'nama_lengkap',
        'aktif',
        'terakhir_login_pada',
    ];

    protected $hidden = [
        'kata_sandi_hash',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'aktif' => 'boolean',
            'terakhir_login_pada' => 'datetime',
            'dibuat_pada' => 'datetime',
            'diubah_pada' => 'datetime',
        ];
    }

    public function getAuthPassword(): string
    {
        return $this->kata_sandi_hash;
    }

    public function getAuthPasswordName(): string
    {
        return 'kata_sandi_hash';
    }

    public function getRememberTokenName(): string
    {
        return '';
    }

    public function getFilamentName(): string
    {
        return $this->nama_lengkap;
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return (bool) $this->aktif
            && in_array($this->peran?->kode, ['ADMIN', 'PETUGAS'], true);
    }

    /**
     * @return BelongsTo<Peran, $this>
     */
    public function peran(): BelongsTo
    {
        return $this->belongsTo(Peran::class, 'peran_id', 'peran_id');
    }

    /**
     * @return HasOne<Siswa, $this>
     */
    public function siswa(): HasOne
    {
        return $this->hasOne(Siswa::class, 'pengguna_id', 'pengguna_id');
    }

    /**
     * @return HasMany<Siswa, $this>
     */
    public function siswaTerverifikasi(): HasMany
    {
        return $this->hasMany(Siswa::class, 'terverifikasi_oleh_pengguna_id', 'pengguna_id');
    }

    /**
     * @return HasMany<Sqffq, $this>
     */
    public function sqffq(): HasMany
    {
        return $this->hasMany(Sqffq::class, 'petugas_pengguna_id', 'pengguna_id');
    }

    /**
     * @return HasMany<RiwayatStatusAkun, $this>
     */
    public function riwayatStatusAkun(): HasMany
    {
        return $this->hasMany(RiwayatStatusAkun::class, 'diubah_oleh_pengguna_id', 'pengguna_id');
    }
}
