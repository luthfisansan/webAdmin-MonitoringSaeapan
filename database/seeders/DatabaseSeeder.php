<?php

namespace Database\Seeders;

use App\Models\Kelas;
use App\Models\Kuesioner;
use App\Models\MateriEdukasi;
use App\Models\OpsiJawaban;
use App\Models\Pengguna;
use App\Models\Peran;
use App\Models\Program;
use App\Models\Soal;
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
            ]
        );

        foreach (['8A', '8B', '8C'] as $namaKelas) {
            Kelas::query()->updateOrCreate(
                ['nama_kelas' => $namaKelas, 'tahun_ajaran' => '2025/2026'],
                ['aktif' => true]
            );
        }

        Program::query()->updateOrCreate(
            ['nama_program' => 'Program Monitoring Sarapan 3 Minggu'],
            [
                'tanggal_mulai' => now()->startOfWeek()->toDateString(),
                'durasi_minggu' => 3,
                'aktif' => true,
            ]
        );

        $this->seedDummySoal('PRETEST');
        $this->seedDummySoal('POSTTEST');
        $this->seedDummyMateri();
    }

    private function seedDummySoal(string $jenisKuesioner): void
    {
        $kuesioner = Kuesioner::query()->where('jenis', $jenisKuesioner)->first();

        if (! $kuesioner) {
            return;
        }

        $daftarSoal = [
            1 => [
                'teks_soal' => '[DUMMY] Apa manfaat utama sarapan pagi bagi siswa sekolah?',
                'opsi' => [
                    'A' => ['teks' => 'Membantu konsentrasi belajar dan memenuhi kebutuhan energi pagi', 'benar' => true],
                    'B' => ['teks' => 'Membuat mengantuk di kelas', 'benar' => false],
                    'C' => ['teks' => 'Menggantikan makan malam sepenuhnya', 'benar' => false],
                    'D' => ['teks' => 'Menurunkan daya ingat', 'benar' => false],
                ],
            ],
            2 => [
                'teks_soal' => '[DUMMY] Komposisi sarapan gizi seimbang sebaiknya mencakup apa saja?',
                'opsi' => [
                    'A' => ['teks' => 'Hanya minuman manis', 'benar' => false],
                    'B' => ['teks' => 'Karbohidrat, protein, sayur/buah, dan air minum yang cukup', 'benar' => true],
                    'C' => ['teks' => 'Makanan ringan kemasan saja', 'benar' => false],
                    'D' => ['teks' => 'Kopi tanpa makanan pendamping', 'benar' => false],
                ],
            ],
        ];

        foreach ($daftarSoal as $nomor => $item) {
            $soal = Soal::query()->updateOrCreate(
                [
                    'kuesioner_id' => $kuesioner->kuesioner_id,
                    'nomor' => $nomor,
                ],
                [
                    'teks_soal' => $item['teks_soal'],
                ]
            );

            foreach ($item['opsi'] as $kodeOpsi => $opsi) {
                OpsiJawaban::query()->updateOrCreate(
                    [
                        'soal_id' => $soal->soal_id,
                        'kode_opsi' => $kodeOpsi,
                    ],
                    [
                        'teks_opsi' => $opsi['teks'],
                        'benar' => $opsi['benar'],
                    ]
                );
            }
        }
    }

    private function seedDummyMateri(): void
    {
        $daftarMateri = [
            1 => [
                'judul' => '[DUMMY] Pentingnya Sarapan Sehat Setiap Pagi',
                'ringkasan' => 'Penjelasan ringkas mengenai manfaat sarapan pagi terhadap fokus belajar dan energi harian.',
                'konten' => 'Sarapan pagi berperan penting dalam menjaga kadar glukosa darah setelah berpuasa semalaman sehingga membantu konsentrasi belajar di sekolah.',
                'wajib' => true,
            ],
            2 => [
                'judul' => '[DUMMY] Menyusun Piring Sarapan Gizi Seimbang',
                'ringkasan' => 'Panduan memilih sumber karbohidrat, lauk pauk berprotein, sayur, dan buah untuk sarapan.',
                'konten' => 'Piring sarapan yang baik mengandung makanan pokok, lauk hewani/nabati, sayur atau buah, serta air putih.',
                'wajib' => true,
            ],
        ];

        foreach ($daftarMateri as $urutan => $materi) {
            MateriEdukasi::query()->updateOrCreate(
                ['urutan' => $urutan],
                [
                    'judul' => $materi['judul'],
                    'ringkasan' => $materi['ringkasan'],
                    'konten' => $materi['konten'],
                    'wajib' => $materi['wajib'],
                    'aktif' => true,
                ]
            );
        }
    }
}
