@if (request()->routeIs('filament.admin.pages.dashboard'))
    <form method="GET" action="{{ route('filament.admin.pages.dashboard') }}" class="dbp-dashboard-filter-bar">
        <label class="dbp-class-filter-pill" title="Pilih bulan">
            <select
                name="bulan"
                aria-label="Filter bulan"
                class="dbp-filter-select"
                onchange="this.form.submit()"
            >
                @foreach ($daftarBulan as $nomorBulan => $namaBulan)
                    <option value="{{ $nomorBulan }}" @selected($bulanAktif === $nomorBulan)>
                        {{ $namaBulan }}
                    </option>
                @endforeach
            </select>
            <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="m5.5 7.5 4.5 4.5 4.5-4.5" />
            </svg>
        </label>

        <label class="dbp-class-filter-pill" title="Pilih tahun">
            <select
                name="tahun"
                aria-label="Filter tahun"
                class="dbp-filter-select"
                onchange="this.form.submit()"
            >
                @foreach ($daftarTahun as $tahunItem)
                    <option value="{{ $tahunItem }}" @selected($tahunAktif === $tahunItem)>
                        {{ $tahunItem }}
                    </option>
                @endforeach
            </select>
            <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="m5.5 7.5 4.5 4.5 4.5-4.5" />
            </svg>
        </label>

        <label class="dbp-class-filter-pill" title="Filter kelas">
            <select
                name="kelas_id"
                aria-label="Filter kelas"
                class="dbp-filter-select"
                onchange="this.form.submit()"
            >
                <option value="">Semua kelas</option>
                @foreach ($daftarKelas as $kelasItem)
                    <option value="{{ $kelasItem->kelas_id }}" @selected($kelasIdAktif === $kelasItem->kelas_id)>
                        {{ $kelasItem->nama_kelas }}
                    </option>
                @endforeach
            </select>
            <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="m5.5 7.5 4.5 4.5 4.5-4.5" />
            </svg>
        </label>

        <noscript>
            <button type="submit" class="fi-btn fi-color-primary">Terapkan</button>
        </noscript>
    </form>
@endif


