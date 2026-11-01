<?php

use App\Models\Kelas;
use App\Models\Pengguna;
use App\Models\Peran;
use App\Models\Sekolah;

test('api sekolah mengembalikan daftar sekolah aktif', function () {
    $sekolahA = Sekolah::query()->create([
        'nama_sekolah' => 'SMP Negeri 1 Merdeka',
        'npsn' => '20101111',
        'alamat' => 'Jl. Merdeka No. 1',
        'aktif' => true,
    ]);

    $sekolahB = Sekolah::query()->create([
        'nama_sekolah' => 'SMP Negeri 2 Juara',
        'npsn' => '20102222',
        'alamat' => 'Jl. Juara No. 2',
        'aktif' => true,
    ]);

    Sekolah::query()->create([
        'nama_sekolah' => 'SMP Nonaktif',
        'npsn' => '20109999',
        'aktif' => false,
    ]);

    $response = $this->getJson('/api/sekolah');

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                '*' => ['sekolah_id', 'nama_sekolah', 'npsn', 'alamat'],
            ],
            'message',
        ])
        ->assertJsonFragment(['nama_sekolah' => 'SMP Negeri 1 Merdeka'])
        ->assertJsonFragment(['nama_sekolah' => 'SMP Negeri 2 Juara'])
        ->assertJsonMissing(['nama_sekolah' => 'SMP Nonaktif']);
});

test('api kelas dapat difilter berdasarkan sekolah_id', function () {
    $sekolah1 = Sekolah::query()->create([
        'nama_sekolah' => 'SMP Negeri 1 Test',
        'aktif' => true,
    ]);

    $sekolah2 = Sekolah::query()->create([
        'nama_sekolah' => 'SMP Negeri 2 Test',
        'aktif' => true,
    ]);

    $kelas1 = Kelas::query()->create([
        'sekolah_id' => $sekolah1->sekolah_id,
        'nama_kelas' => '7A SMP 1',
        'tahun_ajaran' => '2025/2026',
        'aktif' => true,
    ]);

    $kelas2 = Kelas::query()->create([
        'sekolah_id' => $sekolah2->sekolah_id,
        'nama_kelas' => '7A SMP 2',
        'tahun_ajaran' => '2025/2026',
        'aktif' => true,
    ]);

    $responseAll = $this->getJson('/api/kelas');
    $responseAll->assertOk()
        ->assertJsonFragment(['nama_kelas' => '7A SMP 1'])
        ->assertJsonFragment(['nama_kelas' => '7A SMP 2']);

    $responseFilter = $this->getJson('/api/kelas?sekolah_id='.$sekolah1->sekolah_id);
    $responseFilter->assertOk()
        ->assertJsonFragment(['nama_kelas' => '7A SMP 1'])
        ->assertJsonMissing(['nama_kelas' => '7A SMP 2']);
});

test('registrasi dan me menyertakan informasi sekolah siswa', function () {
    $sekolah = Sekolah::query()->create([
        'nama_sekolah' => 'SMP Pelita Harapan',
        'aktif' => true,
    ]);

    $kelas = Kelas::query()->create([
        'sekolah_id' => $sekolah->sekolah_id,
        'nama_kelas' => '9C',
        'tahun_ajaran' => '2025/2026',
        'aktif' => true,
    ]);

    $responseRegister = $this->postJson('/api/auth/register', [
        'nama_lengkap' => 'Budi Santoso',
        'email' => 'budi.santoso@siswa.id',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'kelas_id' => $kelas->kelas_id,
    ]);

    $responseRegister->assertCreated()
        ->assertJsonPath('data.user.sekolah.sekolah_id', $sekolah->sekolah_id)
        ->assertJsonPath('data.user.sekolah.nama_sekolah', 'SMP Pelita Harapan');

    $token = $responseRegister->json('data.token');

    $responseMe = $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/me');

    $responseMe->assertOk()
        ->assertJsonPath('data.sekolah.sekolah_id', $sekolah->sekolah_id)
        ->assertJsonPath('data.sekolah.nama_sekolah', 'SMP Pelita Harapan');
});

test('petugas dapat memfilter cetak laporan berdasarkan sekolah', function () {
    $peranPetugas = Peran::query()->firstOrCreate(
        ['kode' => 'PETUGAS'],
        ['nama' => 'Petugas', 'dibuat_pada' => now()]
    );

    $petugas = Pengguna::factory()->create([
        'peran_id' => $peranPetugas->peran_id,
        'aktif' => true,
    ]);

    $sekolah = Sekolah::query()->create([
        'nama_sekolah' => 'SMP Negeri 5 Bintang',
        'aktif' => true,
    ]);

    $response = $this->actingAs($petugas)->get('/admin/laporan/cetak?sekolah_id='.$sekolah->sekolah_id);

    $response->assertOk()
        ->assertSee('SMP Negeri 5 Bintang');
});
