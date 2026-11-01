    <?php

use App\Models\Kelas;
use App\Models\MateriEdukasi;
use App\Models\Pengguna;
use App\Models\Peran;
use App\Models\Siswa;
use App\Models\StatusAkun;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('materi edukasi dapat memuat file pdf dan api mengembalikan url file_pdf', function () {
    Storage::fake('public');

    $filePdf = UploadedFile::fake()->create('panduan-sarapan.pdf', 500, 'application/pdf');
    $path = $filePdf->store('materi-pdf', 'public');

    $materi = MateriEdukasi::query()->create([
        'judul' => 'Pedoman Sarapan Sehat PDF',
        'ringkasan' => 'Materi sarapan lengkap dalam format PDF',
        'konten' => null,
        'file_pdf' => $path,
        'urutan' => 1,
        'wajib' => true,
        'aktif' => true,
    ]);

    expect($materi->file_pdf)->toBe($path)
        ->and($materi->file_pdf_url)->toContain($path);

    $peranSiswa = Peran::query()->firstOrCreate(['kode' => 'SISWA'], ['nama' => 'Siswa', 'dibuat_pada' => now()]);
    $statusEdukasi = StatusAkun::query()->firstOrCreate(['kode' => 'EDUKASI'], ['nama' => 'Edukasi']);

    $kelas = Kelas::query()->create([
        'nama_kelas' => '8A',
        'tahun_ajaran' => '2025/2026',
        'aktif' => true,
    ]);

    $pengguna = Pengguna::factory()->create([
        'peran_id' => $peranSiswa->peran_id,
        'aktif' => true,
    ]);

    $siswa = Siswa::query()->create([
        'pengguna_id' => $pengguna->pengguna_id,
        'kelas_id' => $kelas->kelas_id,
        'status_akun_id' => $statusEdukasi->status_akun_id,
    ]);

    $token = $pengguna->createToken('mobile')->plainTextToken;

    // Test GET /api/materi
    $responseList = $this->withToken($token)->getJson('/api/materi');
    $responseList->assertOk()
        ->assertJsonFragment([
            'materi_edukasi_id' => $materi->materi_edukasi_id,
            'judul' => 'Pedoman Sarapan Sehat PDF',
            'file_pdf' => Storage::disk('public')->url($path),
        ]);

    // Test GET /api/materi/{id}
    $responseDetail = $this->withToken($token)->getJson('/api/materi/'.$materi->materi_edukasi_id);
    $responseDetail->assertOk()
        ->assertJsonPath('data.judul', 'Pedoman Sarapan Sehat PDF')
        ->assertJsonPath('data.konten', null)
        ->assertJsonPath('data.file_pdf', Storage::disk('public')->url($path));
});
