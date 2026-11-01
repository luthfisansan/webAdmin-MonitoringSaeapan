<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Penelitian Monitoring Sarapan - Digital Breakfast Planner</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #17624B;
            --primary-dark: #0F4434;
            --primary-light: #E8F3EE;
            --accent: #F2A33A;
            --text-main: #14261F;
            --text-muted: #55655D;
            --border-color: #D3DFD8;
            --border-dark: #98B3A4;
            --bg-gray: #F9FAF8;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Plus Jakarta Sans', ui-sans-serif, system-ui, sans-serif;
            color: var(--text-main);
            background-color: #EBF0EC;
            font-size: 11pt;
            line-height: 1.5;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        /* Screen Toolbar */
        .print-toolbar {
            position: sticky;
            top: 0;
            z-index: 100;
            background: #FFFFFF;
            border-bottom: 2px solid var(--border-color);
            padding: 12px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
        }

        .toolbar-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .toolbar-badge {
            background: var(--primary-light);
            color: var(--primary);
            font-weight: 700;
            font-size: 9pt;
            padding: 4px 10px;
            border-radius: 9999px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .toolbar-actions {
            display: flex;
            gap: 10px;
        }

        .btn-print {
            background: var(--primary);
            color: #FFFFFF;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 700;
            font-size: 10.5pt;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: background 0.2s;
        }

        .btn-print:hover {
            background: var(--primary-dark);
        }

        .btn-close {
            background: #FFFFFF;
            color: var(--text-muted);
            border: 1px solid var(--border-color);
            padding: 10px 16px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 10.5pt;
            cursor: pointer;
        }

        .btn-close:hover {
            background: #F4F6F4;
        }

        /* Document Container (A4 preview) */
        .page-sheet {
            background: #FFFFFF;
            width: 210mm;
            min-height: 297mm;
            margin: 24px auto;
            padding: 18mm 18mm 20mm 18mm;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.1);
            position: relative;
        }

        /* Kop / Header Dokumen Resmi */
        .doc-header {
            border-bottom: 3px double var(--primary);
            padding-bottom: 14px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 18px;
        }

        .doc-logo {
            width: 58px;
            height: 58px;
            background: var(--primary);
            color: #FFFFFF;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            font-weight: 800;
            font-size: 22pt;
            letter-spacing: -1px;
        }

        .doc-title-block {
            flex-grow: 1;
        }

        .doc-title-institution {
            font-size: 10pt;
            font-weight: 700;
            text-transform: uppercase;
            color: var(--primary);
            letter-spacing: 0.8px;
            margin-bottom: 2px;
        }

        .doc-title-main {
            font-size: 15pt;
            font-weight: 800;
            color: var(--text-main);
            line-height: 1.25;
            letter-spacing: -0.3px;
        }

        .doc-title-sub {
            font-size: 9.5pt;
            color: var(--text-muted);
            margin-top: 3px;
        }

        /* Metadata Grid */
        .meta-card {
            background: var(--bg-gray);
            border: 1px solid var(--border-color);
            border-radius: 10px;
            padding: 12px 16px;
            margin-bottom: 22px;
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 14px;
        }

        .meta-item {
            display: flex;
            flex-direction: column;
        }

        .meta-label {
            font-size: 8.5pt;
            text-transform: uppercase;
            font-weight: 700;
            color: var(--text-muted);
            letter-spacing: 0.5px;
            margin-bottom: 2px;
        }

        .meta-value {
            font-size: 11pt;
            font-weight: 700;
            color: var(--text-main);
        }

        /* Section Styling */
        .report-section {
            margin-bottom: 24px;
            page-break-inside: avoid;
        }

        .section-header {
            display: flex;
            align-items: baseline;
            justify-content: space-between;
            border-bottom: 2px solid var(--border-color);
            padding-bottom: 6px;
            margin-bottom: 12px;
        }

        .section-title {
            font-size: 12pt;
            font-weight: 800;
            color: var(--primary);
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .section-note {
            font-size: 8.5pt;
            color: var(--text-muted);
            font-style: italic;
        }

        /* Tables */
        .report-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9.5pt;
            margin-bottom: 8px;
        }

        .report-table th,
        .report-table td {
            padding: 8px 10px;
            text-align: left;
            border: 1px solid var(--border-color);
        }

        .report-table th {
            background-color: var(--primary-light);
            color: var(--primary-dark);
            font-weight: 700;
            font-size: 9pt;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .report-table tr:nth-child(even) td {
            background-color: #FAFCFA;
        }

        .report-table td.text-center,
        .report-table th.text-center {
            text-align: center;
        }

        .report-table td.text-right,
        .report-table th.text-right {
            text-align: right;
        }

        .badge-pill {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 9999px;
            font-size: 8pt;
            font-weight: 700;
            text-align: center;
        }

        .badge-success {
            background: #D8EFE4;
            color: #0E5039;
        }

        .badge-warning {
            background: #FEF3D6;
            color: #8C5311;
        }

        .badge-neutral {
            background: #E8ECE9;
            color: #405048;
        }

        /* Progress Mini Bar */
        .progress-track {
            background: #E3ECE7;
            height: 7px;
            border-radius: 9999px;
            overflow: hidden;
            display: inline-block;
            width: 80px;
            vertical-align: middle;
            margin-right: 6px;
        }

        .progress-fill {
            background: var(--primary);
            height: 100%;
            border-radius: 9999px;
        }

        /* Grid 2 Columns for SQ-FFQ & Tempat */
        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        /* Lembar Pengesahan / Tanda Tangan */
        .signature-block {
            margin-top: 36px;
            padding-top: 14px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            page-break-inside: avoid;
        }

        .signature-box {
            text-align: center;
            font-size: 10pt;
        }

        .signature-date {
            margin-bottom: 4px;
            color: var(--text-muted);
            font-size: 9pt;
        }

        .signature-role {
            font-weight: 700;
            margin-bottom: 60px;
        }

        .signature-name {
            font-weight: 700;
            text-decoration: underline;
            color: var(--text-main);
        }

        .signature-nip {
            font-size: 9pt;
            color: var(--text-muted);
            margin-top: 2px;
        }

        /* Print Media Styles */
        @media print {
            body {
                background: #FFFFFF !important;
                font-size: 10pt;
            }

            .print-toolbar {
                display: none !important;
            }

            .page-sheet {
                width: 100% !important;
                min-height: auto !important;
                margin: 0 !important;
                padding: 0 !important;
                box-shadow: none !important;
            }

            @page {
                size: A4 portrait;
                margin: 14mm 16mm 16mm 16mm;
            }

            .report-section {
                page-break-inside: avoid;
            }

            .page-break {
                page-break-before: always;
            }
        }
    </style>
</head>
<body>

    {{-- Screen Action Toolbar (Hidden during print) --}}
    <div class="print-toolbar">
        <div class="toolbar-info">
            <span class="toolbar-badge">Pratinjau Cetak / PDF</span>
            <span style="font-weight: 600; color: var(--text-main);">
                Laporan Hasil Intervensi & Monitoring Sarapan
                @if ($sekolahTerpilih)
                    — {{ $sekolahTerpilih->nama_sekolah }}
                @endif
                @if ($kelasTerpilih)
                    (Kelas {{ $kelasTerpilih->nama_kelas }})
                @else
                    — Semua Kelas
                @endif
            </span>
        </div>
        <div class="toolbar-actions">
            <button type="button" class="btn-close" onclick="window.close()">✕ Tutup</button>
            <button type="button" class="btn-print" onclick="window.print()">
                <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4H7v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                </svg>
                Cetak / Simpan PDF
            </button>
        </div>
    </div>

    {{-- Printable A4 Sheet --}}
    <div class="page-sheet">

        {{-- Kop Resmi Penelitian --}}
        <header class="doc-header">
            <div class="doc-logo">BP</div>
            <div class="doc-title-block">
                <div class="doc-title-institution">{{ $sekolahTerpilih ? $sekolahTerpilih->nama_sekolah : 'Program Studi Gizi' }} · Laporan Penelitian Intervensi Gizi Siswa</div>
                <h1 class="doc-title-main">DIGITAL BREAKFAST PLANNER — REKAP PERUBAHAN SIKAP & SARAPAN</h1>
                <div class="doc-title-sub">Monitoring Rencana Sarapan Harian, Food Record, Pengetahuan Gizi & Konsumsi SQ-FFQ</div>
            </div>
        </header>

        {{-- Informasi Metadata Laporan --}}
        <div class="meta-card" style="grid-template-columns: repeat(5, 1fr);">
            <div class="meta-item">
                <span class="meta-label">Sekolah</span>
                <span class="meta-value">{{ $sekolahTerpilih ? $sekolahTerpilih->nama_sekolah : 'Semua Sekolah' }}</span>
            </div>
            <div class="meta-item">
                <span class="meta-label">Filter Kelas</span>
                <span class="meta-value">{{ $kelasTerpilih ? $kelasTerpilih->nama_kelas : 'Semua Kelas' }}</span>
            </div>
            <div class="meta-item">
                <span class="meta-label">Total Responden</span>
                <span class="meta-value">{{ $totalSiswaTerdata }} Siswa</span>
            </div>
            <div class="meta-item">
                <span class="meta-label">Petugas / Pemeriksa</span>
                <span class="meta-value">{{ $user->nama_lengkap ?? 'Administrator' }}</span>
            </div>
            <div class="meta-item">
                <span class="meta-label">Tanggal Cetak</span>
                <span class="meta-value">{{ $tanggalCetak }} WIB</span>
            </div>
        </div>

        {{-- Bagian 1: Kecenderungan Sarapan (Sikap Konatif) & Pengetahuan Gizi per Kelas --}}
        <section class="report-section">
            <div class="section-header">
                <h2 class="section-title">1. Pengetahuan Gizi & Kecenderungan Sarapan Mingguan (Sikap Konatif) per Kelas</h2>
                <span class="section-note">Sumber data: Laporan Kelas View (Hasil Kuesioner & Pemantauan Sikap Konatif Mingguan)</span>
            </div>

            <table class="report-table">
                <thead>
                    <tr>
                        <th style="width: 25%;">Nama Kelas</th>
                        <th class="text-center" style="width: 12%;">Siswa</th>
                        <th class="text-center" style="width: 15%;">Rata Pretest</th>
                        <th class="text-center" style="width: 15%;">Rata Posttest</th>
                        <th class="text-center" style="width: 11%;">Sesuai M1</th>
                        <th class="text-center" style="width: 11%;">Sesuai M2</th>
                        <th class="text-center" style="width: 11%;">Sesuai M3</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($laporanKelas as $row)
                        <tr>
                            <td>
                                <strong>{{ $row->nama_kelas }}</strong>
                                @if (!$sekolahTerpilih && !empty($row->nama_sekolah))
                                    <div style="font-size: 8pt; color: var(--text-muted);">{{ $row->nama_sekolah }}</div>
                                @endif
                            </td>
                            <td class="text-center">{{ $row->jumlah_siswa ?? 0 }}</td>
                            <td class="text-center">{{ $row->skor_pretest_rata !== null ? number_format($row->skor_pretest_rata, 1) : '-' }}</td>
                            <td class="text-center">{{ $row->skor_posttest_rata !== null ? number_format($row->skor_posttest_rata, 1) : '-' }}</td>
                            <td class="text-center">
                                <span class="badge-pill {{ ($row->persen_patuh_m1 ?? 0) >= 70 ? 'badge-success' : 'badge-warning' }}">
                                    {{ $row->persen_patuh_m1 !== null ? number_format($row->persen_patuh_m1, 0) . '%' : '-' }}
                                </span>
                            </td>
                            <td class="text-center">
                                <span class="badge-pill {{ ($row->persen_patuh_m2 ?? 0) >= 70 ? 'badge-success' : 'badge-warning' }}">
                                    {{ $row->persen_patuh_m2 !== null ? number_format($row->persen_patuh_m2, 0) . '%' : '-' }}
                                </span>
                            </td>
                            <td class="text-center">
                                <span class="badge-pill {{ ($row->persen_patuh_m3 ?? 0) >= 70 ? 'badge-success' : 'badge-warning' }}">
                                    {{ $row->persen_patuh_m3 !== null ? number_format($row->persen_patuh_m3, 0) . '%' : '-' }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center" style="padding: 16px; color: var(--text-muted);">
                                Belum ada data kelas yang tercatat pada rentang ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </section>

        {{-- Bagian 2 & 3: Pola Konsumsi SQ-FFQ & Tempat Sarapan (Grid 2 Kolom) --}}
        <div class="grid-2">
            {{-- Bagian 2: Pola Konsumsi Pangan SQ-FFQ --}}
            <section class="report-section">
                <div class="section-header">
                    <h2 class="section-title">2. Pola Konsumsi SQ-FFQ</h2>
                    <span class="section-note">≥ 3x per minggu</span>
                </div>

                <table class="report-table">
                    <thead>
                        <tr>
                            <th>Kelompok Pangan</th>
                            <th class="text-center" style="width: 25%;">Persentase</th>
                            <th class="text-right" style="width: 25%;">Responden</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($polaKonsumsiList as $pola)
                            <tr>
                                <td>
                                    <strong>{{ $pola['kode'] }}.</strong> {{ $pola['nama'] }}
                                </td>
                                <td class="text-center">
                                    <div class="progress-track">
                                        <div class="progress-fill" style="width: {{ $pola['persen'] }}%;"></div>
                                    </div>
                                    <span style="font-weight: 700; font-size: 8.5pt;">{{ $pola['persen'] }}%</span>
                                </td>
                                <td class="text-right" style="font-size: 8.5pt; color: var(--text-muted);">
                                    {{ $pola['jumlah_sering'] }} / {{ $pola['total_responden'] }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center" style="color: var(--text-muted);">Belum ada data SQ-FFQ</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </section>

            {{-- Bagian 3: Distribusi Tempat Sarapan --}}
            <section class="report-section">
                <div class="section-header">
                    <h2 class="section-title">3. Distribusi Tempat Sarapan</h2>
                    <span class="section-note">Food Record Siswa</span>
                </div>

                <table class="report-table">
                    <thead>
                        <tr>
                            <th>Lokasi Sarapan</th>
                            <th class="text-center" style="width: 25%;">Persentase</th>
                            <th class="text-right" style="width: 25%;">Frekuensi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($tempatSarapanList as $tempat)
                            <tr>
                                <td><strong>{{ $tempat['nama'] }}</strong></td>
                                <td class="text-center">
                                    <div class="progress-track">
                                        <div class="progress-fill" style="width: {{ $tempat['persen'] }}%;"></div>
                                    </div>
                                    <span style="font-weight: 700; font-size: 8.5pt;">{{ $tempat['persen'] }}%</span>
                                </td>
                                <td class="text-right" style="font-size: 8.5pt; color: var(--text-muted);">
                                    {{ $tempat['jumlah'] }} kali
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center" style="color: var(--text-muted);">Belum ada data tempat makan</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </section>
        </div>

        {{-- Bagian 4: Rekap Skor Pangan SQ-FFQ Individu --}}
        <section class="report-section">
            <div class="section-header">
                <h2 class="section-title">4. Rekapitulasi Skor Pangan SQ-FFQ Responden</h2>
                <span class="section-note">Hasil wawancara SQ-FFQ oleh petugas gizi</span>
            </div>

            <table class="report-table">
                <thead>
                    <tr>
                        <th style="width: 8%;" class="text-center">No</th>
                        <th style="width: 32%;">Nama Siswa</th>
                        <th style="width: 20%;">Kelas</th>
                        <th class="text-center" style="width: 15%;">Tahap</th>
                        <th class="text-center" style="width: 12%;">Skor Total</th>
                        <th class="text-center" style="width: 13%;">Kategori</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($sqffqSkorList as $index => $skorRow)
                        <tr>
                            <td class="text-center">{{ $index + 1 }}</td>
                            <td><strong>{{ $skorRow->siswa?->nama_lengkap ?? '-' }}</strong></td>
                            <td>
                                {{ $skorRow->siswa?->kelas?->nama_kelas ?? '-' }}
                                @if (!$sekolahTerpilih && $skorRow->siswa?->kelas?->sekolah)
                                    <div style="font-size: 8pt; color: var(--text-muted);">{{ $skorRow->siswa->kelas->sekolah->nama_sekolah }}</div>
                                @endif
                            </td>
                            <td class="text-center">
                                <span class="badge-pill badge-neutral">{{ $skorRow->tahap }}</span>
                            </td>
                            <td class="text-center"><strong>{{ number_format($skorRow->skor_total, 1) }}</strong></td>
                            <td class="text-center">
                                <span class="badge-pill {{ $skorRow->kategori_skor === 'BAIK' ? 'badge-success' : 'badge-warning' }}">
                                    {{ $skorRow->kategori_skor ?? '-' }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center" style="padding: 14px; color: var(--text-muted);">
                                Belum ada siswa dengan SQ-FFQ selesai yang terdata.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </section>

        {{-- Lembar Pengesahan / Tanda Tangan --}}
        <div class="signature-block">
            <div class="signature-box">
                <div class="signature-date">&nbsp;</div>
                <div class="signature-role">Mengetahui,<br>Koordinator Program / Guru Pendamping</div>
                <div class="signature-name">( .................................................... )</div>
                <div class="signature-nip">NIP. ....................................................</div>
            </div>

            <div class="signature-box">
                <div class="signature-date">Jakarta, {{ \Carbon\Carbon::now()->translatedFormat('d F Y') }}</div>
                <div class="signature-role">Petugas Peneliti Gizi,</div>
                <div class="signature-name">{{ $user->nama_lengkap ?? 'Administrator' }}</div>
                <div class="signature-nip">NIM / NIP. {{ $user->pengguna_id ? 'ID-' . str_pad($user->pengguna_id, 5, '0', STR_PAD_LEFT) : 'Peneliti Gizi' }}</div>
            </div>
        </div>

    </div>

</body>
</html>

