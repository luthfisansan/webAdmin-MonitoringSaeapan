<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Rekap Kelas {{ $kelas->nama_kelas }} — {{ $kelas->sekolah?->nama_sekolah ?? 'Digital Breakfast Planner' }}</title>
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
            font-size: 9.5pt;
            line-height: 1.4;
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
            letter-spacing: 0.5px;
        }

        .toolbar-title {
            font-size: 11pt;
            font-weight: 700;
            color: var(--text-main);
        }

        .toolbar-actions {
            display: flex;
            gap: 10px;
        }

        .btn-print {
            background: var(--primary);
            color: #FFFFFF;
            border: none;
            padding: 9px 18px;
            border-radius: 8px;
            font-weight: 700;
            font-size: 10pt;
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
            padding: 9px 16px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 10pt;
            cursor: pointer;
        }

        .btn-close:hover {
            background: #F4F6F4;
        }

        /* Document Container (A4 Landscape) */
        .page-sheet {
            background: #FFFFFF;
            width: 297mm;
            min-height: 210mm;
            margin: 20px auto;
            padding: 14mm 16mm;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.1);
            position: relative;
        }

        /* Header Dokumen */
        .doc-header {
            border-bottom: 2.5px double var(--primary);
            padding-bottom: 12px;
            margin-bottom: 14px;
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
        }

        .doc-header-main {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .doc-logo-emblem {
            width: 44px;
            height: 44px;
            background: var(--primary);
            color: #FFFFFF;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20pt;
            font-weight: 800;
            flex-shrink: 0;
        }

        .doc-title {
            font-size: 14pt;
            font-weight: 800;
            color: var(--primary);
            letter-spacing: -0.02em;
            text-transform: uppercase;
        }

        .doc-subtitle {
            font-size: 10.5pt;
            font-weight: 700;
            color: var(--text-main);
            margin-top: 1px;
        }

        .doc-subinfo {
            font-size: 8.5pt;
            color: var(--text-muted);
            margin-top: 2px;
        }

        .doc-header-meta {
            text-align: right;
            font-size: 8.5pt;
            color: var(--text-muted);
        }

        .meta-school {
            font-size: 11pt;
            font-weight: 700;
            color: var(--primary-dark);
        }

        /* Summary Badges Grid */
        .summary-strip {
            display: grid;
            grid-template-columns: repeat(6, 1fr);
            gap: 8px;
            margin-bottom: 14px;
        }

        .summary-card {
            background: var(--primary-light);
            border: 1px solid var(--border-dark);
            border-radius: 6px;
            padding: 8px 10px;
            text-align: center;
        }

        .summary-label {
            font-size: 7.5pt;
            text-transform: uppercase;
            font-weight: 700;
            color: var(--primary);
            letter-spacing: 0.4px;
        }

        .summary-value {
            font-size: 13pt;
            font-weight: 800;
            color: var(--text-main);
            margin-top: 2px;
        }

        .summary-sub {
            font-size: 7.5pt;
            color: var(--text-muted);
        }

        /* Main Table */
        .report-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8.5pt;
            margin-bottom: 14px;
        }

        .report-table th,
        .report-table td {
            border: 1px solid var(--border-color);
            padding: 5px 7px;
            vertical-align: middle;
        }

        .report-table th {
            background: #F2F7F4;
            color: var(--text-main);
            font-weight: 700;
            font-size: 8pt;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .report-table tbody tr:nth-child(even) {
            background-color: #FAFCFA;
        }

        .report-table tfoot td {
            background: #E8F3EE;
            font-weight: 700;
            border-top: 2px solid var(--primary);
        }

        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-bold { font-weight: 700; }

        .badge-pill {
            display: inline-block;
            font-size: 7pt;
            font-weight: 700;
            padding: 2px 6px;
            border-radius: 4px;
            text-transform: uppercase;
        }

        .badge-success { background: #D2EADB; color: #17624B; }
        .badge-warning { background: #FDE8D0; color: #B35C00; }
        .badge-danger { background: #FDE2E2; color: #B91C1C; }
        .badge-neutral { background: #EAEFEA; color: #55655D; }

        .diff-pos { color: #17624B; font-weight: 700; }
        .diff-neg { color: #B91C1C; font-weight: 700; }

        /* Notes Box */
        .notes-box {
            background: #FAFBF9;
            border: 1px dashed var(--border-dark);
            border-radius: 6px;
            padding: 8px 12px;
            font-size: 8pt;
            color: var(--text-muted);
            margin-bottom: 16px;
            line-height: 1.4;
        }

        .notes-box strong {
            color: var(--text-main);
        }

        /* Signatures */
        .signature-block {
            display: flex;
            justify-content: space-between;
            margin-top: 14px;
            page-break-inside: avoid;
        }

        .signature-box {
            width: 220px;
            text-align: center;
            font-size: 8.5pt;
        }

        .signature-role {
            font-weight: 600;
            color: var(--text-main);
            margin-bottom: 45px;
        }

        .signature-name {
            font-weight: 700;
            border-bottom: 1px solid var(--text-main);
            padding-bottom: 2px;
            color: var(--text-main);
        }

        .signature-nip {
            font-size: 8pt;
            color: var(--text-muted);
            margin-top: 2px;
        }

        /* Print Media Styles */
        @media print {
            body {
                background: #FFFFFF;
                font-size: 8.5pt;
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
                size: A4 landscape;
                margin: 10mm 12mm;
            }

            .report-table th {
                background: #E8F3EE !important;
                -webkit-print-color-adjust: exact;
            }

            .summary-card {
                background: #F4F8F6 !important;
                border: 1px solid #B0C4B8 !important;
                -webkit-print-color-adjust: exact;
            }
        }
    </style>
</head>
<body>

    {{-- Screen Floating Toolbar --}}
    <div class="print-toolbar">
        <div class="toolbar-info">
            <span class="toolbar-badge">Cetak Lembar Rekap Kelas</span>
            <div class="toolbar-title">{{ $kelas->nama_kelas }} · {{ $kelas->sekolah?->nama_sekolah ?? 'Sekolah' }} (Total {{ count($siswaRows) }} Siswa)</div>
        </div>
        <div class="toolbar-actions">
            <button onclick="window.close()" class="btn-close">Kembali</button>
            <button onclick="window.print()" class="btn-print">
                🖨️ Cetak / Simpan PDF
            </button>
        </div>
    </div>

    {{-- A4 Landscape Sheet --}}
    <div class="page-sheet">

        {{-- Kop Resmi Dokumen --}}
        <div class="doc-header">
            <div class="doc-header-main">
                <div class="doc-logo-emblem">🥣</div>
                <div>
                    <h1 class="doc-title">DIGITAL BREAKFAST PLANNER</h1>
                    <div class="doc-subtitle">REKAPITULASI PENGETAHUAN GIZI & SIKAP KONATIF SARAPAN SISWA PER KELAS</div>
                    <div class="doc-subinfo">Monitoring dan Evaluasi Intervensi Sarapan Sehat Mahasiswa Ilmu Gizi</div>
                </div>
            </div>
            <div class="doc-header-meta">
                <div class="meta-school">{{ $kelas->sekolah?->nama_sekolah ?? 'Nama Sekolah' }}</div>
                <div><strong>Kelas:</strong> {{ $kelas->nama_kelas }} (TA {{ $kelas->tahun_ajaran ?? '-' }})</div>
                <div><strong>Tanggal Cetak:</strong> {{ $tanggalCetak }} WIB</div>
                <div><strong>Petugas:</strong> {{ $user->nama_lengkap ?? 'Administrator' }}</div>
            </div>
        </div>

        {{-- Strip Ringkasan Kelas (KPI) --}}
        <div class="summary-strip">
            <div class="summary-card">
                <div class="summary-label">Total Siswa</div>
                <div class="summary-value">{{ $ringkasan['total_siswa'] }}</div>
                <div class="summary-sub">Responden kelas</div>
            </div>
            <div class="summary-card">
                <div class="summary-label">Rata Pretest</div>
                <div class="summary-value">{{ $ringkasan['rata_pretest'] !== null ? $ringkasan['rata_pretest'] : '-' }}</div>
                <div class="summary-sub">Skor awal (0–100)</div>
            </div>
            <div class="summary-card">
                <div class="summary-label">Rata Posttest</div>
                <div class="summary-value">{{ $ringkasan['rata_posttest'] !== null ? $ringkasan['rata_posttest'] : '-' }}</div>
                <div class="summary-sub">Skor akhir (0–100)</div>
            </div>
            <div class="summary-card">
                <div class="summary-label">Peningkatan (Δ)</div>
                <div class="summary-value" style="color: {{ ($ringkasan['rata_selisih'] ?? 0) >= 0 ? '#17624B' : '#B91C1C' }};">
                    @if ($ringkasan['rata_selisih'] !== null)
                        {{ $ringkasan['rata_selisih'] >= 0 ? '+' . $ringkasan['rata_selisih'] : $ringkasan['rata_selisih'] }}
                    @else
                        -
                    @endif
                </div>
                <div class="summary-sub">Rata-rata selisih</div>
            </div>
            <div class="summary-card">
                <div class="summary-label">Sikap Konatif</div>
                <div class="summary-value">{{ $ringkasan['rata_kecenderungan'] !== null ? $ringkasan['rata_kecenderungan'] . '%' : '-' }}</div>
                <div class="summary-sub">Kesesuaian M1–M3</div>
            </div>
            <div class="summary-card">
                <div class="summary-label">Rata SQ-FFQ</div>
                <div class="summary-value">{{ $ringkasan['rata_sqffq'] !== null ? $ringkasan['rata_sqffq'] : '-' }}</div>
                <div class="summary-sub">Skor pangan awal</div>
            </div>
        </div>

        {{-- Tabel Data Siswa Perorangan --}}
        <table class="report-table">
            <thead>
                <tr>
                    <th rowspan="2" class="text-center" style="width: 28px;">No</th>
                    <th rowspan="2" style="width: 170px;">Nama Lengkap Siswa</th>
                    <th rowspan="2" class="text-center" style="width: 32px;">L/P</th>
                    <th rowspan="2" style="width: 100px;">Status Akun</th>
                    <th colspan="3" class="text-center" style="background: #E5EFE9;">Pengetahuan Gizi</th>
                    <th colspan="4" class="text-center" style="background: #FBF0DF;">Kecenderungan Sarapan (Sikap Konatif)</th>
                    <th colspan="2" class="text-center" style="background: #EBF1F7;">SQ-FFQ Awal</th>
                </tr>
                <tr>
                    <th class="text-center" style="width: 50px;">Pre</th>
                    <th class="text-center" style="width: 50px;">Post</th>
                    <th class="text-center" style="width: 50px;">Δ (Selisih)</th>
                    <th class="text-center" style="width: 55px;">Mgg 1</th>
                    <th class="text-center" style="width: 55px;">Mgg 2</th>
                    <th class="text-center" style="width: 55px;">Mgg 3</th>
                    <th class="text-center" style="width: 65px;">Rata-rata</th>
                    <th class="text-center" style="width: 55px;">Skor</th>
                    <th class="text-center" style="width: 70px;">Kategori</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($siswaRows as $idx => $row)
                    <tr>
                        <td class="text-center">{{ $idx + 1 }}</td>
                        <td>
                            <div class="font-bold">{{ $row['nama_lengkap'] }}</div>
                            <div style="font-size: 7pt; color: var(--text-muted);">NIS: {{ $row['nis'] ?? '-' }} · ID #{{ $row['siswa_id'] }}</div>
                        </td>
                        <td class="text-center">{{ $row['jenis_kelamin'] }}</td>
                        <td>
                            @php
                                $badgeClass = match ($row['status_akun_nama']) {
                                    'Terverifikasi' => 'badge-success',
                                    'Menunggu Verifikasi' => 'badge-warning',
                                    default => 'badge-neutral',
                                };
                            @endphp
                            <span class="badge-pill {{ $badgeClass }}">{{ $row['status_akun_nama'] }}</span>
                        </td>
                        <td class="text-center font-bold">{{ $row['skor_pretest'] !== null ? $row['skor_pretest'] : '-' }}</td>
                        <td class="text-center font-bold">{{ $row['skor_posttest'] !== null ? $row['skor_posttest'] : '-' }}</td>
                        <td class="text-center">
                            @if ($row['selisih'] !== null)
                                <span class="{{ $row['selisih'] >= 0 ? 'diff-pos' : 'diff-neg' }}">
                                    {{ $row['selisih'] >= 0 ? '+' . $row['selisih'] : $row['selisih'] }}
                                </span>
                            @else
                                <span style="color: var(--text-muted);">-</span>
                            @endif
                        </td>
                        <td class="text-center">{{ $row['m1_persen'] !== null ? $row['m1_persen'] . '%' : '-' }}</td>
                        <td class="text-center">{{ $row['m2_persen'] !== null ? $row['m2_persen'] . '%' : '-' }}</td>
                        <td class="text-center">{{ $row['m3_persen'] !== null ? $row['m3_persen'] . '%' : '-' }}</td>
                        <td class="text-center font-bold">
                            @if ($row['rata_kecenderungan'] !== null)
                                {{ $row['rata_kecenderungan'] }}%
                            @else
                                <span style="color: var(--text-muted);">-</span>
                            @endif
                        </td>
                        <td class="text-center font-bold">{{ $row['sqffq_skor'] !== null ? $row['sqffq_skor'] : '-' }}</td>
                        <td class="text-center">
                            @if ($row['sqffq_kategori'] !== '-')
                                @php
                                    $sqBadge = match ($row['sqffq_kategori']) {
                                        'Baik' => 'badge-success',
                                        'Cukup' => 'badge-warning',
                                        default => 'badge-danger',
                                    };
                                @endphp
                                <span class="badge-pill {{ $sqBadge }}">{{ $row['sqffq_kategori'] }}</span>
                            @else
                                <span style="color: var(--text-muted);">-</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="13" class="text-center" style="padding: 16px; color: var(--text-muted);">
                            Tidak ada data siswa terdaftar di kelas ini.
                        </td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="4" class="text-right font-bold">RATA-RATA KELAS:</td>
                    <td class="text-center font-bold">{{ $ringkasan['rata_pretest'] !== null ? $ringkasan['rata_pretest'] : '-' }}</td>
                    <td class="text-center font-bold">{{ $ringkasan['rata_posttest'] !== null ? $ringkasan['rata_posttest'] : '-' }}</td>
                    <td class="text-center font-bold">
                        @if ($ringkasan['rata_selisih'] !== null)
                            <span class="{{ $ringkasan['rata_selisih'] >= 0 ? 'diff-pos' : 'diff-neg' }}">
                                {{ $ringkasan['rata_selisih'] >= 0 ? '+' . $ringkasan['rata_selisih'] : $ringkasan['rata_selisih'] }}
                            </span>
                        @else
                            -
                        @endif
                    </td>
                    <td colspan="3" class="text-center font-bold" style="font-size: 7.5pt; color: var(--text-muted);">RATA-RATA M1–M3:</td>
                    <td class="text-center font-bold" style="color: var(--primary);">
                        {{ $ringkasan['rata_kecenderungan'] !== null ? $ringkasan['rata_kecenderungan'] . '%' : '-' }}
                    </td>
                    <td class="text-center font-bold">{{ $ringkasan['rata_sqffq'] !== null ? $ringkasan['rata_sqffq'] : '-' }}</td>
                    <td class="text-center font-bold">-</td>
                </tr>
            </tfoot>
        </table>

        {{-- Catatan Interpretasi Ilmiah Gizi --}}
        <div class="notes-box">
            <strong>Catatan & Keterangan Metodologis:</strong>
            <ol style="margin-left: 18px; margin-top: 2px;">
                <li><strong>Pengetahuan Gizi:</strong> Diukur menggunakan kuesioner terstandar sebelum intervensi (Pretest) dan setelah intervensi 3 minggu (Posttest). Nilai selisih (Δ) positif menandakan adanya peningkatan pemahaman gizi sarapan.</li>
                <li><strong>Kecenderungan Sarapan (Sikap Konatif):</strong> Diukur dari rasio kesesuaian realisasi sarapan pagi hari terhadap perencanaan menu yang dibuat mandiri oleh siswa pada malam sebelumnya. Sikap konatif merefleksikan kesiapan dan komitmen perilaku nyata siswa.</li>
                <li><strong>SQ-FFQ (Semi-Quantitative Food Frequency Questionnaire):</strong> Menilai kebiasaan konsumsi ragam bahan pangan sarapan dalam 1 bulan terakhir sebelum program dimulai (Kategori: Baik ≥80, Cukup 60–79, Kurang &lt;60).</li>
            </ol>
        </div>

        {{-- Lembar Pengesahan / Tanda Tangan --}}
        <div class="signature-block">
            <div class="signature-box">
                <div class="signature-role">Mengetahui,<br>Wali Kelas / Guru Pembimbing</div>
                <div class="signature-name">( ............................................................ )</div>
                <div class="signature-nip">NIP. ........................................................</div>
            </div>

            <div class="signature-box">
                <div class="signature-role">Jakarta, {{ \Carbon\Carbon::now()->translatedFormat('d F Y') }}<br>Petugas Peneliti Gizi,</div>
                <div class="signature-name">{{ $user->nama_lengkap ?? 'Administrator' }}</div>
                <div class="signature-nip">Petugas Pelaksana Monitoring Sarapan</div>
            </div>
        </div>

    </div>

</body>
</html>

