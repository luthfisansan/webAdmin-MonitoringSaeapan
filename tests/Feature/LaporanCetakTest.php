<?php

use App\Models\Kelas;
use App\Models\Pengguna;
use App\Models\Peran;
use App\Models\Siswa;
use App\Models\StatusAkun;

test('tamu yang belum login diarahkan ke login saat mengakses cetak laporan', function () {
    $response = $this->get('/admin/laporan/cetak');

    $response->assertRedirect('/admin/login');
});

test('siswa tidak boleh mengakses cetak laporan dan mendapat 403', function () {
    $peranSiswa = Peran::query()->firstOrCreate(
        ['kode' => 'SISWA'],
        ['nama' => 'Siswa', 'dibuat_pada' => now()]
    );

    $pengguna = Pengguna::factory()->create([
        'peran_id' => $peranSiswa->peran_id,
        'aktif' => true,
    ]);

    $response = $this->actingAs($pengguna)->get('/admin/laporan/cetak');

    $response->assertForbidden();
});

test('petugas dapat membuka cetak laporan umum dengan sukses', function () {
    $peranPetugas = Peran::query()->firstOrCreate(
        ['kode' => 'PETUGAS'],
        ['nama' => 'Petugas', 'dibuat_pada' => now()]
    );

    $petugas = Pengguna::factory()->create([
        'peran_id' => $peranPetugas->peran_id,
        'aktif' => true,
    ]);

    $response = $this->actingAs($petugas)->get('/admin/laporan/cetak');

    $response->assertOk()
        ->assertSee('DIGITAL BREAKFAST PLANNER')
        ->assertSee('REKAP MONITORING SARAPAN')
        ->assertSee('Semua Kelas')
        ->assertSee('Cetak / Simpan PDF');
});

test('petugas dapat memfilter cetak laporan berdasarkan kelas', function () {
    $peranPetugas = Peran::query()->firstOrCreate(
        ['kode' => 'PETUGAS'],
        ['nama' => 'Petugas', 'dibuat_pada' => now()]
    );

    $petugas = Pengguna::factory()->create([
        'peran_id' => $peranPetugas->peran_id,
        'aktif' => true,
    ]);

    $kelas = Kelas::query()->create([
        'nama_kelas' => 'Kelas Uji Cetak',
        'tahun_ajaran' => '2026/2027',
        'aktif' => true,
    ]);

    $response = $this->actingAs($petugas)->get("/admin/laporan/cetak?kelas_id={$kelas->kelas_id}");

    $response->assertOk()
        ->assertSee('Kelas Uji Cetak');
});

test('petugas dapat mencetak laporan rekap individu siswa', function () {
    $peranPetugas = Peran::query()->firstOrCreate(
        ['kode' => 'PETUGAS'],
        ['nama' => 'Petugas', 'dibuat_pada' => now()]
    );

    $petugas = Pengguna::factory()->create([
        'peran_id' => $peranPetugas->peran_id,
        'aktif' => true,
    ]);

    $peranSiswa = Peran::query()->firstOrCreate(
        ['kode' => 'SISWA'],
        ['nama' => 'Siswa', 'dibuat_pada' => now()]
    );

    $statusTerverifikasi = StatusAkun::query()->firstOrCreate(
        ['kode' => 'TERVERIFIKASI'],
        ['nama' => 'Terverifikasi', 'urutan' => 4, 'dibuat_pada' => now()]
    );

    $kelas = Kelas::query()->create([
        'nama_kelas' => 'Kelas Siswa Cetak',
        'tahun_ajaran' => '2026/2027',
        'aktif' => true,
    ]);

    $penggunaSiswa = Pengguna::factory()->create([
        'peran_id' => $peranSiswa->peran_id,
        'nama_lengkap' => 'Siswa Uji Cetak',
        'aktif' => true,
    ]);

    $siswa = Siswa::query()->create([
        'pengguna_id' => $penggunaSiswa->pengguna_id,
        'kelas_id' => $kelas->kelas_id,
        'status_akun_id' => $statusTerverifikasi->status_akun_id,
    ]);

    $response = $this->actingAs($petugas)->get("/admin/detail-siswa/cetak?siswa_id={$siswa->siswa_id}");

    $response->assertOk()
        ->assertSee('LEMBAR REKAP INDIVIDU')
        ->assertSee('Siswa Uji Cetak')
        ->assertSee('Kelas Siswa Cetak');
});
