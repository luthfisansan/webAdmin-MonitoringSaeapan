<?php

namespace App\Filament\Pages;

use App\Models\FoodRecordDetailView;
use App\Models\HasilKuesioner;
use App\Models\KepatuhanHarian;
use App\Models\KepatuhanMingguan;
use App\Models\ProgresEdukasi;
use App\Models\Siswa;
use App\Models\SkorPengetahuan;
use App\Models\SqffqRekap;
use App\Models\SqffqSkor;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Livewire\Attributes\Url;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DetailSiswa extends Page
{
    protected static ?string $title = 'Detail siswa';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'detail-siswa';

    protected string $view = 'filament.pages.detail-siswa';

    #[Url(as: 'siswa_id', except: null)]
    public ?int $siswaId = null;

    #[Url(as: 'tab', except: 'food_record')]
    public string $tabAktif = 'food_record';

    #[Url(as: 'minggu', except: null)]
    public ?int $mingguFilter = null;

    public function mount(): void
    {
        if ($this->siswaId === null && request()->filled('siswa_id')) {
            $this->siswaId = request()->integer('siswa_id');
        }

        if ($this->siswaId === null) {
            $this->siswaId = Siswa::query()->value('siswa_id');
        }
    }

    public function pilihTab(string $tab): void
    {
        $this->tabAktif = $tab;
    }

    public function pilihMinggu(?int $minggu = null): void
    {
        $this->mingguFilter = $minggu;
    }

    public function getTitle(): string|Htmlable
    {
        if (! $this->siswaId) {
            return 'Detail siswa';
        }

        $siswa = Siswa::query()->with('pengguna')->find($this->siswaId);

        return $siswa?->pengguna?->nama_lengkap ?? 'Detail siswa';
    }

    public function getSubheading(): string|Htmlable|null
    {
        if (! $this->siswaId) {
            return null;
        }

        $siswa = Siswa::query()->with(['kelas', 'pesertaProgram.program'])->find($this->siswaId);
        if (! $siswa) {
            return null;
        }

        $pesertaAktif = $siswa->pesertaProgram->sortByDesc('tanggal_mulai')->first();
        if ($pesertaAktif) {
            $mulai = $pesertaAktif->tanggal_mulai?->translatedFormat('d M');
            $selesai = $pesertaAktif->tanggal_selesai?->translatedFormat('d M Y');

            return "Kelas {$siswa->kelas?->nama_kelas} · Program {$mulai} – {$selesai}";
        }

        return "Kelas {$siswa->kelas?->nama_kelas} · Belum terdaftar pada periode program";
    }

    public function unduhFoodRecordCsv(): StreamedResponse
    {
        $siswa = Siswa::query()->with(['pengguna', 'kelas'])->findOrFail($this->siswaId);
        $records = FoodRecordDetailView::query()
            ->where('siswa_id', $siswa->siswa_id)
            ->when($this->mingguFilter, fn ($query) => $query->where('minggu_ke', $this->mingguFilter))
            ->orderBy('tanggal')
            ->orderBy('food_record_detail_id')
            ->get();

        $namaFile = 'food-record-'.str($siswa->pengguna?->nama_lengkap ?? 'siswa')->slug().'.csv';

        return response()->streamDownload(function () use ($records): void {
            $handle = fopen('php://output', 'w');
            if ($handle === false) {
                return;
            }

            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, [
                'Tanggal',
                'Minggu Ke',
                'Waktu',
                'Tempat',
                'Jenis Makanan',
                'Bahan Makanan',
                'URT',
                'Jumlah URT',
                'Gram',
                'Keterangan',
                'Status Realisasi',
            ]);

            foreach ($records as $item) {
                fputcsv($handle, [
                    $item->tanggal?->format('Y-m-d'),
                    $item->minggu_ke,
                    $item->jam_makan ?? '-',
                    $item->tempat ?? '-',
                    $item->nama_makanan ?? ($item->tidak_sarapan ? 'Tidak sarapan' : '-'),
                    $item->bahan_teks ?? '-',
                    $item->urt ?? '-',
                    $item->jumlah_urt ?? '-',
                    $item->berat_gram ?? '-',
                    $item->keterangan ?? '-',
                    $item->status_realisasi_nama ?? '-',
                ]);
            }

            fclose($handle);
        }, $namaFile, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $siswa = $this->siswaId
            ? Siswa::query()->with([
                'pengguna',
                'kelas',
                'statusAkun',
                'pesertaProgram.program',
                'bacaMateri.materiEdukasi',
                'riwayatStatusAkun.statusAkun',
                'riwayatStatusAkun.diubahOleh',
            ])->find($this->siswaId)
            : null;

        if (! $siswa) {
            return [
                'siswa' => null,
            ];
        }

        $kepatuhanMingguan = KepatuhanMingguan::query()
            ->where('siswa_id', $siswa->siswa_id)
            ->orderBy('minggu_ke')
            ->get();

        $totalHariBerjalan = (int) $kepatuhanMingguan->sum('hari_berjalan');
        $totalHariTercatat = (int) $kepatuhanMingguan->sum('hari_tercatat');
        $totalHariSesuai = (int) $kepatuhanMingguan->sum('hari_sesuai');
        $persenKepatuhanTotal = $totalHariBerjalan > 0
            ? (int) round(($totalHariSesuai / $totalHariBerjalan) * 100)
            : 0;

        $skorPengetahuan = SkorPengetahuan::query()->where('siswa_id', $siswa->siswa_id)->first();
        $progresEdukasi = ProgresEdukasi::query()->where('siswa_id', $siswa->siswa_id)->first();

        $pesertaTerbaru = $siswa->pesertaProgram->sortByDesc('tanggal_mulai')->first();
        $jumlahMingguProgram = max(
            3,
            (int) ($pesertaTerbaru?->program?->durasi_minggu ?? 3),
            (int) ($kepatuhanMingguan->max('minggu_ke') ?? 0),
        );

        $foodRecordList = FoodRecordDetailView::query()
            ->where('siswa_id', $siswa->siswa_id)
            ->when($this->mingguFilter, fn ($query) => $query->where('minggu_ke', $this->mingguFilter))
            ->orderBy('tanggal')
            ->orderBy('food_record_detail_id')
            ->get();

        $hariBelumDicatat = KepatuhanHarian::query()
            ->where('siswa_id', $siswa->siswa_id)
            ->where('status_kepatuhan', 'BELUM_DICATAT')
            ->when($this->mingguFilter, fn ($query) => $query->where('minggu_ke', $this->mingguFilter))
            ->orderBy('tanggal')
            ->get();

        $sqffqSkorList = SqffqSkor::query()
            ->with('sqffq')
            ->where('siswa_id', $siswa->siswa_id)
            ->get();

        $sqffqRekapList = SqffqRekap::query()
            ->where('siswa_id', $siswa->siswa_id)
            ->orderBy('kelompok_kode')
            ->get();

        $hasilKuesionerList = HasilKuesioner::query()
            ->with(['kuesioner', 'jawabanKuesioner.soal', 'jawabanKuesioner.opsiJawaban'])
            ->where('siswa_id', $siswa->siswa_id)
            ->get();

        return [
            'siswa' => $siswa,
            'kepatuhanMingguan' => $kepatuhanMingguan,
            'totalHariBerjalan' => $totalHariBerjalan,
            'totalHariTercatat' => $totalHariTercatat,
            'totalHariSesuai' => $totalHariSesuai,
            'persenKepatuhanTotal' => $persenKepatuhanTotal,
            'skorPengetahuan' => $skorPengetahuan,
            'progresEdukasi' => $progresEdukasi,
            'jumlahMingguProgram' => $jumlahMingguProgram,
            'foodRecordList' => $foodRecordList,
            'hariBelumDicatat' => $hariBelumDicatat,
            'sqffqSkorList' => $sqffqSkorList,
            'sqffqRekapList' => $sqffqRekapList,
            'hasilKuesionerList' => $hasilKuesionerList,
        ];
    }
}

