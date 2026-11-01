<x-filament-panels::page>
    <div class="dbp-overview-stack">
        {{-- Toolbar Filter & Aksi Ekspor --}}
        <div class="dbp-page-toolbar">
            <div class="dbp-toolbar-left">
                <label class="dbp-class-filter-pill" title="Filter sekolah">
                    <select wire:model.live="sekolahId" class="dbp-filter-select" aria-label="Filter sekolah">
                        @foreach ($daftarSekolah as $sekolahOpt)
                            <option value="{{ $sekolahOpt->sekolah_id }}">{{ $sekolahOpt->nama_sekolah }}</option>
                        @endforeach
                    </select>
                    <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="m5.5 7.5 4.5 4.5 4.5-4.5" />
                    </svg>
                </label>

                <label class="dbp-class-filter-pill" title="Filter kelas">
                    <select wire:model.live="kelasId" class="dbp-filter-select" aria-label="Filter kelas">
                        @foreach ($daftarKelas as $kelasOpt)
                            <option value="{{ $kelasOpt->kelas_id }}">{{ $kelasOpt->nama_kelas }}</option>
                        @endforeach
                    </select>
                    <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="m5.5 7.5 4.5 4.5 4.5-4.5" />
                    </svg>
                </label>

                <div class="dbp-search-box">
                    <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <circle cx="9" cy="9" r="6" />
                        <path d="m13.5 13.5 4 4" stroke-linecap="round" />
                    </svg>
                    <input
                        type="search"
                        wire:model.live.debounce.250ms="cari"
                        placeholder="Cari nama siswa di kelas ini..."
                        aria-label="Cari nama siswa"
                        class="dbp-search-input"
                    />
                </div>
            </div>

            <div class="dbp-toolbar-right">
                <button
                    type="button"
                    wire:click="unduhCsv"
                    class="dbp-btn-outline"
                    @disabled(! $kelasId || empty($siswaRows))
                >
                    ↓ Unduh CSV Kelas
                </button>

                @if ($kelasId)
                    <a
                        href="{{ route('admin.laporan.cetak-kelas', ['kelas_id' => $kelasId]) }}"
                        target="_blank"
                        class="dbp-btn-primary"
                        style="text-decoration: none; display: inline-flex; align-items: center; gap: 6px;"
                    >
                        🖨️ Cetak Lembar Kelas (PDF)
                    </a>
                @endif
            </div>
        </div>

        {{-- Ringkasan Indikator Kelas (KPI Cards) --}}
        <div class="dbp-stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));">
            <div class="dbp-stat-card">
                <span class="dbp-stat-label">Total Siswa Terdata</span>
                <span class="dbp-stat-value">{{ $ringkasan['total_siswa'] }}</span>
                <span class="dbp-stat-meta">{{ $kelasTerpilih ? $kelasTerpilih->nama_kelas : 'Pilih kelas' }}</span>
            </div>

            <div class="dbp-stat-card">
                <span class="dbp-stat-label">Rata-rata Pretest</span>
                <span class="dbp-stat-value">{{ $ringkasan['rata_pretest'] !== null ? $ringkasan['rata_pretest'] : '-' }}</span>
                <span class="dbp-stat-meta">Skala 0–100 (Awal)</span>
            </div>

            <div class="dbp-stat-card">
                <span class="dbp-stat-label">Rata-rata Posttest</span>
                <span class="dbp-stat-value">{{ $ringkasan['rata_posttest'] !== null ? $ringkasan['rata_posttest'] : '-' }}</span>
                <span class="dbp-stat-meta">Skala 0–100 (Akhir)</span>
            </div>

            <div class="dbp-stat-card">
                <span class="dbp-stat-label">Peningkatan Pengetahuan</span>
                <span class="dbp-stat-value" style="color: {{ ($ringkasan['rata_selisih'] ?? 0) >= 0 ? '#17624B' : '#B91C1C' }};">
                    @if ($ringkasan['rata_selisih'] !== null)
                        {{ $ringkasan['rata_selisih'] >= 0 ? '+' . $ringkasan['rata_selisih'] : $ringkasan['rata_selisih'] }}
                    @else
                        -
                    @endif
                </span>
                <span class="dbp-stat-meta">Selisih skor rata-rata</span>
            </div>

            <div class="dbp-stat-card">
                <span class="dbp-stat-label">Rata-rata Sikap Konatif</span>
                <span class="dbp-stat-value">{{ $ringkasan['rata_kecenderungan'] !== null ? $ringkasan['rata_kecenderungan'] . '%' : '-' }}</span>
                <span class="dbp-stat-meta">Kesesuaian rencana M1–M3</span>
            </div>

            <div class="dbp-stat-card">
                <span class="dbp-stat-label">Rata-rata Skor SQ-FFQ</span>
                <span class="dbp-stat-value">{{ $ringkasan['rata_sqffq'] !== null ? $ringkasan['rata_sqffq'] : '-' }}</span>
                <span class="dbp-stat-meta">Kebiasaan sarapan awal</span>
            </div>
        </div>

        {{-- Tabel Data Individual Siswa --}}
        <div class="dbp-panel-card">
            <div class="dbp-panel-header">
                <div>
                    <h3 class="dbp-panel-title">
                        Daftar Siswa Kelas {{ $kelasTerpilih?->nama_kelas ?? '-' }}
                    </h3>
                    <p class="dbp-panel-subtitle">
                        {{ $kelasTerpilih?->sekolah?->nama_sekolah ?? '-' }} · Tahun Ajaran {{ $kelasTerpilih?->tahun_ajaran ?? '-' }} · Total {{ count($siswaRows) }} Siswa
                    </p>
                </div>
                <div class="dbp-panel-badge">
                    <span class="dbp-pill dbp-pill-success">Rekap Perorangan</span>
                </div>
            </div>

            <div class="dbp-table-scroll">
                <table class="dbp-custom-table">
                    <thead>
                        <tr>
                            <th style="width: 44px; text-align: center;">No.</th>
                            <th>Nama Siswa</th>
                            <th style="text-align: center;">L/P</th>
                            <th>Status Akun</th>
                            <th style="text-align: center;">Pretest</th>
                            <th style="text-align: center;">Posttest</th>
                            <th style="text-align: center;">Selisih</th>
                            <th style="text-align: center;">Sikap M1</th>
                            <th style="text-align: center;">Sikap M2</th>
                            <th style="text-align: center;">Sikap M3</th>
                            <th style="text-align: center;">Rata-rata Sikap</th>
                            <th style="text-align: center;">SQ-FFQ</th>
                            <th style="text-align: center; width: 140px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($siswaRows as $idx => $row)
                            <tr>
                                <td style="text-align: center; color: var(--dbp-text-muted);">{{ $idx + 1 }}</td>
                                <td>
                                    <div style="font-weight: 600; color: var(--dbp-text-main);">
                                        {{ $row['nama_lengkap'] }}
                                    </div>
                                    <div style="font-size: 0.75rem; color: var(--dbp-text-muted);">
                                        ID #{{ $row['siswa_id'] }} · {{ $row['email'] }}
                                    </div>
                                </td>
                                <td style="text-align: center; font-weight: 500;">
                                    {{ $row['jenis_kelamin'] }}
                                </td>
                                <td>
                                    @php
                                        $pillClass = match ($row['status_akun_kode']) {
                                            'TERVERIFIKASI' => 'dbp-pill-success',
                                            'MENUNGGU_VERIFIKASI' => 'dbp-pill-warning',
                                            'EDUKASI' => 'dbp-pill-neutral',
                                            default => 'dbp-pill-neutral',
                                        };
                                    @endphp
                                    <span class="dbp-pill {{ $pillClass }}">
                                        {{ $row['status_akun_nama'] }}
                                    </span>
                                </td>
                                <td style="text-align: center; font-variant-numeric: tabular-nums;">
                                    {{ $row['skor_pretest'] !== null ? $row['skor_pretest'] : '-' }}
                                </td>
                                <td style="text-align: center; font-variant-numeric: tabular-nums;">
                                    {{ $row['skor_posttest'] !== null ? $row['skor_posttest'] : '-' }}
                                </td>
                                <td style="text-align: center; font-variant-numeric: tabular-nums; font-weight: 600;">
                                    @if ($row['selisih'] !== null)
                                        <span style="color: {{ $row['selisih'] >= 0 ? '#17624B' : '#B91C1C' }};">
                                            {{ $row['selisih'] >= 0 ? '+' . $row['selisih'] : $row['selisih'] }}
                                        </span>
                                    @else
                                        <span style="color: var(--dbp-text-muted);">-</span>
                                    @endif
                                </td>
                                <td style="text-align: center;">
                                    @if ($row['m1_persen'] !== null)
                                        <span style="font-weight: 600;">{{ $row['m1_persen'] }}%</span>
                                        <div style="font-size: 0.7rem; color: var(--dbp-text-muted);">{{ $row['m1_sesuai'] }}/{{ $row['m1_berjalan'] }} hari</div>
                                    @else
                                        <span style="color: var(--dbp-text-muted);">-</span>
                                    @endif
                                </td>
                                <td style="text-align: center;">
                                    @if ($row['m2_persen'] !== null)
                                        <span style="font-weight: 600;">{{ $row['m2_persen'] }}%</span>
                                        <div style="font-size: 0.7rem; color: var(--dbp-text-muted);">{{ $row['m2_sesuai'] }}/{{ $row['m2_berjalan'] }} hari</div>
                                    @else
                                        <span style="color: var(--dbp-text-muted);">-</span>
                                    @endif
                                </td>
                                <td style="text-align: center;">
                                    @if ($row['m3_persen'] !== null)
                                        <span style="font-weight: 600;">{{ $row['m3_persen'] }}%</span>
                                        <div style="font-size: 0.7rem; color: var(--dbp-text-muted);">{{ $row['m3_sesuai'] }}/{{ $row['m3_berjalan'] }} hari</div>
                                    @else
                                        <span style="color: var(--dbp-text-muted);">-</span>
                                    @endif
                                </td>
                                <td style="text-align: center;">
                                    @if ($row['rata_kecenderungan'] !== null)
                                        <div class="dbp-inline-compliance" style="justify-content: center;">
                                            <div class="dbp-inline-track" style="width: 50px;">
                                                <div
                                                    class="dbp-inline-fill {{ $row['rata_kecenderungan'] < 65 ? 'dbp-inline-fill-warning' : '' }}"
                                                    style="width: {{ min(100, max(4, $row['rata_kecenderungan'])) }}%;"
                                                ></div>
                                            </div>
                                            <strong>{{ $row['rata_kecenderungan'] }}%</strong>
                                        </div>
                                    @else
                                        <span style="color: var(--dbp-text-muted);">-</span>
                                    @endif
                                </td>
                                <td style="text-align: center;">
                                    @if ($row['sqffq_skor'] !== null)
                                        <div style="font-weight: 600;">{{ $row['sqffq_skor'] }}</div>
                                        @php
                                            $sqClass = match ($row['sqffq_kategori']) {
                                                'Baik' => 'dbp-pill-success',
                                                'Cukup' => 'dbp-pill-warning',
                                                default => 'dbp-pill-danger',
                                            };
                                        @endphp
                                        <span class="dbp-pill {{ $sqClass }}" style="font-size: 0.7rem; padding: 2px 6px;">
                                            {{ $row['sqffq_kategori'] }}
                                        </span>
                                    @else
                                        <span style="color: var(--dbp-text-muted);">-</span>
                                    @endif
                                </td>
                                <td style="text-align: center;">
                                    <div style="display: flex; gap: 4px; justify-content: center;">
                                        <a
                                            href="{{ url('/admin/detail-siswa?siswa_id=' . $row['siswa_id']) }}"
                                            class="dbp-btn-outline dbp-btn-sm"
                                            title="Buka detail profil & kepatuhan siswa"
                                            style="text-decoration: none; padding: 4px 8px; font-size: 0.75rem;"
                                        >
                                            Detail
                                        </a>
                                        <a
                                            href="{{ route('admin.detail-siswa.cetak', ['siswa_id' => $row['siswa_id']]) }}"
                                            target="_blank"
                                            class="dbp-btn-outline dbp-btn-sm"
                                            title="Cetak lembar perorangan siswa"
                                            style="text-decoration: none; padding: 4px 8px; font-size: 0.75rem;"
                                        >
                                            🖨️
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="13" class="dbp-empty-cell">
                                    <div style="padding: 24px; text-align: center; color: var(--dbp-text-muted);">
                                        Belum ada data siswa untuk kelas yang dipilih atau pencarian tidak ditemukan.
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-filament-panels::page>

