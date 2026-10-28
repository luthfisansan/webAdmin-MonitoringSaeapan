<x-filament-panels::page>
    <div class="dbp-overview-stack">
        @if (! $siswaAktif)
            {{-- Tampilan Pemilih Antrean Verifikasi bila belum ada responden aktif --}}
            <div class="dbp-panel-card">
                <div class="dbp-panel-header">
                    <div>
                        <h3 class="dbp-panel-title">Antrean Verifikasi SQ-FFQ</h3>
                        <p class="dbp-panel-subtitle">Pilih siswa yang telah menyelesaikan pretest & materi edukasi wajib untuk diwawancarai.</p>
                    </div>
                    <span class="dbp-pill dbp-pill-warning">{{ $antreanList->count() }} menunggu</span>
                </div>

                <div class="dbp-table-scroll">
                    <table class="dbp-custom-table">
                        <thead>
                            <tr>
                                <th>Nama siswa</th>
                                <th>Kelas</th>
                                <th>Progres item SQ-FFQ</th>
                                <th>Status SQ-FFQ</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($antreanList as $antrean)
                                <tr>
                                    <td class="font-bold">{{ $antrean->nama_lengkap }}</td>
                                    <td>{{ $antrean->nama_kelas }}</td>
                                    <td>{{ $antrean->item_terisi }} / {{ $antrean->item_total }} item</td>
                                    <td>
                                        <span class="dbp-pill dbp-pill-warning">
                                            {{ $antrean->sqffq_status === 'DRAF' ? 'Draf tersimpan' : 'Belum diisi' }}
                                        </span>
                                    </td>
                                    <td>
                                        <button
                                            type="button"
                                            wire:click="pilihSiswa({{ $antrean->siswa_id }})"
                                            class="dbp-btn-primary dbp-btn-sm"
                                        >
                                            {{ $antrean->sqffq_status === 'DRAF' ? 'Lanjutkan' : 'Isi SQ-FFQ' }}
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="dbp-empty-cell">
                                        Belum ada siswa dalam antrean verifikasi. Anda juga dapat memilih siswa dari daftar di bawah.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @if ($semuaSiswaList->isNotEmpty())
                <div class="dbp-panel-card">
                    <div class="dbp-panel-header">
                        <h3 class="dbp-panel-title">Semua Siswa Terdaftar</h3>
                    </div>

                    <div class="dbp-table-scroll">
                        <table class="dbp-custom-table">
                            <thead>
                                <tr>
                                    <th>Nama siswa</th>
                                    <th>Kelas</th>
                                    <th>Status akun</th>
                                    <th>SQ-FFQ awal</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($semuaSiswaList as $siswaRow)
                                    <tr>
                                        <td class="font-bold">{{ $siswaRow->nama_lengkap }}</td>
                                        <td>{{ $siswaRow->nama_kelas }}</td>
                                        <td>
                                            <span class="dbp-pill {{ $siswaRow->status_akun_kode === 'TERVERIFIKASI' ? 'dbp-pill-success' : ($siswaRow->status_akun_kode === 'MENUNGGU_VERIFIKASI' ? 'dbp-pill-warning' : 'dbp-pill-neutral') }}">
                                                {{ $siswaRow->status_akun_nama }}
                                            </span>
                                        </td>
                                        <td>{{ $siswaRow->sqffq_status }}</td>
                                        <td>
                                            <button
                                                type="button"
                                                wire:click="pilihSiswa({{ $siswaRow->siswa_id }})"
                                                class="dbp-btn-outline dbp-btn-sm"
                                            >
                                                Buka Form SQ-FFQ
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        @else
            {{-- Toolbar Aksi Atas mengikuti C Form SQ-FFQ (petugas).png --}}
            <div class="dbp-page-toolbar">
                <div class="dbp-toolbar-left">
                    <button type="button" wire:click="gantiSiswa" class="dbp-btn-outline dbp-btn-sm">
                        ← Ganti responden
                    </button>
                </div>

                <div class="dbp-toolbar-right">
                    <button
                        type="button"
                        wire:click="simpanDraf"
                        class="dbp-btn-outline"
                    >
                        Simpan draf
                    </button>

                    <button
                        type="button"
                        wire:click="selesaikanSqffq"
                        class="dbp-btn-primary"
                    >
                        Simpan dan verifikasi akun
                    </button>
                </div>
            </div>

            {{-- Banner Informasi Verifikasi mengikuti C Form SQ-FFQ (petugas).png --}}
            <div class="dbp-alert-banner">
                <div class="dbp-alert-banner-left">
                    <span class="dbp-alert-icon" aria-hidden="true">⏳</span>
                    <div>
                        <div class="dbp-alert-title">Setelah disimpan, akun siswa otomatis Terverifikasi</div>
                        <div class="dbp-alert-desc">
                            Aplikasi {{ $siswaAktif->pengguna?->nama_lengkap }} akan membuka Beranda saat ia memeriksa status atau login ulang.
                        </div>
                    </div>
                </div>
            </div>

            {{-- Layout 2 Kolom: Sidebar Responden & Kelompok (Kiri) + Tabel Bahan Makanan (Kanan) --}}
            <div class="dbp-sqffq-layout">
                {{-- Kartu Kiri: Responden & Daftar 9 Kelompok Bahan (A-I) --}}
                <div class="dbp-panel-card dbp-sqffq-sidebar">
                    <div class="dbp-respondent-meta">RESPONDEN</div>
                    <div class="dbp-respondent-name">{{ $siswaAktif->pengguna?->nama_lengkap }}</div>
                    <div class="dbp-respondent-sub">
                        Kelas {{ $siswaAktif->kelas?->nama_kelas }} · Wawancara {{ \Illuminate\Support\Carbon::parse($tanggalWawancara)->translatedFormat('d M Y') }}
                    </div>

                    <div style="margin-top: 0.5rem;">
                        @if ($siswaAktif->statusAkun?->kode === 'TERVERIFIKASI')
                            <span class="dbp-pill dbp-pill-success">Terverifikasi</span>
                        @else
                            <span class="dbp-pill dbp-pill-warning">{{ $siswaAktif->statusAkun?->nama ?? 'Menunggu verifikasi' }}</span>
                        @endif
                    </div>

                    <div class="dbp-sqffq-progress-box">
                        <div class="dbp-progress-header">
                            <span class="dbp-progress-label">Terisi</span>
                            <span class="dbp-progress-score">{{ $totalTerisi }} / {{ $totalBahanAktif }} item</span>
                        </div>
                        @php
                            $persenProgresSqffq = $totalBahanAktif > 0
                                ? (int) round(($totalTerisi / $totalBahanAktif) * 100)
                                : 0;
                        @endphp
                        <div class="dbp-progress-track">
                            <div class="dbp-progress-fill" style="width: {{ min(100, max(3, $persenProgresSqffq)) }}%;"></div>
                        </div>
                    </div>

                    <div class="dbp-group-nav-list">
                        @foreach ($kelompokList as $kelompokItem)
                            @php
                                $isAktif = $kelompokAktif && $kelompokAktif->kode === $kelompokItem->kode;
                                $statKelompok = $progresPerKelompok[$kelompokItem->kode] ?? ['terisi' => 0, 'total' => 0];
                            @endphp
                            <button
                                type="button"
                                wire:click="pilihKelompok('{{ $kelompokItem->kode }}')"
                                class="dbp-group-nav-item {{ $isAktif ? 'dbp-group-nav-item-active' : '' }}"
                            >
                                <span class="dbp-group-badge {{ $isAktif ? 'dbp-group-badge-active' : '' }}">
                                    {{ $kelompokItem->kode }}
                                </span>
                                <span class="dbp-group-name">{{ $kelompokItem->nama }}</span>
                                <span class="dbp-group-count">{{ $statKelompok['terisi'] }}/{{ $statKelompok['total'] }}</span>
                            </button>
                        @endforeach
                    </div>
                </div>

                {{-- Kartu Kanan: Tabel Bahan Makanan Kelompok Aktif --}}
                <div class="dbp-panel-card dbp-sqffq-content">
                    @if ($kelompokAktif)
                        @php
                            $statAktif = $progresPerKelompok[$kelompokAktif->kode] ?? ['terisi' => 0, 'total' => 0];
                        @endphp
                        <div class="dbp-panel-header">
                            <div>
                                <h3 class="dbp-panel-title" style="font-size: 1.25rem;">
                                    {{ $kelompokAktif->kode }}. {{ $kelompokAktif->nama }}
                                </h3>
                                <p class="dbp-panel-subtitle">
                                    Tanyakan porsi yang biasa dimakan (gram), lalu seberapa sering siswa memakannya.
                                </p>
                            </div>
                            <span class="dbp-pill dbp-pill-success">
                                {{ $statAktif['terisi'] }} / {{ $statAktif['total'] }} terisi
                            </span>
                        </div>

                        <div class="dbp-table-scroll">
                            <table class="dbp-custom-table dbp-sqffq-table">
                                <thead>
                                    <tr>
                                        <th style="width: 3rem;">No</th>
                                        <th>Bahan makanan</th>
                                        <th style="width: 8.5rem; text-align: center;">Satu porsi (g)</th>
                                        @foreach ($frekuensiList as $frekHeader)
                                            <th style="text-align: center;">{{ $frekHeader->label }}</th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($kelompokAktif->bahanMakanan as $index => $bahan)
                                        <tr wire:key="bahan-row-{{ $bahan->bahan_makanan_id }}">
                                            <td>{{ $index + 1 }}</td>
                                            <td class="font-bold">{{ $bahan->nama_bahan }}</td>
                                            <td style="text-align: center;">
                                                <input
                                                    type="number"
                                                    step="0.01"
                                                    min="0"
                                                    wire:model.blur="porsiGram.{{ $bahan->bahan_makanan_id }}"
                                                    placeholder="{{ $bahan->porsi_standar_gram !== null ? (int) $bahan->porsi_standar_gram : '—' }}"
                                                    class="dbp-portion-input"
                                                    aria-label="Porsi gram {{ $bahan->nama_bahan }}"
                                                />
                                            </td>
                                            @foreach ($frekuensiList as $frekOption)
                                                <td style="text-align: center;">
                                                    <input
                                                        type="radio"
                                                        name="frekuensi_{{ $bahan->bahan_makanan_id }}"
                                                        value="{{ $frekOption->frekuensi_id }}"
                                                        wire:model.live="frekuensiPilihan.{{ $bahan->bahan_makanan_id }}"
                                                        class="dbp-freq-radio"
                                                        aria-label="{{ $bahan->nama_bahan }} {{ $frekOption->label }}"
                                                    />
                                                </td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="dbp-sqffq-footer">
                            @if ($kelompokSebelumnya)
                                <button
                                    type="button"
                                    wire:click="pilihKelompok('{{ $kelompokSebelumnya->kode }}')"
                                    class="dbp-btn-outline"
                                >
                                    ‹ Kembali ke {{ $kelompokSebelumnya->kode }}. {{ $kelompokSebelumnya->nama }}
                                </button>
                            @else
                                <div></div>
                            @endif

                            @if ($kelompokBerikutnya)
                                <button
                                    type="button"
                                    wire:click="pilihKelompok('{{ $kelompokBerikutnya->kode }}')"
                                    class="dbp-btn-primary"
                                >
                                    Lanjut ke {{ $kelompokBerikutnya->kode }}. {{ $kelompokBerikutnya->nama }}
                                </button>
                            @else
                                <button
                                    type="button"
                                    wire:click="selesaikanSqffq"
                                    class="dbp-btn-primary"
                                >
                                    Simpan dan verifikasi akun
                                </button>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>

