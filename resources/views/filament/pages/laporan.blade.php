<x-filament-panels::page>
    <div class="dbp-overview-stack">
        {{-- Baris Filter & Tombol Ekspor mengikuti E Laporan.png --}}
        <div class="dbp-page-toolbar">
            <div class="dbp-toolbar-left">
                <label class="dbp-class-filter-pill" title="Filter kelas">
                    <select wire:model.live="kelasId" class="dbp-filter-select" aria-label="Filter kelas laporan">
                        <option value="">Semua kelas</option>
                        @foreach ($daftarKelas as $kelasOpt)
                            <option value="{{ $kelasOpt->kelas_id }}">{{ $kelasOpt->nama_kelas }}</option>
                        @endforeach
                    </select>
                    <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="m5.5 7.5 4.5 4.5 4.5-4.5" />
                    </svg>
                </label>
            </div>

            <div class="dbp-toolbar-right">
                <button
                    type="button"
                    wire:click="unduhExcelCsv"
                    class="dbp-btn-outline"
                >
                    ↓ Unduh Excel (CSV)
                </button>

                <button
                    type="button"
                    onclick="window.print()"
                    class="dbp-btn-primary"
                >
                    ↓ Cetak / PDF
                </button>
            </div>
        </div>

        {{-- Grid 2x2 mengikuti E Laporan.png --}}
        <div class="dbp-report-grid">
            <div class="dbp-overview-stack">
                {{-- Kartu 1: Pengetahuan gizi: pretest vs post test --}}
                <div class="dbp-panel-card">
                    <div class="dbp-panel-header">
                        <h3 class="dbp-panel-title">1. Pengetahuan gizi: pretest vs post test</h3>
                        <span class="dbp-pill dbp-pill-neutral">Per kelas</span>
                    </div>

                    <div class="dbp-table-scroll">
                        <table class="dbp-custom-table">
                            <thead>
                                <tr>
                                    <th>Kelas</th>
                                    <th>Siswa</th>
                                    <th>Rata-rata pretest</th>
                                    <th>Rata-rata post test</th>
                                    <th>Selisih</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($laporanKelasList as $row)
                                    <tr>
                                        <td class="font-bold">{{ $row->nama_kelas }}</td>
                                        <td>{{ $row->jumlah_siswa }}</td>
                                        <td>{{ $row->rata_pretest !== null ? (int) round((float) $row->rata_pretest) : '0' }}</td>
                                        <td>{{ $row->rata_posttest !== null ? (int) round((float) $row->rata_posttest) : '0' }}</td>
                                        <td>
                                            @php
                                                $selisih = (int) round((float) ($row->rata_selisih ?? 0));
                                            @endphp
                                            {{ $selisih >= 0 ? '+'.$selisih : $selisih }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="dbp-empty-cell">Belum ada data kelas.</td>
                                    </tr>
                                @endforelse

                                @if ($laporanKelasList->count() > 1)
                                    @php
                                        $totalSiswaAll = (int) $laporanKelasList->sum('jumlah_siswa');
                                        $avgPreAll = (int) round((float) ($laporanKelasList->avg('rata_pretest') ?? 0));
                                        $avgPostAll = (int) round((float) ($laporanKelasList->avg('rata_posttest') ?? 0));
                                        $avgDiffAll = $avgPostAll - $avgPreAll;
                                    @endphp
                                    <tr class="font-bold">
                                        <td>Semua kelas</td>
                                        <td>{{ $totalSiswaAll }}</td>
                                        <td>{{ $avgPreAll }}</td>
                                        <td>{{ $avgPostAll }}</td>
                                        <td>{{ $avgDiffAll >= 0 ? '+'.$avgDiffAll : $avgDiffAll }}</td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Kartu 2: Kepatuhan rencana sarapan per minggu --}}
                <div class="dbp-panel-card">
                    <div class="dbp-panel-header">
                        <h3 class="dbp-panel-title">2. Kepatuhan rencana sarapan per minggu</h3>
                        <span class="dbp-pill dbp-pill-neutral">Minggu 1–3</span>
                    </div>

                    <div class="dbp-table-scroll">
                        <table class="dbp-custom-table">
                            <thead>
                                <tr>
                                    <th>Kelas</th>
                                    <th>Minggu 1</th>
                                    <th>Minggu 2</th>
                                    <th>Minggu 3</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($laporanKelasList as $row)
                                    @php
                                        $m1 = (int) round((float) ($row->kepatuhan_minggu_1 ?? 0));
                                        $m2 = (int) round((float) ($row->kepatuhan_minggu_2 ?? 0));
                                        $m3 = (int) round((float) ($row->kepatuhan_minggu_3 ?? 0));
                                    @endphp
                                    <tr>
                                        <td class="font-bold">{{ $row->nama_kelas }}</td>
                                        @foreach ([$m1, $m2, $m3] as $nilaiMinggu)
                                            <td>
                                                <div class="dbp-inline-compliance">
                                                    <div class="dbp-inline-track">
                                                        <div
                                                            class="dbp-inline-fill {{ $nilaiMinggu > 0 && $nilaiMinggu < 65 ? 'dbp-inline-fill-warning' : '' }}"
                                                            style="width: {{ min(100, max(4, $nilaiMinggu)) }}%;"
                                                        ></div>
                                                    </div>
                                                    <strong>{{ $nilaiMinggu }}%</strong>
                                                </div>
                                            </td>
                                        @endforeach
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

            <div class="dbp-overview-stack">
                {{-- Kartu 3: Pola konsumsi (SQ-FFQ) --}}
                <div class="dbp-panel-card">
                    <div class="dbp-panel-header">
                        <div>
                            <h3 class="dbp-panel-title">3. Pola konsumsi (SQ-FFQ)</h3>
                            <p class="dbp-panel-subtitle">Persentase siswa yang makan ≥3x per minggu, per kelompok bahan makanan.</p>
                        </div>
                        <span class="dbp-pill dbp-pill-neutral">9 kelompok</span>
                    </div>

                    <div class="dbp-progress-list">
                        @foreach ($polaKonsumsiList as $pola)
                            <div class="dbp-horizontal-stat-row">
                                <span class="dbp-horizontal-stat-label">{{ $pola['nama'] }}</span>
                                <div class="dbp-progress-track">
                                    <div class="dbp-progress-fill" style="width: {{ min(100, max(3, $pola['persen'])) }}%;"></div>
                                </div>
                                <span class="dbp-horizontal-stat-value">{{ $pola['persen'] }}%</span>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Kartu 4: Tempat sarapan (food record) --}}
                <div class="dbp-panel-card">
                    <div class="dbp-panel-header">
                        <h3 class="dbp-panel-title">4. Tempat sarapan (food record)</h3>
                        <span class="dbp-pill dbp-pill-neutral">Lokasi makan</span>
                    </div>

                    <div class="dbp-progress-list">
                        @foreach ($tempatSarapanList as $tempat)
                            <div class="dbp-horizontal-stat-row">
                                <span class="dbp-horizontal-stat-label">{{ $tempat['nama'] }}</span>
                                <div class="dbp-progress-track">
                                    <div class="dbp-progress-fill" style="width: {{ min(100, max(3, $tempat['persen'])) }}%;"></div>
                                </div>
                                <span class="dbp-horizontal-stat-value">{{ $tempat['persen'] }}%</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        {{-- Kartu 5: Rekap Skor Konsumsi Pangan (SQ-FFQ) per Siswa --}}
        <div class="dbp-panel-card">
            <div class="dbp-panel-header">
                <div>
                    <h3 class="dbp-panel-title">5. Rekap skor konsumsi pangan (SQ-FFQ)</h3>
                    <p class="dbp-panel-subtitle">Total food score dan jumlah bahan makanan yang diisi per responden.</p>
                </div>
            </div>

            <div class="dbp-table-scroll">
                <table class="dbp-custom-table">
                    <thead>
                        <tr>
                            <th>Nama siswa</th>
                            <th>Kelas</th>
                            <th>Tahap</th>
                            <th>Bahan terisi</th>
                            <th>Total skor pangan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($sqffqSkorList as $skorRow)
                            <tr>
                                <td class="font-bold">{{ $skorRow->siswa?->pengguna?->nama_lengkap ?? '-' }}</td>
                                <td>{{ $skorRow->siswa?->kelas?->nama_kelas ?? '-' }}</td>
                                <td><span class="dbp-pill dbp-pill-neutral">{{ $skorRow->sqffq?->tahap ?? 'AWAL' }}</span></td>
                                <td>{{ $skorRow->jumlah_terisi }} item</td>
                                <td class="font-bold">{{ (float) ($skorRow->total_skor ?? 0) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="dbp-empty-cell">Belum ada data skor SQ-FFQ yang tercatat.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-filament-panels::page>

