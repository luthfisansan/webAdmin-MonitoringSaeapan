<?php

namespace Database\Seeders;

use App\Models\Kelas;
use App\Models\Kuesioner;
use App\Models\MateriEdukasi;
use App\Models\OpsiJawaban;
use App\Models\Program;
use App\Models\Soal;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DevContentSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed konten pengembangan dummy (TIDAK dipanggil oleh DatabaseSeeder).
     */
    public function run(): void
    {
        foreach (['8A', '8B', '8C'] as $namaKelas) {
            Kelas::query()->updateOrCreate(
                ['nama_kelas' => $namaKelas, 'tahun_ajaran' => '2025/2026'],
                ['aktif' => true],
            );
        }

        Program::query()->updateOrCreate(
            ['nama_program' => '[DUMMY] Program Sarapan Sehat 3 Minggu'],
            [
                'tanggal_mulai' => now()->toDateString(),
                'durasi_minggu' => 3,
                'aktif' => true,
            ],
        );

        $pretest = Kuesioner::query()->where('jenis', 'PRETEST')->where('aktif', true)->first()
            ?? Kuesioner::query()->create([
                'jenis' => 'PRETEST',
                'judul' => '[DUMMY] Pretest Pengetahuan Gizi Sarapan',
                'aktif' => true,
            ]);

        $posttest = Kuesioner::query()->where('jenis', 'POSTTEST')->where('aktif', true)->first()
            ?? Kuesioner::query()->create([
                'jenis' => 'POSTTEST',
                'judul' => '[DUMMY] Posttest Pengetahuan Gizi Sarapan',
                'aktif' => true,
            ]);

        $daftarSoalDummy = [
            1 => [
                'teks_soal' => '[DUMMY] Mengapa sarapan pagi sebelum berangkat sekolah penting bagi remaja?',
                'opsi' => [
                    'A' => ['teks' => '[DUMMY] Membantu menjaga konsentrasi belajar dan pasokan energi pagi hari', 'benar' => true],
                    'B' => ['teks' => '[DUMMY] Menyebabkan rasa kantuk berlebih sepanjang pelajaran', 'benar' => false],
                    'C' => ['teks' => '[DUMMY] Menggantikan seluruh kebutuhan makan siang dan malam', 'benar' => false],
                    'D' => ['teks' => '[DUMMY] Hanya diperlukan saat ada pelajaran olahraga', 'benar' => false],
                ],
            ],
            2 => [
                'teks_soal' => '[DUMMY] Manakah kombinasi menu sarapan yang paling mendekati prinsip gizi seimbang?',
                'opsi' => [
                    'A' => ['teks' => '[DUMMY] Teh manis hangat dan kerupuk saja', 'benar' => false],
                    'B' => ['teks' => '[DUMMY] Nasi putih, telur dadar sayur bayam, pisang, dan air putih', 'benar' => true],
                    'C' => ['teks' => '[DUMMY] Mie instan pedas ditambah minuman bersoda', 'benar' => false],
                    'D' => ['teks' => '[DUMMY] Permen manis dan kopi kemasan', 'benar' => false],
                ],
            ],
            3 => [
                'teks_soal' => '[DUMMY] Berapa proporsi perkiraan kebutuhan energi harian yang sebaiknya dipenuhi dari sarapan?',
                'opsi' => [
                    'A' => ['teks' => '[DUMMY] Sekitar 1–2% dari kebutuhan harian', 'benar' => false],
                    'B' => ['teks' => '[DUMMY] Sekitar 15–30% dari kebutuhan energi harian', 'benar' => true],
                    'C' => ['teks' => '[DUMMY] Sekitar 80–90% dari kebutuhan energi harian', 'benar' => false],
                    'D' => ['teks' => '[DUMMY] Tidak menyumbang energi sama sekali', 'benar' => false],
                ],
            ],
            4 => [
                'teks_soal' => '[DUMMY] Zat gizi apa pada menu sarapan yang berperan utama membangun dan memperbaiki jaringan tubuh?',
                'opsi' => [
                    'A' => ['teks' => '[DUMMY] Pewarna makanan tambahan', 'benar' => false],
                    'B' => ['teks' => '[DUMMY] Gula pasir murni', 'benar' => false],
                    'C' => ['teks' => '[DUMMY] Protein dari lauk hewani maupun nabati (telur, ayam, tempe, tahu)', 'benar' => true],
                    'D' => ['teks' => '[DUMMY] Penyedap rasa buatan', 'benar' => false],
                ],
            ],
            5 => [
                'teks_soal' => '[DUMMY] Waktu sarapan pagi yang dianjurkan bagi anak sekolah adalah...',
                'opsi' => [
                    'A' => ['teks' => '[DUMMY] Setelah bangun pagi sebelum jam pelajaran dimulai (sekitar pukul 06.00–08.00)', 'benar' => true],
                    'B' => ['teks' => '[DUMMY] Menjelang pukul 12.00 siang', 'benar' => false],
                    'C' => ['teks' => '[DUMMY] Sore hari sepulang sekolah', 'benar' => false],
                    'D' => ['teks' => '[DUMMY] Tengah malam sebelum tidur', 'benar' => false],
                ],
            ],
        ];

        foreach ([$pretest, $posttest] as $kuesionerTarget) {
            foreach ($daftarSoalDummy as $nomor => $item) {
                $soal = Soal::query()->updateOrCreate(
                    [
                        'kuesioner_id' => $kuesionerTarget->kuesioner_id,
                        'nomor' => $nomor,
                    ],
                    [
                        'teks_soal' => $item['teks_soal'],
                    ],
                );

                // Reset opsi benar lama sebelum sinkronisasi agar partial unique index opsi_jawaban_m_benar_uq terjaga
                OpsiJawaban::query()
                    ->where('soal_id', $soal->soal_id)
                    ->update(['benar' => false]);

                foreach ($item['opsi'] as $kodeOpsi => $opsiData) {
                    OpsiJawaban::query()->updateOrCreate(
                        [
                            'soal_id' => $soal->soal_id,
                            'kode_opsi' => $kodeOpsi,
                        ],
                        [
                            'teks_opsi' => $opsiData['teks'],
                            'benar' => $opsiData['benar'],
                        ],
                    );
                }
            }
        }

        $daftarMateriDummy = [
            1 => [
                'judul' => '[DUMMY] Mengapa Sarapan Tidak Boleh Dilewatkan?',
                'ringkasan' => '[DUMMY] Materi wajib 1: manfaat sarapan terhadap kadar glukosa darah, fokus, dan prestasi belajar.',
                'konten' => "[DUMMY KONTEN PENGEMBANGAN]\n\nSetelah tidur malam selama 7–8 jam, cadangan glukosa tubuh menurun. Sarapan pagi mengisi kembali energi otak sehingga siswa lebih fokus mengikuti pelajaran di sekolah.",
                'wajib' => true,
            ],
            2 => [
                'judul' => '[DUMMY] Panduan Piring Sarapan Gizi Seimbang',
                'ringkasan' => '[DUMMY] Materi wajib 2: cara mudah memadukan karbohidrat, protein, sayur/buah, dan air putih saat pagi hari.',
                'konten' => "[DUMMY KONTEN PENGEMBANGAN]\n\nMenu sarapan seimbang tidak harus rumit. Contohnya nasi putih dengan telur dadar bayam, atau roti gandum dengan telur rebus dan buah pisang ditambah segelas air putih atau susu.",
                'wajib' => true,
            ],
            3 => [
                'judul' => '[DUMMY] Tips Menyiapkan Sarapan Praktis dalam 10 Menit',
                'ringkasan' => '[DUMMY] Materi opsional (tidak wajib): ide persiapan malam hari agar tidak terburu-buru di pagi hari.',
                'konten' => "[DUMMY KONTEN PENGEMBANGAN]\n\nMerencanakan menu sarapan sejak malam sebelumnya melalui fitur Planner membantu keluarga menyiapkan bahan lebih cepat di pagi hari.",
                'wajib' => false,
            ],
        ];

        foreach ($daftarMateriDummy as $urutan => $materi) {
            MateriEdukasi::query()->updateOrCreate(
                ['urutan' => $urutan],
                [
                    'judul' => $materi['judul'],
                    'ringkasan' => $materi['ringkasan'],
                    'konten' => $materi['konten'],
                    'wajib' => $materi['wajib'],
                    'aktif' => true,
                ],
            );
        }
    }
}
