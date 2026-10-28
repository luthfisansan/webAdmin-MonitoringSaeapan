<?php

use App\Filament\Pages\Auth\Login;
use App\Filament\Widgets\AntreanVerifikasiWidget;
use App\Filament\Widgets\StatistikSiswaOverview;
use App\Models\Kelas;
use App\Models\Pengguna;
use App\Models\Siswa;
use App\Models\StatusAkun;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

it('allows an active admin to log in and access the dashboard at /admin', function () {
    $admin = Pengguna::factory()->admin()->create([
        'email' => 'admin@example.com',
        'nama_lengkap' => 'Sysadmin Uji',
        'kata_sandi_hash' => Hash::make('rahasia123'),
        'aktif' => true,
    ]);

    Livewire::test(Login::class)
        ->assertFormFieldDoesNotExist('remember')
        ->fillForm([
            'email' => 'admin@example.com',
            'password' => 'rahasia123',
        ])
        ->call('authenticate')
        ->assertHasNoFormErrors()
        ->assertRedirect('/admin');

    $this->assertAuthenticatedAs($admin);

    $this->get('/admin')
        ->assertSuccessful()
        ->assertSee('Sysadmin Uji')
        ->assertSee('Total Siswa')
        ->assertSee('Status: Pretest (PRETEST)')
        ->assertSee('Status: Edukasi (EDUKASI)')
        ->assertSee('Menunggu Verifikasi (MENUNGGU_VERIFIKASI)')
        ->assertSee('Terverifikasi (TERVERIFIKASI)')
        ->assertSee('SQ-FFQ Selesai')
        ->assertSee('Antrean Verifikasi')
        ->assertSee('Belum ada siswa dalam antrean verifikasi');
});

it('allows an active petugas to log in and access the dashboard at /admin', function () {
    $petugas = Pengguna::factory()->petugas()->create([
        'email' => 'petugas@example.com',
        'nama_lengkap' => 'Petugas Gizi',
        'kata_sandi_hash' => Hash::make('rahasia123'),
        'aktif' => true,
    ]);

    Livewire::test(Login::class)
        ->fillForm([
            'email' => 'petugas@example.com',
            'password' => 'rahasia123',
        ])
        ->call('authenticate')
        ->assertHasNoFormErrors()
        ->assertRedirect('/admin');

    $this->assertAuthenticatedAs($petugas);

    $this->get('/admin')
        ->assertSuccessful()
        ->assertSee('Petugas Gizi');
});

it('rejects login when the password is wrong', function () {
    Pengguna::factory()->admin()->create([
        'email' => 'admin@example.com',
        'kata_sandi_hash' => Hash::make('rahasia123'),
        'aktif' => true,
    ]);

    Livewire::test(Login::class)
        ->fillForm([
            'email' => 'admin@example.com',
            'password' => 'password-salah',
        ])
        ->call('authenticate')
        ->assertHasFormErrors(['email']);

    $this->assertGuest();
});

it('forbids a user with role SISWA from logging in or accessing /admin', function () {
    $siswa = Pengguna::factory()->siswa()->create([
        'email' => 'siswa@example.com',
        'kata_sandi_hash' => Hash::make('rahasia123'),
        'aktif' => true,
    ]);

    Livewire::test(Login::class)
        ->fillForm([
            'email' => 'siswa@example.com',
            'password' => 'rahasia123',
        ])
        ->call('authenticate')
        ->assertHasFormErrors(['email']);

    $this->assertGuest();

    $this->actingAs($siswa)
        ->get('/admin')
        ->assertForbidden();
});

it('rejects login when the user account has aktif set to false', function () {
    $nonaktif = Pengguna::factory()->admin()->nonaktif()->create([
        'email' => 'nonaktif@example.com',
        'kata_sandi_hash' => Hash::make('rahasia123'),
    ]);

    Livewire::test(Login::class)
        ->fillForm([
            'email' => 'nonaktif@example.com',
            'password' => 'rahasia123',
        ])
        ->call('authenticate')
        ->assertHasFormErrors(['email']);

    $this->assertGuest();

    $this->actingAs($nonaktif)
        ->get('/admin')
        ->assertForbidden();
});

it('displays students waiting for verification in AntreanVerifikasiWidget and updates stats', function () {
    $admin = Pengguna::factory()->admin()->create();
    $akunSiswa = Pengguna::factory()->siswa()->create([
        'nama_lengkap' => 'Siti Aminah',
    ]);

    $kelas = Kelas::query()->create([
        'nama_kelas' => 'X IPA 1',
        'tahun_ajaran' => '2026/2027',
        'aktif' => true,
    ]);

    $statusMenunggu = StatusAkun::query()->where('kode', 'MENUNGGU_VERIFIKASI')->firstOrFail();

    $siswa = Siswa::query()->create([
        'pengguna_id' => $akunSiswa->pengguna_id,
        'kelas_id' => $kelas->kelas_id,
        'status_akun_id' => $statusMenunggu->status_akun_id,
    ]);

    $this->actingAs($admin);

    Livewire::test(StatistikSiswaOverview::class)
        ->assertSee('Total Siswa')
        ->assertSee('1');

    Livewire::test(AntreanVerifikasiWidget::class)
        ->assertCanSeeTableRecords([$siswa->siswa_id])
        ->assertSee('Siti Aminah')
        ->assertSee('X IPA 1');
});

it('seeds a single idempotent sysadmin account via DatabaseSeeder', function () {
    $this->seed(DatabaseSeeder::class);
    $this->seed(DatabaseSeeder::class);

    $admins = Pengguna::query()
        ->where('email', strtolower((string) config('auth.seed_admin.email')))
        ->get();

    expect($admins)->toHaveCount(1)
        ->and($admins->first()->nama_lengkap)->toBe('Sysadmin')
        ->and($admins->first()->aktif)->toBeTrue()
        ->and($admins->first()->peran->kode)->toBe('ADMIN')
        ->and(Hash::check((string) config('auth.seed_admin.password'), $admins->first()->kata_sandi_hash))->toBeTrue();
});
