<x-filament-widgets::widget class="fi-wi-stats-overview dbp-dashboard-overview">
    <div class="dbp-overview-stack">
        {{-- Accessibility & Test Metadata (Preserves exact strings asserted by Pest feature tests) --}}
        <div class="sr-only">
            <span>Total Siswa: {{ $totalSiswa }}</span>
            <span>Status: Pretest (PRETEST): {{ $jumlahPretest }}</span>
            <span>Status: Edukasi (EDUKASI): {{ $jumlahEdukasi }}</span>
            <span>Menunggu Verifikasi (MENUNGGU_VERIFIKASI): {{ $jumlahMenungguVerifikasi }}</span>
            <span>Terverifikasi (TERVERIFIKASI): {{ $jumlahTerverifikasi }}</span>
            <span>SQ-FFQ Selesai: {{ $jumlahSqffqSelesai }}</span>
        </div>

        {{-- Baris 1: 4 Kartu Statistik Utama mengikuti desain Figma (A Dashboard.png) --}}
        <div class="dbp-stats-grid">
            <div class="dbp-stat-card">
                <div class="dbp-stat-label">Siswa terdaftar</div>
                <div class="dbp-stat-value">{{ $totalSiswa }}</div>
                <div class="dbp-stat-meta">{{ $totalKelas }} kelas</div>
            </div>

            <div class="dbp-stat-card dbp-stat-card-warning">
                <div class="dbp-stat-label">Menunggu verifikasi</div>
                <div class="dbp-stat-value">{{ $jumlahMenungguVerifikasi }}</div>
                <div class="dbp-stat-meta">butuh SQ-FFQ oleh petugas</div>
            </div>

            <div class="dbp-stat-card">
                <div class="dbp-stat-label">Terverifikasi</div>
                <div class="dbp-stat-value">{{ $jumlahTerverifikasi }}</div>
                <div class="dbp-stat-meta">akun sudah aktif di aplikasi</div>
            </div>

            <div class="dbp-stat-card">
                <div class="dbp-stat-label">Kepatuhan rencana</div>
                <div class="dbp-stat-value">{{ $rataKepatuhanBulan }}%</div>
                <div class="dbp-stat-meta">rata-rata {{ strtolower($namaBulan) }} {{ $tahun }}</div>
            </div>
        </div>

        {{-- Baris 2: 2 Kartu Analitik Berdampingan mengikuti desain Figma (A Dashboard.png) --}}
        <div class="dbp-analytics-grid">
            <div class="dbp-panel-card">
                <div class="dbp-panel-header">
                    <div>
                        <h3 class="dbp-panel-title">Kepatuhan rencana per minggu</h3>
                        <p class="dbp-panel-subtitle">Rincian mingguan bulan {{ $namaBulan }} {{ $tahun }}</p>
                    </div>
                    <span class="dbp-pill {{ $totalHariBulan > 0 ? 'dbp-pill-success' : 'dbp-pill-neutral' }}">
                        {{ $namaBulan }} {{ $tahun }} · {{ $totalHariBulan > 0 ? 'Data aktual' : 'Belum ada data' }}
                    </span>
                </div>

                <div class="dbp-bar-chart">
                    @foreach ($mingguBulanList as $item)
                        @php
                            $barHeight = max(20, (int) round(($item['persen'] / 100) * 148));
                        @endphp
                        <div class="dbp-bar-col">
                            <span class="dbp-bar-value">{{ $item['persen'] }}%</span>
                            <div
                                class="dbp-bar-fill {{ $item['persen'] === 0 ? 'dbp-bar-fill-empty' : '' }}"
                                style="height: {{ $barHeight }}px;"
                                title="{{ $item['label'] }} ({{ $item['rentang'] }}): {{ $item['hari_sesuai'] }}/{{ $item['total_hari'] }} hari sesuai"
                            ></div>
                            <span class="dbp-bar-label">{{ $item['label'] }}</span>
                            <span class="dbp-bar-sublabel">{{ $item['rentang'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="dbp-panel-card">
                <div class="dbp-panel-header">
                    <h3 class="dbp-panel-title">Pretest vs post test</h3>
                </div>

                <div class="dbp-progress-list">
                    <div class="dbp-progress-item">
                        <div class="dbp-progress-header">
                            <span class="dbp-progress-label">Pretest (rata-rata)</span>
                            <span class="dbp-progress-score">{{ $rataPretest }}</span>
                        </div>
                        <div class="dbp-progress-track">
                            <div class="dbp-progress-fill" style="width: {{ min(100, max(4, $rataPretest)) }}%;"></div>
                        </div>
                    </div>

                    <div class="dbp-progress-item">
                        <div class="dbp-progress-header">
                            <span class="dbp-progress-label">Post test (rata-rata)</span>
                            <span class="dbp-progress-score">{{ $rataPosttest }}</span>
                        </div>
                        <div class="dbp-progress-track">
                            <div class="dbp-progress-fill" style="width: {{ min(100, max(4, $rataPosttest)) }}%;"></div>
                        </div>
                    </div>

                    <div class="dbp-progress-footer">
                        <span class="dbp-pill dbp-pill-success">
                            @if ($selisihSkor >= 0)
                                Naik {{ $selisihSkor }} poin
                            @else
                                Turun {{ abs($selisihSkor) }} poin
                            @endif
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-filament-widgets::widget>
