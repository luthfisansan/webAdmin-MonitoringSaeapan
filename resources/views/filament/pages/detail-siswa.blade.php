<x-filament-panels::page>
    <div class="dbp-overview-stack">
        @if (! $siswa)
            <div class="dbp-panel-card">
                <p class="dbp-empty-cell">Data siswa tidak ditemukan.</p>
                <div style="text-align: center;">
                    <a href="{{ \App\Filament\Pages\DataSiswa::getUrl() }}" class="dbp-btn-outline">← Kembali ke Data siswa</a>
                </div>
            </div>
        @else
            {{-- Baris Tab Navigasi + Tombol Unduh Food Record mengikuti D Detail siswa.png --}}
            <div class="dbp-page-toolbar">
                <div class="dbp-tabs-bar">
                    <button
                        type="button"
                        wire:click="pilihTab('ringkasan')"
                        class="dbp-tab-btn {{ $tabAktif === 'ringkasan' ? 'dbp-tab-btn-active' : '' }}"
                    >
                        Ringkasan
                    </button>

                    <button
                        type="button"
                        wire:click="pilihTab('sqffq')"
                        class="dbp-tab-btn {{ $tabAktif === 'sqffq' ? 'dbp-tab-btn-active' : '' }}"
                    >
                        SQ-FFQ
                    </button>

                    <button
                        type="button"
                        wire:click="pilihTab('food_record')"
                        class="dbp-tab-btn {{ $tabAktif === 'food_record' ? 'dbp-tab-btn-active' : '' }}"
                    >
                        Food record
                    </button>

                    <button
                        type="button"
                        wire:click="pilihTab('pengetahuan')"
                        class="dbp-tab-btn {{ $tabAktif === 'pengetahuan' ? 'dbp-tab-btn-active' : '' }}"
                    >
                        Pengetahuan
                    </button>
                </div>

                <div class="dbp-toolbar-right">
                    <a href="{{ \App\Filament\Pages\DataSiswa::getUrl() }}" class="dbp-btn-outline dbp-btn-sm">
                        ← Data siswa
                    </a>

                    <button
                        type="button"
                        wire:click="unduhFoodRecordCsv"
                        class="dbp-btn-outline"
                    >
                        ↓ Unduh food record
                    </button>
                </div>
            </div>

            {{-- 4 Kartu Ringkasan Atas mengikuti D Detail siswa.png --}}
            <div class="dbp-stats-grid">
                <div class="dbp-stat-card">
                    <div class="dbp-stat-label">Kepatuhan rencana</div>
                    <div class="dbp-stat-value">{{ $persenKepatuhanTotal }}%</div>
                    <div class="dbp-stat-meta">{{ $totalHariSesuai }} dari {{ $totalHariBerjalan }} hari sesuai</div>
                </div>

                <div class="dbp-stat-card">
                    <div class="dbp-stat-label">Hari tercatat</div>
                    <div class="dbp-stat-value">{{ $totalHariTercatat }} / {{ $totalHariBerjalan }}</div>
                    <div class="dbp-stat-meta">hari berjalan pada program</div>
                </div>

                <div class="dbp-stat-card">
                    <div class="dbp-stat-label">Pretest</div>
                    <div class="dbp-stat-value">
                        {{ $skorPengetahuan?->skor_pretest !== null ? (int) round((float) $skorPengetahuan->skor_pretest) : '—' }}
                    </div>
                    <div class="dbp-stat-meta">
                        @if ($skorPengetahuan?->skor_posttest !== null)
                            post test: {{ (int) round((float) $skorPengetahuan->skor_posttest) }}
                        @else
                            post test dibuka setelah minggu 3
                        @endif
                    </div>
                </div>

                <div class="dbp-stat-card">
                    <div class="dbp-stat-label">Status akun</div>
                    <div class="dbp-stat-value" style="font-size: 1.55rem;">{{ $siswa->statusAkun?->nama }}</div>
                    <div class="dbp-stat-meta">
                        @if ($siswa->terverifikasi_pada)
                            setelah SQ-FFQ diisi {{ $siswa->terverifikasi_pada->translatedFormat('d M Y') }}
                        @else
                            status akun saat ini
                        @endif
                    </div>
                </div>
            </div>

            {{-- TAB 1: FOOD RECORD (Default mengikuti D Detail siswa.png) --}}
            @if ($tabAktif === 'food_record')
                <div class="dbp-panel-card">
                    <div class="dbp-panel-header">
                        <h3 class="dbp-panel-title">Food record sarapan: rencana vs realisasi</h3>

                        <div class="dbp-filter-pills">
                            <button
                                type="button"
                                wire:click="pilihMinggu(null)"
                                class="dbp-filter-chip {{ $mingguFilter === null ? 'dbp-filter-chip-active' : '' }}"
                            >
                                Semua
                            </button>

                            @for ($m = 1; $m <= $jumlahMingguProgram; $m++)
                                <button
                                    type="button"
                                    wire:click="pilihMinggu({{ $m }})"
                                    class="dbp-filter-chip {{ $mingguFilter === $m ? 'dbp-filter-chip-active' : '' }}"
                                >
                                    Minggu {{ $m }}
                                </button>
                            @endfor
                        </div>
                    </div>

                    <div class="dbp-table-scroll">
                        <table class="dbp-custom-table">
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Waktu</th>
                                    <th>Tempat</th>
                                    <th>Jenis makanan</th>
                                    <th>Bahan makanan</th>
                                    <th>URT</th>
                                    <th>Gram</th>
                                    <th>Keterangan</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($foodRecordList as $item)
                                    <tr>
                                        <td>{{ $item->tanggal?->translatedFormat('d M') }}</td>
                                        <td>{{ $item->jam_makan ? substr((string) $item->jam_makan, 0, 5) : '—' }}</td>
                                        <td>{{ $item->tempat ?? '—' }}</td>
                                        <td>{{ $item->tidak_sarapan ? 'Tidak sarapan' : ($item->nama_makanan ?? '—') }}</td>
                                        <td class="font-bold">{{ $item->bahan_teks ?? '—' }}</td>
                                        <td>
                                            @if ($item->jumlah_urt !== null && $item->urt)
                                                {{ (float) $item->jumlah_urt }} {{ $item->urt }}
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td>{{ $item->berat_gram !== null ? (float) $item->berat_gram : '—' }}</td>
                                        <td>{{ $item->keterangan ?? '—' }}</td>
                                        <td>
                                            @if ($item->status_realisasi_kode === 'SESUAI')
                                                <span class="dbp-pill dbp-pill-success">Sesuai</span>
                                            @elseif ($item->status_realisasi_kode === 'DIUBAH')
                                                <span class="dbp-pill dbp-pill-warning">Diubah</span>
                                            @elseif ($item->status_realisasi_kode === 'TIDAK_DIMAKAN')
                                                <span class="dbp-pill dbp-pill-warning">Tidak sesuai</span>
                                            @elseif ($item->status_realisasi_kode === 'TAMBAHAN')
                                                <span class="dbp-pill dbp-pill-neutral">Tambahan</span>
                                            @else
                                                <span class="dbp-pill dbp-pill-neutral">{{ $item->status_realisasi_nama ?? 'Belum dicatat' }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    @forelse ($hariBelumDicatat as $hariKosong)
                                        <tr>
                                            <td>{{ $hariKosong->tanggal?->translatedFormat('d M') }}</td>
                                            <td>—</td>
                                            <td>—</td>
                                            <td>—</td>
                                            <td>—</td>
                                            <td>—</td>
                                            <td>—</td>
                                            <td>—</td>
                                            <td><span class="dbp-pill dbp-pill-neutral">Belum dicatat</span></td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="9" class="dbp-empty-cell">
                                                Belum ada catatan food record untuk siswa ini.
                                            </td>
                                        </tr>
                                    @endforelse
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            {{-- TAB 2: RINGKASAN (Progres Edukasi, Kepatuhan Mingguan & Riwayat Status) --}}
            @if ($tabAktif === 'ringkasan')
                <div class="dbp-analytics-grid">
                    <div class="dbp-panel-card">
                        <div class="dbp-panel-header">
                            <h3 class="dbp-panel-title">Progres Materi Edukasi Wajib</h3>
                            <span class="dbp-pill {{ $progresEdukasi?->selesai ? 'dbp-pill-success' : 'dbp-pill-warning' }}">
                                {{ $progresEdukasi?->dibaca ?? 0 }} / {{ $progresEdukasi?->total_wajib ?? 0 }} terbaca
                            </span>
                        </div>

                        <div class="dbp-table-scroll">
                            <table class="dbp-custom-table">
                                <thead>
                                    <tr>
                                        <th>Judul materi</th>
                                        <th>Status</th>
                                        <th>Selesai pada</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($siswa->bacaMateri as $baca)
                                        <tr>
                                            <td class="font-bold">{{ $baca->materiEdukasi?->judul ?? '-' }}</td>
                                            <td>
                                                <span class="dbp-pill {{ $baca->selesai ? 'dbp-pill-success' : 'dbp-pill-warning' }}">
                                                    {{ $baca->selesai ? 'Selesai' : 'Sedang dibaca' }}
                                                </span>
                                            </td>
                                            <td>{{ $baca->selesai_pada?->translatedFormat('d M Y H:i') ?? '—' }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3" class="dbp-empty-cell">Belum ada materi edukasi yang dibaca.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="dbp-panel-card">
                        <div class="dbp-panel-header">
                            <h3 class="dbp-panel-title">Kepatuhan per Minggu</h3>
                        </div>

                        <div class="dbp-table-scroll">
                            <table class="dbp-custom-table">
                                <thead>
                                    <tr>
                                        <th>Minggu</th>
                                        <th>Hari tercatat</th>
                                        <th>Hari sesuai</th>
                                        <th>Kepatuhan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($kepatuhanMingguan as $km)
                                        <tr>
                                            <td class="font-bold">Minggu {{ $km->minggu_ke }}</td>
                                            <td>{{ $km->hari_tercatat }} / {{ $km->hari_berjalan }}</td>
                                            <td>{{ $km->hari_sesuai }}</td>
                                            <td><span class="dbp-pill dbp-pill-success">{{ $km->persen_kepatuhan }}%</span></td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="dbp-empty-cell">Belum ada data kepatuhan mingguan.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif

            {{-- TAB 3: SQ-FFQ --}}
            @if ($tabAktif === 'sqffq')
                <div class="dbp-panel-card">
                    <div class="dbp-panel-header">
                        <div>
                            <h3 class="dbp-panel-title">Rekap Skor Konsumsi Pangan (SQ-FFQ)</h3>
                            <p class="dbp-panel-subtitle">Ringkasan jumlah item terisi dan total skor konsumsi pangan per kelompok bahan makanan.</p>
                        </div>

                        <a
                            href="{{ \App\Filament\Pages\VerifikasiSqffq::getUrl(['siswa_id' => $siswa->siswa_id]) }}"
                            class="dbp-btn-primary dbp-btn-sm"
                        >
                            Buka Form SQ-FFQ
                        </a>
                    </div>

                    <div class="dbp-table-scroll">
                        <table class="dbp-custom-table">
                            <thead>
                                <tr>
                                    <th>Kode</th>
                                    <th>Kelompok bahan makanan</th>
                                    <th>Bahan terisi</th>
                                    <th>Skor konsumsi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($sqffqRekapList as $rekap)
                                    <tr>
                                        <td><span class="dbp-pill dbp-pill-success">{{ $rekap->kelompok_kode }}</span></td>
                                        <td class="font-bold">{{ $rekap->kelompok_nama }}</td>
                                        <td>{{ $rekap->jumlah_terisi }} / {{ $rekap->jumlah_bahan }} item</td>
                                        <td class="font-bold">{{ (float) ($rekap->total_skor ?? 0) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="dbp-empty-cell">Wawancara SQ-FFQ belum dilakukan untuk siswa ini.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            {{-- TAB 4: PENGETAHUAN (Pretest & Post test) --}}
            @if ($tabAktif === 'pengetahuan')
                <div class="dbp-panel-card">
                    <div class="dbp-panel-header">
                        <h3 class="dbp-panel-title">Hasil Pretest & Post test Pengetahuan Gizi</h3>
                    </div>

                    <div class="dbp-table-scroll">
                        <table class="dbp-custom-table">
                            <thead>
                                <tr>
                                    <th>Jenis kuesioner</th>
                                    <th>Judul</th>
                                    <th>Benar / Soal</th>
                                    <th>Skor (0-100)</th>
                                    <th>Selesai pada</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($hasilKuesionerList as $hasil)
                                    <tr>
                                        <td>
                                            <span class="dbp-pill {{ $hasil->kuesioner?->jenis === 'PRETEST' ? 'dbp-pill-warning' : 'dbp-pill-success' }}">
                                                {{ $hasil->kuesioner?->jenis }}
                                            </span>
                                        </td>
                                        <td class="font-bold">{{ $hasil->kuesioner?->judul }}</td>
                                        <td>{{ $hasil->jumlah_benar ?? 0 }} / {{ $hasil->jumlah_soal ?? 0 }}</td>
                                        <td class="font-bold">{{ $hasil->skor !== null ? (float) $hasil->skor : '—' }}</td>
                                        <td>{{ $hasil->selesai_pada?->translatedFormat('d M Y H:i') ?? 'Belum selesai' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="dbp-empty-cell">Siswa belum mengerjakan kuesioner pretest maupun post test.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        @endif
    </div>
</x-filament-panels::page>

