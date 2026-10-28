<?php

namespace Database\Factories;

use App\Models\Pengguna;
use App\Models\Peran;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<Pengguna>
 */
class PenggunaFactory extends Factory
{
    protected $model = Pengguna::class;

    protected static ?string $password = null;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'peran_id' => fn () => Peran::query()->where('kode', 'ADMIN')->value('peran_id')
                ?? Peran::query()->firstOrCreate(['kode' => 'ADMIN'], ['nama' => 'Administrator'])->peran_id,
            'email' => fake()->unique()->safeEmail(),
            'kata_sandi_hash' => static::$password ??= Hash::make('password'),
            'nama_lengkap' => fake()->name(),
            'aktif' => true,
        ];
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes): array => [
            'peran_id' => Peran::query()->where('kode', 'ADMIN')->value('peran_id'),
        ]);
    }

    public function petugas(): static
    {
        return $this->state(fn (array $attributes): array => [
            'peran_id' => Peran::query()->where('kode', 'PETUGAS')->value('peran_id'),
        ]);
    }

    public function siswa(): static
    {
        return $this->state(fn (array $attributes): array => [
            'peran_id' => Peran::query()->where('kode', 'SISWA')->value('peran_id'),
        ]);
    }

    public function nonaktif(): static
    {
        return $this->state(fn (array $attributes): array => [
            'aktif' => false,
        ]);
    }
}
