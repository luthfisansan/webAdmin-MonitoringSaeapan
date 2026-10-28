<?php

namespace App\Filament\Pages;

use App\Models\AntreanVerifikasi;
use App\Models\BahanMakanan;
use App\Models\Frekuensi;
use App\Models\KelompokBahan;
use App\Models\Siswa;
use App\Models\SiswaStatus;
use App\Models\Sqffq;
use App\Models\SqffqDetail;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Url;

class VerifikasiSqffq extends Page
{
    protected static ?string $title = 'Form SQ-FFQ';

    protected static ?string $navigationLabel = 'Verifikasi SQ-FFQ';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?string $slug = 'verifikasi-sqffq';

    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.pages.verifikasi-sqffq';

    #[Url(as: 'siswa_id', except: null)]
    public ?int $siswaId = null;

    #[Url(except: 'AWAL')]
    public string $tahap = 'AWAL';

    #[Url(as: 'kelompok', except: 'A')]
    public string $kelompokKodeAktif = 'A';

    public string $tanggalWawancara = '';

    public string $catatan = '';

    /**
     * @var array<int, int|string|null>
     */
    public array $frekuensiPilihan = [];

    /**
     * @var array<int, float|string|null>
     */
    public array $porsiGram = [];

    public function mount(): void
    {
        if ($this->siswaId === null && request()->filled('siswa_id')) {
            $this->siswaId = request()->integer('siswa_id');
        }

        if ($this->siswaId === null) {
            $antreanPertama = AntreanVerifikasi::query()->value('siswa_id');
            if ($antreanPertama) {
                $this->siswaId = (int) $antreanPertama;
            }
        }

        $this->tanggalWawancara = now()->toDateString();

        if ($this->siswaId !== null) {
            $this->muatDataSqffqSiswa();
        }
    }

    public function pilihSiswa(int $siswaId): void
    {
        $this->siswaId = $siswaId;
        $this->kelompokKodeAktif = 'A';
        $this->muatDataSqffqSiswa();
    }

    public function gantiSiswa(): void
    {
        $this->siswaId = null;
        $this->frekuensiPilihan = [];
        $this->porsiGram = [];
    }

    public function pilihKelompok(string $kode): void
    {
        $this->kelompokKodeAktif = $kode;
    }

    public function muatDataSqffqSiswa(): void
    {
        $this->frekuensiPilihan = [];
        $this->porsiGram = [];
        $this->tanggalWawancara = now()->toDateString();
        $this->catatan = '';

        if (! $this->siswaId) {
            return;
        }

        $sqffq = Sqffq::query()
            ->with('sqffqDetail')
            ->where('siswa_id', $this->siswaId)
            ->where('tahap', $this->tahap)
            ->first();

        if (! $sqffq) {
            return;
        }

        $this->tanggalWawancara = $sqffq->tanggal_wawancara?->toDateString() ?? now()->toDateString();
        $this->catatan = (string) ($sqffq->catatan ?? '');

        foreach ($sqffq->sqffqDetail as $detail) {
            $this->frekuensiPilihan[(int) $detail->bahan_makanan_id] = (int) $detail->frekuensi_id;
            $this->porsiGram[(int) $detail->bahan_makanan_id] = $detail->porsi_gram !== null
                ? (string) (float) $detail->porsi_gram
                : null;
        }
    }

    public function getSubheading(): string|Htmlable|null
    {
        if (! $this->siswaId) {
            return 'Pilih responden siswa untuk memulai atau melanjutkan wawancara SQ-FFQ';
        }

        $siswa = Siswa::query()->with(['pengguna', 'kelas'])->find($this->siswaId);
        if (! $siswa) {
            return 'Pilih responden siswa untuk memulai atau melanjutkan wawancara SQ-FFQ';
        }

        return "Responden: {$siswa->pengguna?->nama_lengkap} · Kelas {$siswa->kelas?->nama_kelas} · diisi petugas lewat wawancara";
    }

    public function simpanDraf(): void
    {
        if (! $this->siswaId) {
            return;
        }

        $this->simpanKeDatabase(selesaikan: false);

        Notification::make()
            ->success()
            ->title('Draf SQ-FFQ disimpan')
            ->body('Data wawancara SQ-FFQ berhasil disimpan sebagai draf.')
            ->send();
    }

    public function selesaikanSqffq(): void
    {
        if (! $this->siswaId) {
            return;
        }

        $terisiCount = collect($this->frekuensiPilihan)
            ->filter(fn ($val) => filled($val))
            ->count();

        if ($terisiCount === 0) {
            Notification::make()
                ->warning()
                ->title('Belum ada item yang diisi')
                ->body('Silakan isi frekuensi konsumsi minimal satu bahan makanan sebelum menyelesaikan SQ-FFQ.')
                ->send();

            return;
        }

        $this->simpanKeDatabase(selesaikan: true);

        Notification::make()
            ->success()
            ->title('SQ-FFQ selesai & akun siswa diverifikasi')
            ->body('SQ-FFQ tahap '.$this->tahap.' telah diselesaikan. Status akun siswa diperbarui oleh database.')
            ->send();
    }

