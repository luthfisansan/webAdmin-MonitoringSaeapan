<?php

namespace App\Filament\Pages;

use App\Models\Kelas;
use App\Models\Pengguna;
use App\Models\Peran;
use App\Models\Siswa;
use App\Models\SiswaStatus;
use App\Models\StatusAkun;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Url;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DataSiswa extends Page
{
    protected static ?string $title = 'Data siswa';

    protected static ?string $navigationLabel = 'Data siswa';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static ?string $slug = 'data-siswa';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.data-siswa';

    #[Url(except: '')]
    public string $cari = '';

    #[Url(except: null)]
    public ?int $kelasId = null;

    #[Url(except: '')]
    public string $statusFilter = '';

    public bool $tampilkanModalTambah = false;

    public string $namaLengkapBaru = '';

    public string $emailBaru = '';

    public string $kataSandiBaru = '';

    public ?int $kelasIdBaru = null;

    public ?string $jenisKelaminBaru = null;

    public ?string $tanggalLahirBaru = null;

    public function getSubheading(): string|Htmlable|null
    {
        $totalSiswa = SiswaStatus::query()->count();
        $menungguVerifikasi = SiswaStatus::query()->where('status_akun_kode', 'MENUNGGU_VERIFIKASI')->count();

        return "{$totalSiswa} siswa · {$menungguVerifikasi} menunggu verifikasi";
    }

    public function pilihKelas(?int $kelasId = null): void
    {
        $this->kelasId = $kelasId;
    }

    public function toggleAntreanVerifikasi(): void
    {
        $this->statusFilter = $this->statusFilter === 'MENUNGGU_VERIFIKASI' ? '' : 'MENUNGGU_VERIFIKASI';
    }

    public function bukaModalTambah(): void
    {
        $this->resetValidation();
        $this->namaLengkapBaru = '';
        $this->emailBaru = '';
        $this->kataSandiBaru = '';
        $this->kelasIdBaru = Kelas::query()->where('aktif', true)->value('kelas_id');
        $this->jenisKelaminBaru = null;
        $this->tanggalLahirBaru = null;
        $this->tampilkanModalTambah = true;
    }

    public function tutupModalTambah(): void
    {
        $this->tampilkanModalTambah = false;
    }

    public function simpanSiswaBaru(): void
    {
        $validated = $this->validate([
            'namaLengkapBaru' => ['required', 'string', 'max:120'],
            'emailBaru' => ['required', 'email', 'max:150', 'unique:pengguna_m,email'],
            'kataSandiBaru' => ['required', 'string', 'min:6'],
            'kelasIdBaru' => ['required', 'integer', 'exists:kelas_m,kelas_id'],
            'jenisKelaminBaru' => ['nullable', 'in:L,P'],
            'tanggalLahirBaru' => ['nullable', 'date'],
        ]);

        DB::transaction(function () use ($validated): void {
            $peranSiswa = Peran::query()->where('kode', 'SISWA')->firstOrFail();
            $statusPretest = StatusAkun::query()->where('kode', 'PRETEST')->firstOrFail();

            $pengguna = Pengguna::query()->create([
                'peran_id' => $peranSiswa->peran_id,
                'email' => strtolower(trim($validated['emailBaru'])),
                'kata_sandi_hash' => Hash::make($validated['kataSandiBaru']),
                'nama_lengkap' => trim($validated['namaLengkapBaru']),
                'aktif' => true,
            ]);

            Siswa::query()->create([
                'pengguna_id' => $pengguna->pengguna_id,
                'kelas_id' => $validated['kelasIdBaru'],
                'status_akun_id' => $statusPretest->status_akun_id,
                'jenis_kelamin' => $validated['jenisKelaminBaru'] ?: null,
                'tanggal_lahir' => $validated['tanggalLahirBaru'] ?: null,
            ]);
        });

        $this->tampilkanModalTambah = false;

        Notification::make()
            ->success()
            ->title('Siswa berhasil ditambahkan')
            ->body('Akun siswa baru telah dibuat dengan status awal Belum Pretest.')
            ->send();
    }

    public function unduhDaftarCsv(): StreamedResponse
    {
        $daftarSiswa = SiswaStatus::query()
            ->when($this->kelasId, fn ($query) => $query->where('kelas_id', $this->kelasId))
            ->when($this->statusFilter !== '', fn ($query) => $query->where('status_akun_kode', $this->statusFilter))
            ->when($this->cari !== '', fn ($query) => $query->where('nama_lengkap', 'ilike', "%{$this->cari}%"))
            ->orderBy('nama_lengkap')
            ->get();

        return response()->streamDownload(function () use ($daftarSiswa): void {
            $handle = fopen('php://output', 'w');
            if ($handle === false) {
                return;
            }

            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, [
                'ID Siswa',
                'Nama Siswa',
                'Email',
                'Kelas',
                'Pretest',
                'Progres Edukasi',
                'Status SQ-FFQ',
                'Kepatuhan Rencana (%)',
                'Status Akun',
            ]);

            foreach ($daftarSiswa as $baris) {
                fputcsv($handle, [
                    $baris->siswa_id,
                    $baris->nama_lengkap,
                    $baris->email,
                    $baris->nama_kelas,
                    $baris->pretest_selesai ? 'Selesai' : 'Belum',
                    "{$baris->materi_wajib_dibaca}/{$baris->materi_wajib_total}",
                    $baris->sqffq_status,
                    $baris->persen_kepatuhan !== null ? "{$baris->persen_kepatuhan}%" : '-',
                    $baris->status_akun_nama,
                ]);
            }

            fclose($handle);
        }, 'daftar-siswa-breakfast-planner.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $daftarKelas = Kelas::query()->where('aktif', true)->orderBy('nama_kelas')->get();
        $menungguVerifikasiCount = SiswaStatus::query()->where('status_akun_kode', 'MENUNGGU_VERIFIKASI')->count();

        $daftarSiswa = SiswaStatus::query()
            ->when($this->kelasId, fn ($query) => $query->where('kelas_id', $this->kelasId))
            ->when($this->statusFilter !== '', fn ($query) => $query->where('status_akun_kode', $this->statusFilter))
            ->when($this->cari !== '', function ($query): void {
                $kataKunci = trim($this->cari);
                $query->where(function ($subQuery) use ($kataKunci): void {
                    $subQuery->where('nama_lengkap', 'ilike', "%{$kataKunci}%")
                        ->orWhere('email', 'ilike', "%{$kataKunci}%");
                });
            })
            ->orderBy('nama_lengkap')
            ->get();

        return [
            'daftarKelas' => $daftarKelas,
            'daftarSiswa' => $daftarSiswa,
            'menungguVerifikasiCount' => $menungguVerifikasiCount,
        ];
    }
}

