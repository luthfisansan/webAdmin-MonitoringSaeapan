<?php

namespace Database\Seeders;

use App\Models\Pengguna;
use App\Models\Peran;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $peranAdmin = Peran::query()->where('kode', 'ADMIN')->firstOrFail();

        $email = (string) config('auth.seed_admin.email');
        $password = (string) config('auth.seed_admin.password');

        Pengguna::query()->updateOrCreate(
            ['email' => strtolower($email)],
            [
                'peran_id' => $peranAdmin->peran_id,
                'nama_lengkap' => 'Sysadmin',
                'kata_sandi_hash' => Hash::make($password),
                'aktif' => true,
            ],
        );
    }
}