    protected function simpanKeDatabase(bool $selesaikan): void
    {
        DB::transaction(function () use ($selesaikan): void {
            $petugasId = (int) Auth::id();
            $tahapValid = in_array($this->tahap, ['AWAL', 'AKHIR'], true) ? $this->tahap : 'AWAL';

            $sqffq = Sqffq::query()
                ->where('siswa_id', $this->siswaId)
                ->where('tahap', $tahapValid)
                ->first();

            if (! $sqffq) {
                $sqffq = Sqffq::query()->create([
                    'siswa_id' => $this->siswaId,
                    'petugas_pengguna_id' => $petugasId,
                    'tahap' => $tahapValid,
                    'status' => 'DRAF',
                    'tanggal_wawancara' => $this->tanggalWawancara ?: now()->toDateString(),
                    'catatan' => $this->catatan !== '' ? $this->catatan : null,
                ]);
            } else {
                $sqffq->update([
                    'petugas_pengguna_id' => $petugasId,
                    'tanggal_wawancara' => $this->tanggalWawancara ?: now()->toDateString(),
                    'catatan' => $this->catatan !== '' ? $this->catatan : null,
                ]);
            }

            foreach ($this->frekuensiPilihan as $bahanMakananId => $frekuensiId) {
                $bahanId = (int) $bahanMakananId;

                if (! filled($frekuensiId)) {
                    SqffqDetail::query()
                        ->where('sqffq_id', $sqffq->sqffq_id)
                        ->where('bahan_makanan_id', $bahanId)
                        ->delete();

                    continue;
                }

                $nilaiPorsi = $this->porsiGram[$bahanId] ?? null;
                $porsiNumerik = (is_numeric($nilaiPorsi) && (float) $nilaiPorsi >= 0)
                    ? round((float) $nilaiPorsi, 2)
                    : null;

                SqffqDetail::query()->updateOrCreate(
                    [
                        'sqffq_id' => $sqffq->sqffq_id,
                        'bahan_makanan_id' => $bahanId,
                    ],
                    [
                        'frekuensi_id' => (int) $frekuensiId,
                        'porsi_gram' => $porsiNumerik,
                    ],
                );
            }

            if ($selesaikan) {
                // Trigger trg_sqffq_selesai_fn di PostgreSQL berjalan saat UPDATE status = 'SELESAI'
                $sqffq->update([
                    'status' => 'SELESAI',
                    'selesai_pada' => now(),
                ]);
            }
        });
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $antreanList = AntreanVerifikasi::query()->get();
        $semuaSiswaList = SiswaStatus::query()->orderBy('nama_lengkap')->get();

        $siswaAktif = $this->siswaId
            ? Siswa::query()->with(['pengguna', 'kelas', 'statusAkun'])->find($this->siswaId)
            : null;

        $kelompokList = KelompokBahan::query()
            ->with(['bahanMakanan' => fn ($query) => $query->where('aktif', true)->orderBy('urutan')])
            ->orderBy('urutan')
            ->get();

        $frekuensiList = Frekuensi::query()->orderBy('urutan')->get();

        $kelompokAktif = $kelompokList->firstWhere('kode', $this->kelompokKodeAktif)
            ?? $kelompokList->first();

        $indeksKelompokAktif = $kelompokList->search(fn (KelompokBahan $item): bool => $item->kode === $kelompokAktif?->kode);
        $kelompokSebelumnya = ($indeksKelompokAktif !== false && $indeksKelompokAktif > 0)
            ? $kelompokList->get($indeksKelompokAktif - 1)
            : null;
        $kelompokBerikutnya = ($indeksKelompokAktif !== false && $indeksKelompokAktif < $kelompokList->count() - 1)
            ? $kelompokList->get($indeksKelompokAktif + 1)
            : null;

        $totalBahanAktif = BahanMakanan::query()->where('aktif', true)->count();
        $totalTerisi = collect($this->frekuensiPilihan)
            ->filter(fn ($val): bool => filled($val))
            ->count();

        $progresPerKelompok = [];
        foreach ($kelompokList as $kelompok) {
            $bahanIds = $kelompok->bahanMakanan->pluck('bahan_makanan_id');
            $terisi = $bahanIds->filter(fn ($id): bool => filled($this->frekuensiPilihan[(int) $id] ?? null))->count();
            $progresPerKelompok[$kelompok->kode] = [
                'terisi' => $terisi,
                'total' => $bahanIds->count(),
            ];
        }

        return [
            'antreanList' => $antreanList,
            'semuaSiswaList' => $semuaSiswaList,
            'siswaAktif' => $siswaAktif,
            'kelompokList' => $kelompokList,
            'frekuensiList' => $frekuensiList,
            'kelompokAktif' => $kelompokAktif,
            'kelompokSebelumnya' => $kelompokSebelumnya,
            'kelompokBerikutnya' => $kelompokBerikutnya,
            'totalBahanAktif' => $totalBahanAktif,
            'totalTerisi' => $totalTerisi,
            'progresPerKelompok' => $progresPerKelompok,
        ];
    }
}

