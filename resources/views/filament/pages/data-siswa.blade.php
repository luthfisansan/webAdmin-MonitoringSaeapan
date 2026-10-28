<x-filament-panels::page>
    <div class="dbp-overview-stack">
        {{-- Baris Filter & Tombol Aksi Atas mengikuti B Data siswa.png --}}
        <div class="dbp-page-toolbar">
            <div class="dbp-toolbar-left">
                <div class="dbp-search-box">
                    <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <circle cx="9" cy="9" r="6" />
                        <path d="m13.5 13.5 4 4" stroke-linecap="round" />
                    </svg>
                    <input
                        type="search"
                        wire:model.live.debounce.250ms="cari"
                        placeholder="Cari nama siswa"
                        aria-label="Cari nama siswa"
                        class="dbp-search-input"
                    />
                </div>

                <div class="dbp-filter-pills">
                    <button
                        type="button"
                        wire:click="pilihKelas(null)"
                        class="dbp-filter-chip {{ $kelasId === null ? 'dbp-filter-chip-active' : '' }}"
                    >
                        Semua kelas
                    </button>

                    @foreach ($daftarKelas as $kelasItem)
                        <button
                            type="button"
                            wire:click="pilihKelas({{ $kelasItem->kelas_id }})"
                            class="dbp-filter-chip {{ $kelasId === $kelasItem->kelas_id ? 'dbp-filter-chip-active' : '' }}"
                        >
                            {{ $kelasItem->nama_kelas }}
                        </button>
                    @endforeach
                </div>
            </div>

            <div class="dbp-toolbar-right">
                <button
                    type="button"
                    wire:click="unduhDaftarCsv"
                    class="dbp-btn-outline"
                >
                    <span>↓ Unduh daftar</span>
                </button>

                <button
                    type="button"
                    wire:click="bukaModalTambah"
                    class="dbp-btn-primary"
                >
                    <span>+ Tambah siswa</span>
                </button>
            </div>
        </div>

        {{-- Banner Peringatan Antrean Verifikasi mengikuti B Data siswa.png --}}
        @if ($menungguVerifikasiCount > 0)
            <div class="dbp-alert-banner">
                <div class="dbp-alert-banner-left">
                    <span class="dbp-alert-icon" aria-hidden="true">⏳</span>
                    <div>
                        <div class="dbp-alert-title">{{ $menungguVerifikasiCount }} siswa menunggu verifikasi</div>
                        <div class="dbp-alert-desc">Isi SQ-FFQ lewat wawancara agar akun mereka bisa masuk ke beranda aplikasi.</div>
                    </div>
                </div>

                <button
                    type="button"
                    wire:click="toggleAntreanVerifikasi"
                    class="dbp-btn-outline dbp-btn-sm"
                >
                    {{ $statusFilter === 'MENUNGGU_VERIFIKASI' ? 'Tampilkan semua' : 'Tampilkan antrean' }}
                </button>
            </div>
        @endif

        {{-- Form Tambah Siswa Baru (Inline Panel) --}}
        @if ($tampilkanModalTambah)
            <div class="dbp-panel-card">
                <div class="dbp-panel-header">
                    <div>
                        <h3 class="dbp-panel-title">Tambah Akun Siswa Baru</h3>
                        <p class="dbp-panel-subtitle">Siswa yang didaftarkan akan otomatis berstatus awal Belum Pretest (PRETEST).</p>
                    </div>
                    <button type="button" wire:click="tutupModalTambah" class="dbp-btn-outline dbp-btn-sm">Tutup</button>
                </div>

                <form wire:submit="simpanSiswaBaru" class="dbp-form-grid">
                    <div class="dbp-field">
                        <label class="dbp-field-label">Nama lengkap siswa *</label>
                        <input type="text" wire:model="namaLengkapBaru" class="dbp-field-input" placeholder="Contoh: Aisyah Putri" required />
                        @error('namaLengkapBaru') <span class="dbp-field-error">{{ $message }}</span> @enderror
                    </div>

                    <div class="dbp-field">
                        <label class="dbp-field-label">Email login *</label>
                        <input type="email" wire:model="emailBaru" class="dbp-field-input" placeholder="Contoh: aisyah@siswa.id" required />
                        @error('emailBaru') <span class="dbp-field-error">{{ $message }}</span> @enderror
                    </div>

                    <div class="dbp-field">
                        <label class="dbp-field-label">Kata sandi *</label>
                        <input type="password" wire:model="kataSandiBaru" class="dbp-field-input" placeholder="Minimal 6 karakter" required />
                        @error('kataSandiBaru') <span class="dbp-field-error">{{ $message }}</span> @enderror
                    </div>

                    <div class="dbp-field">
                        <label class="dbp-field-label">Kelas *</label>
                        <select wire:model="kelasIdBaru" class="dbp-field-input" required>
                            <option value="">-- Pilih Kelas --</option>
                            @foreach ($daftarKelas as $kelasOpt)
                                <option value="{{ $kelasOpt->kelas_id }}">{{ $kelasOpt->nama_kelas }} ({{ $kelasOpt->tahun_ajaran }})</option>
                            @endforeach
                        </select>
                        @error('kelasIdBaru') <span class="dbp-field-error">{{ $message }}</span> @enderror
                    </div>

                    <div class="dbp-field">
                        <label class="dbp-field-label">Jenis kelamin (opsional)</label>
                        <select wire:model="jenisKelaminBaru" class="dbp-field-input">
                            <option value="">-- Tidak diisi --</option>
                            <option value="P">Perempuan (P)</option>
                            <option value="L">Laki-laki (L)</option>
                        </select>
                    </div>

                    <div class="dbp-field">
                        <label class="dbp-field-label">Tanggal lahir (opsional)</label>
                        <input type="date" wire:model="tanggalLahirBaru" class="dbp-field-input" />
                    </div>

                    <div class="dbp-form-actions">
                        <button type="button" wire:click="tutupModalTambah" class="dbp-btn-outline">Batal</button>
                        <button type="submit" class="dbp-btn-primary">Simpan Siswa</button>
                    </div>
                </form>
            </div>
        @endif

        {{-- Tabel Daftar Siswa mengikuti B Data siswa.png --}}
        <div class="dbp-panel-card dbp-table-card">
            <div class="dbp-table-scroll">
                <table class="dbp-custom-table">
                    <thead>
                        <tr>
                            <th>Nama siswa</th>
                            <th>Kelas</th>
                            <th>Pretest</th>
                            <th>SQ-FFQ</th>
                            <th>Kepatuhan rencana</th>
                            <th>Status akun</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($daftarSiswa as $siswa)
                            <tr>
                                <td>
                                    <a
                                        href="{{ \App\Filament\Pages\DetailSiswa::getUrl(['siswa_id' => $siswa->siswa_id]) }}"
                                        class="dbp-student-link"
                                    >
                                        {{ $siswa->nama_lengkap }}
                                    </a>
                                </td>
                                <td>{{ $siswa->nama_kelas }}</td>
                                <td>
                                    @if ($siswa->pretest_selesai)
                                        <span class="dbp-pill dbp-pill-success">Selesai</span>
                                    @else
                                        <span class="dbp-pill dbp-pill-warning">Belum</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($siswa->sqffq_status === 'SELESAI')
                                        <span class="dbp-pill dbp-pill-success">Selesai</span>
                                    @elseif ($siswa->sqffq_status === 'DRAF')
                                        <span class="dbp-pill dbp-pill-warning">Proses</span>
                                    @else
                                        <span class="dbp-pill dbp-pill-warning">Belum</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($siswa->persen_kepatuhan !== null)
                                        @php
                                            $persen = (int) round((float) $siswa->persen_kepatuhan);
                                        @endphp
                                        <div class="dbp-inline-compliance">
                                            <div class="dbp-inline-track">
                                                <div
                                                    class="dbp-inline-fill {{ $persen < 65 ? 'dbp-inline-fill-warning' : '' }}"
                                                    style="width: {{ min(100, max(5, $persen)) }}%;"
                                                ></div>
                                            </div>
                                            <strong>{{ $persen }}%</strong>
                                        </div>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($siswa->status_akun_kode === 'TERVERIFIKASI')
                                        <span class="dbp-pill dbp-pill-success">Terverifikasi</span>
                                    @elseif ($siswa->status_akun_kode === 'MENUNGGU_VERIFIKASI')
                                        <span class="dbp-pill dbp-pill-warning">Menunggu verifikasi</span>
                                    @else
                                        <span class="dbp-pill dbp-pill-neutral">{{ $siswa->status_akun_nama }}</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($siswa->status_akun_kode === 'TERVERIFIKASI')
                                        <a
                                            href="{{ \App\Filament\Pages\DetailSiswa::getUrl(['siswa_id' => $siswa->siswa_id]) }}"
                                            class="dbp-btn-outline dbp-btn-sm"
                                        >
                                            Lihat
                                        </a>
                                    @elseif ($siswa->sqffq_status === 'DRAF')
                                        <a
                                            href="{{ \App\Filament\Pages\VerifikasiSqffq::getUrl(['siswa_id' => $siswa->siswa_id]) }}"
                                            class="dbp-btn-primary dbp-btn-sm"
                                        >
                                            Lanjutkan
                                        </a>
                                    @else
                                        <a
                                            href="{{ \App\Filament\Pages\VerifikasiSqffq::getUrl(['siswa_id' => $siswa->siswa_id]) }}"
                                            class="dbp-btn-primary dbp-btn-sm"
                                        >
                                            Isi SQ-FFQ
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="dbp-empty-cell">
                                    Belum ada data siswa yang sesuai dengan filter.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="dbp-table-footer">
                <span>Menampilkan {{ $daftarSiswa->count() }} siswa</span>
            </div>
        </div>
    </div>
</x-filament-panels::page>

