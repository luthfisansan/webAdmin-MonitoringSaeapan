<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Disable automatic transaction wrapping because the SQL schema file manages its own transaction.
     */
    public $withinTransaction = false;

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $this->down();

        DB::unprepared(file_get_contents(database_path('schema/breakfast_planner_schema.sql')));
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::unprepared('
            DROP VIEW IF EXISTS
                tempat_sarapan_v,
                pola_konsumsi_kelompok_v,
                laporan_kelas_v,
                food_record_detail_v,
                sqffq_skor_v,
                sqffq_rekap_v,
                antrean_verifikasi_v,
                siswa_status_v,
                kepatuhan_mingguan_v,
                kepatuhan_harian_v,
                skor_pengetahuan_v,
                progres_edukasi_v
            CASCADE;

            DROP TABLE IF EXISTS
                food_record_detail_t,
                food_record_t,
                rencana_sarapan_detail_t,
                rencana_sarapan_t,
                peserta_program_t,
                sqffq_detail_t,
                sqffq_t,
                baca_materi_t,
                jawaban_kuesioner_t,
                hasil_kuesioner_t,
                riwayat_status_akun_t,
                status_realisasi_m,
                tempat_makan_m,
                konversi_urt_m,
                urt_m,
                frekuensi_m,
                bahan_makanan_m,
                kelompok_bahan_m,
                materi_edukasi_m,
                opsi_jawaban_m,
                soal_m,
                kuesioner_m,
                program_m,
                siswa_m,
                pengguna_m,
                kelas_m,
                status_akun_m,
                peran_m
            CASCADE;

            DROP FUNCTION IF EXISTS trg_sqffq_fn() CASCADE;
            DROP FUNCTION IF EXISTS trg_baca_materi_fn() CASCADE;
            DROP FUNCTION IF EXISTS trg_hasil_kuesioner_fn() CASCADE;
            DROP FUNCTION IF EXISTS fn_cek_edukasi_selesai(BIGINT) CASCADE;
            DROP FUNCTION IF EXISTS fn_verifikasi_siswa(BIGINT) CASCADE;
            DROP FUNCTION IF EXISTS fn_set_status_akun(BIGINT, VARCHAR, VARCHAR, BIGINT, TEXT) CASCADE;
        ');
    }
};
