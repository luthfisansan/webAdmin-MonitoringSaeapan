<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1
        FROM information_schema.columns
        WHERE table_name = 'materi_edukasi_m'
          AND column_name = 'file_pdf'
    ) THEN
        ALTER TABLE materi_edukasi_m
        ADD COLUMN file_pdf VARCHAR(255) NULL;
    END IF;
END $$;
SQL);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::unprepared(<<<'SQL'
DO $$
BEGIN
    IF EXISTS (
        SELECT 1
        FROM information_schema.columns
        WHERE table_name = 'materi_edukasi_m'
          AND column_name = 'file_pdf'
    ) THEN
        ALTER TABLE materi_edukasi_m
        DROP COLUMN file_pdf;
    END IF;
END $$;
SQL);
    }
};
