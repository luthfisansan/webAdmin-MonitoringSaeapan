<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FoodRecordDetailView;
use App\Models\HasilKuesioner;
use App\Models\Kelas;
use App\Models\KelompokBahan;
use App\Models\KepatuhanMingguan;
use App\Models\LaporanKelas;
use App\Models\PolaKonsumsiKelompok;
use App\Models\Sekolah;
use App\Models\Siswa;
use App\Models\SkorPengetahuan;
use App\Models\SqffqRekap;
use App\Models\SqffqSkor;
use App\Models\TempatMakan;
use App\Models\TempatSarapan;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class LaporanCetakController extends Controller
{
    public function cetakLaporan(Request $request): View
    {
        $user = $request->user();
        if (! $user || ! in_array($user->peran?->kode, ['ADMIN', 'PETUGAS'], true)) {
            abort(403, 'Akses khusus administrator dan petugas gizi.');
        }

        $sekolahId = $request->filled('sekolah_id') ? $request->integer('sekolah_id') : null;
        $sekolahTerpilih = $sekolahId ? Sekolah::query()->find($sekolahId) : null;

        $kelasId = $request->filled('kelas_id') ? $request->integer('kelas_id') : null;
        $kelasTerpilih = $kelasId ? Kelas::query()->with('sekolah')->find($kelasId) : null;

        $laporanKelas = LaporanKelas::query()
            ->when($sekolahId, fn ($query) => $query->where('sekolah_id', $sekolahId))
            ->when($kelasId, fn ($query) => $query->where('kelas_id', $kelasId))
            ->orderBy('nama_kelas')
            ->get();

        $kelompokMaster = KelompokBahan::query()->orderBy('urutan')->get();
        $polaRows = PolaKonsumsiKelompok::query()
            ->when($sekolahId, fn ($query) => $query->where('sekolah_id', $sekolahId))
            ->when($kelasId, fn ($query) => $query->where('kelas_id', $kelasId))
            ->get()
            ->groupBy('kelompok_bahan_id');

        $polaKonsumsiList = $kelompokMaster->map(function (KelompokBahan $kelompok) use ($polaRows): array {
            $rows = $polaRows->get($kelompok->kelompok_bahan_id, collect());
            $totalResponden = (int) $rows->sum('jumlah_responden');
            $totalSering = (int) $rows->sum('jumlah_sering');
            $persen = $totalResponden > 0
                ? (int) round(($totalSering / $totalResponden) * 100)
                : 0;

            return [
                'kode' => $kelompok->kode,
                'nama' => $kelompok->nama,
                'total_responden' => $totalResponden,
                'jumlah_sering' => $totalSering,
                'persen' => $persen,
            ];
        });

        $tempatMaster = TempatMakan::query()->orderBy('tempat_makan_id')->get();
        $tempatRows = TempatSarapan::query()
            ->when($sekolahId, fn ($query) => $query->where('sekolah_id', $sekolahId))
            ->when($kelasId, fn ($query) => $query->where('kelas_id', $kelasId))
            ->get();

        $totalCatatanTempat = (int) $tempatRows->sum('jumlah_catatan');
        $tempatGrouped = $tempatRows->groupBy('tempat_makan_id');

        $tempatSarapanList = $tempatMaster->map(function (TempatMakan $tempat) use ($tempatGrouped, $totalCatatanTempat): array {
            $rows = $tempatGrouped->get($tempat->tempat_makan_id, collect());
            $jml = (int) $rows->sum('jumlah_catatan');
            $persen = $totalCatatanTempat > 0
                ? (int) round(($jml / $totalCatatanTempat) * 100)
                : 0;

            return [
                'nama' => $tempat->nama,
                'jumlah' => $jml,
                'persen' => $persen,
            ];
        });

        $sqffqSkorList = SqffqSkor::query()
            ->with(['siswa.pengguna', 'siswa.kelas.sekolah', 'sqffq'])
            ->when($sekolahId, fn ($query) => $query->whereHas('siswa.kelas', fn ($kq) => $kq->where('sekolah_id', $sekolahId)))
            ->when($kelasId, fn ($query) => $query->whereHas('siswa', fn ($sq) => $sq->where('kelas_id', $kelasId)))
            ->get();

        $totalSiswaTerdata = (int) $laporanKelas->sum('jumlah_siswa');

        return view('laporan.cetak', [
            'user' => $user,
            'sekolahId' => $sekolahId,
            'sekolahTerpilih' => $sekolahTerpilih,
            'kelasId' => $kelasId,
            'kelasTerpilih' => $kelasTerpilih,
            'laporanKelas' => $laporanKelas,
            'polaKonsumsiList' => $polaKonsumsiList,
            'tempatSarapanList' => $tempatSarapanList,
            'sqffqSkorList' => $sqffqSkorList,
            'totalSiswaTerdata' => $totalSiswaTerdata,
            'tanggalCetak' => Carbon::now()->translatedFormat('d F Y, H:i'),
        ]);
    }

    public function cetakSiswa(Request $request): View
    {
        $user = $request->user();
        if (! $user || ! in_array($user->peran?->kode, ['ADMIN', 'PETUGAS'], true)) {
            abort(403, 'Akses khusus administrator dan petugas gizi.');
        }

        $siswaId = $request->integer('siswa_id');
        $mingguFilter = $request->filled('minggu') ? $request->integer('minggu') : null;

        $siswa = Siswa::query()
            ->with(['pengguna', 'kelas.sekolah', 'statusAkun', 'pesertaProgram.program'])
            ->findOrFail($siswaId);

        $pesertaAktif = $siswa->pesertaProgram->sortByDesc('tanggal_mulai')->first();

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

        $foodRecordList = FoodRecordDetailView::query()
            ->where('siswa_id', $siswa->siswa_id)
            ->when($mingguFilter, fn ($query) => $query->where('minggu_ke', $mingguFilter))
            ->orderBy('tanggal')
            ->orderBy('food_record_detail_id')
            ->get();

        $sqffqRekapList = SqffqRekap::query()
            ->where('siswa_id', $siswa->siswa_id)
            ->orderBy('kelompok_kode')
            ->get();

        $hasilKuesionerList = HasilKuesioner::query()
            ->with('kuesioner')
            ->where('siswa_id', $siswa->siswa_id)
            ->get();

        return view('laporan.cetak-siswa', [
            'user' => $user,
            'siswa' => $siswa,
            'pesertaAktif' => $pesertaAktif,
            'mingguFilter' => $mingguFilter,
            'kepatuhanMingguan' => $kepatuhanMingguan,
            'totalHariBerjalan' => $totalHariBerjalan,
            'totalHariTercatat' => $totalHariTercatat,
            'totalHariSesuai' => $totalHariSesuai,
            'persenKepatuhanTotal' => $persenKepatuhanTotal,
            'skorPengetahuan' => $skorPengetahuan,
            'foodRecordList' => $foodRecordList,
            'sqffqRekapList' => $sqffqRekapList,
            'hasilKuesionerList' => $hasilKuesionerList,
            'tanggalCetak' => Carbon::now()->translatedFormat('d F Y, H:i'),
        ]);
    }
}
