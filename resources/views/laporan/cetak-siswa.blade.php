<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Monitoring Siswa - {{ $siswa->pengguna?->nama_lengkap ?? $siswa->nama_lengkap }}</title>
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

        /* Screen Action Toolbar */
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

        /* Document Container (A4) */
        .page-sheet {
            background: #FFFFFF;
            width: 210mm;
            min-height: 297mm;
            margin: 24px auto;
            padding: 18mm 18mm 20mm 18mm;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.1);
            position: relative;
        }

        /* Header Dokumen */
        .doc-header {
            border-bottom: 3px double var(--primary);
            padding-bottom: 14px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .doc-logo {
            width: 54px;
            height: 54px;
            background: var(--primary);
            color: #FFFFFF;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            font-weight: 800;
            font-size: 20pt;
        }

        .doc-title-block {
            flex-grow: 1;
        }

        .doc-title-institution {
            font-size: 9.5pt;
            font-weight: 700;
            text-transform: uppercase;
            color: var(--primary);
            letter-spacing: 0.8px;
            margin-bottom: 2px;
        }

        .doc-title-main {
            font-size: 14.5pt;
            font-weight: 800;
            color: var(--text-main);
            line-height: 1.25;
        }

        .doc-title-sub {
            font-size: 9.5pt;
            color: var(--text-muted);
            margin-top: 2px;
        }

        /* Student Profile Card */
        .profile-card {
            background: var(--bg-gray);
            border: 1px solid var(--border-color);
            border-radius: 10px;
            padding: 14px 18px;
            margin-bottom: 22px;
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 14px;
        }

        .profile-item {
            display: flex;
            flex-direction: column;
        }

        .profile-label {
            font-size: 8.5pt;
            text-transform: uppercase;
            font-weight: 700;
            color: var(--text-muted);
            letter-spacing: 0.5px;
            margin-bottom: 2px;
        }

        .profile-value {
            font-size: 11pt;
            font-weight: 700;
            color: var(--text-main);
        }

        /* Section Styling */
        .report-section {
            margin-bottom: 22px;
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
        }

        .section-note {
            font-size: 8.5pt;
            color: var(--text-muted);
            font-style: italic;
        }

        /* Summary Stats Cards */
        .stats-row {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
            margin-bottom: 14px;
        }

        .stat-box {
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 10px 14px;
            text-align: center;
            background: #FFFFFF;
        }

        .stat-box-label {
            font-size: 8.5pt;
            color: var(--text-muted);
            font-weight: 600;
            margin-bottom: 4px;
        }

        .stat-box-value {
            font-size: 15pt;
            font-weight: 800;
            color: var(--primary);
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
            padding: 7px 9px;
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

        .badge-danger {
            background: #FEE4E2;
            color: #B42318;
        }

        .badge-neutral {
            background: #E8ECE9;
            color: #405048;
        }

        /* Signature block */
        .signature-block {
            margin-top: 32px;
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

        .signature-role {
            font-weight: 700;
            margin-bottom: 60px;
        }

        .signature-name {
            font-weight: 700;
            text-decoration: underline;
        }

        .signature-nip {
            font-size: 9pt;
            color: var(--text-muted);
            margin-top: 2px;
        }

        @media print {
            body {
                background: #FFFFFF !important;
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
        }
    </style>
</head>
<body>

    {{-- Toolbar --}}
    <div class="print-toolbar">
        <div class="toolbar-info">
            <span class="toolbar-badge">Pratinjau Cetak / PDF</span>
            <span style="font-weight: 600; color: var(--text-main);">
                Laporan Monitoring Individu — {{ $siswa->nama_lengkap }} ({{ $siswa->kelas?->nama_kelas }} · {{ $siswa->kelas?->sekolah?->nama_sekolah ?? '-' }})
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

    {{-- Sheet --}}
    <div class="page-sheet">

        <header class="doc-header">
            <div class="doc-logo">BP</div>
            <div class="doc-title-block">
                <div class="doc-title-institution">{{ $siswa->kelas?->sekolah?->nama_sekolah ?? 'Program Studi Gizi' }} · Formulir Monitoring Siswa</div>
                <h1 class="doc-title-main">DIGITAL BREAKFAST PLANNER — LEMBAR REKAP INDIVIDU</h1>
                <div class="doc-title-sub">Laporan Evaluasi Rencana Sarapan, Food Record & Frekuensi Pangan Siswa</div>
            </div>
        </header>

        {{-- Profil Siswa --}}
        <div class="profile-card" style="grid-template-columns: repeat(5, 1fr);">
            <div class="profile-item">
                <span class="profile-label">Nama Siswa</span>
                <span class="profile-value">{{ $siswa->nama_lengkap }}</span>
            </div>
            <div class="profile-item">
                <span class="profile-label">Sekolah</span>
                <span class="profile-value">{{ $siswa->kelas?->sekolah?->nama_sekolah ?? '-' }}</span>
            </div>
            <div class="profile-item">
                <span class="profile-label">Kelas / ID</span>
                <span class="profile-value">{{ $siswa->kelas?->nama_kelas }} · #{{ $siswa->siswa_id }}</span>
            </div>
            <div class="profile-item">
                <span class="profile-label">Status Akun</span>
                <span class="profile-value">{{ $siswa->statusAkun?->nama ?? 'Terverifikasi' }}</span>
            </div>
            <div class="profile-item">
                <span class="profile-label">Tanggal Cetak</span>
                <span class="profile-value">{{ $tanggalCetak }} WIB</span>
            </div>
        </div>

        {{-- Ringkasan Pengetahuan & Kepatuhan --}}
        <div class="stats-row">
            <div class="stat-box">
                <div class="stat-box-label">Skor Pretest</div>
                <div class="stat-box-value">{{ $skorPengetahuan?->skor_pretest !== null ? number_format($skorPengetahuan->skor_pretest, 0) : '-' }}</div>
            </div>
            <div class="stat-box">
                <div class="stat-box-label">Skor Posttest</div>
                <div class="stat-box-value">{{ $skorPengetahuan?->skor_posttest !== null ? number_format($skorPengetahuan->skor_posttest, 0) : '-' }}</div>
            </div>
            <div class="stat-box">
                <div class="stat-box-label">Hari Sarapan Sesuai</div>
                <div class="stat-box-value">{{ $totalHariSesuai }} / {{ $totalHariBerjalan }}</div>
            </div>
            <div class="stat-box">
                <div class="stat-box-label">Rata Kepatuhan</div>
                <div class="stat-box-value">{{ $persenKepatuhanTotal }}%</div>
            </div>
        </div>

        {{-- Bagian 1: Kepatuhan Mingguan --}}
        <section class="report-section">
            <div class="section-header">
                <h2 class="section-title">1. Kepatuhan Rencana Sarapan Mingguan</h2>
                <span class="section-note">Evaluasi perbandingan Rencana Sarapan vs Food Record Aktual</span>
            </div>

            <table class="report-table">
                <thead>
                    <tr>
                        <th style="width: 25%;">Minggu Program</th>
                        <th class="text-center" style="width: 18%;">Hari Berjalan</th>
                        <th class="text-center" style="width: 18%;">Hari Tercatat</th>
                        <th class="text-center" style="width: 18%;">Hari Sesuai</th>
                        <th class="text-center" style="width: 21%;">Persentase Kepatuhan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($kepatuhanMingguan as $km)
                        <tr>
                            <td><strong>Minggu ke-{{ $km->minggu_ke }}</strong></td>
                            <td class="text-center">{{ $km->hari_berjalan }} hari</td>
                            <td class="text-center">{{ $km->hari_tercatat }} hari</td>
                            <td class="text-center">{{ $km->hari_sesuai }} hari</td>
                            <td class="text-center">
                                <span class="badge-pill {{ ($km->persen_kepatuhan ?? 0) >= 70 ? 'badge-success' : 'badge-warning' }}">
                                    {{ $km->persen_kepatuhan !== null ? number_format($km->persen_kepatuhan, 0) . '%' : '-' }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center" style="color: var(--text-muted); padding: 12px;">
                                Belum ada data kepatuhan mingguan yang tercatat.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </section>

        {{-- Bagian 2: Log Food Record Siswa --}}
        <section class="report-section">
            <div class="section-header">
                <h2 class="section-title">2. Catatan Food Record Sarapan Harian</h2>
                <span class="section-note">
                    @if ($mingguFilter)
                        Difilter: Minggu ke-{{ $mingguFilter }}
                    @else
                        Semua Catatan Aktual
                    @endif
                </span>
            </div>

            <table class="report-table">
                <thead>
                    <tr>
                        <th style="width: 14%;">Tanggal</th>
                        <th style="width: 10%;">Waktu</th>
                        <th style="width: 14%;">Tempat</th>
                        <th style="width: 20%;">Menu / Makanan</th>
                        <th style="width: 20%;">Bahan (URT/Gram)</th>
                        <th class="text-center" style="width: 12%;">Realisasi</th>
                        <th style="width: 10%;">Ket</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($foodRecordList as $fr)
                        <tr>
                            <td>
                                <strong>{{ \Carbon\Carbon::parse($fr->tanggal)->translatedFormat('d/m/Y') }}</strong>
                                <div style="font-size: 8pt; color: var(--text-muted);">M-{{ $fr->minggu_ke }} H-{{ $fr->hari_ke }}</div>
                            </td>
                            <td>{{ $fr->jam_makan ? substr($fr->jam_makan, 0, 5) : '-' }}</td>
                            <td>{{ $fr->tempat_nama ?? '-' }}</td>
                            <td><strong>{{ $fr->nama_makanan ?? '-' }}</strong></td>
                            <td>
                                {{ $fr->bahan_nama ?? '-' }}
                                @if ($fr->jumlah_urt || $fr->nama_urt || $fr->berat_gram)
                                    <div style="font-size: 8pt; color: var(--text-muted);">
                                        {{ $fr->jumlah_urt ? (float)$fr->jumlah_urt : '' }} {{ $fr->nama_urt ?? '' }}
                                        {{ $fr->berat_gram ? "({$fr->berat_gram}g)" : '' }}
                                    </div>
                                @endif
                            </td>
                            <td class="text-center">
                                <span class="badge-pill {{ $fr->status_realisasi_kode === 'SESUAI' ? 'badge-success' : ($fr->status_realisasi_kode === 'SEBAGIAN' ? 'badge-warning' : 'badge-danger') }}">
                                    {{ $fr->status_realisasi_kode ?? '-' }}
                                </span>
                            </td>
                            <td style="font-size: 8.5pt;">{{ $fr->keterangan ?: '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center" style="color: var(--text-muted); padding: 14px;">
                                Belum ada catatan food record untuk siswa ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </section>

        {{-- Bagian 3: Ringkasan Pola Konsumsi SQ-FFQ --}}
        @if ($sqffqRekapList->isNotEmpty())
            <section class="report-section">
                <div class="section-header">
                    <h2 class="section-title">3. Ringkasan SQ-FFQ per Kelompok Bahan Makanan</h2>
                    <span class="section-note">Frekuensi konsumsi bahan pangan sering (≥ 3x/minggu)</span>
                </div>

                <table class="report-table">
                    <thead>
                        <tr>
                            <th style="width: 10%;">Kode</th>
                            <th style="width: 50%;">Kelompok Bahan Makanan</th>
                            <th class="text-center" style="width: 20%;">Bahan Sering Dikonsumsi</th>
                            <th class="text-center" style="width: 20%;">Total Bahan Dinilai</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($sqffqRekapList as $rekap)
                            <tr>
                                <td><strong>{{ $rekap->kelompok_kode }}</strong></td>
                                <td>{{ $rekap->kelompok_nama }}</td>
                                <td class="text-center">
                                    <span class="badge-pill {{ $rekap->jumlah_bahan_sering > 0 ? 'badge-success' : 'badge-neutral' }}">
                                        {{ $rekap->jumlah_bahan_sering }} bahan
                                    </span>
                                </td>
                                <td class="text-center">{{ $rekap->total_bahan }} bahan</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </section>
        @endif

        {{-- Tanda Tangan --}}
        <div class="signature-block">
            <div class="signature-box">
                <div class="signature-role">Mengetahui,<br>Siswa / Responden</div>
                <div class="signature-name">{{ $siswa->nama_lengkap }}</div>
                <div class="signature-nip">Kelas {{ $siswa->kelas?->nama_kelas }}</div>
            </div>

            <div class="signature-box">
                <div class="signature-role">Jakarta, {{ \Carbon\Carbon::now()->translatedFormat('d F Y') }}<br>Petugas Peneliti Gizi,</div>
                <div class="signature-name">{{ $user->nama_lengkap ?? 'Administrator' }}</div>
                <div class="signature-nip">NIM / NIP. {{ $user->pengguna_id ? 'ID-' . str_pad($user->pengguna_id, 5, '0', STR_PAD_LEFT) : 'Peneliti Gizi' }}</div>
            </div>
        </div>

    </div>

</body>
</html>
