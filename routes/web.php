<?php

use App\Http\Controllers\Admin\LaporanCetakController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/admin');
});

Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/admin/laporan/cetak', [LaporanCetakController::class, 'cetakLaporan'])->name('admin.laporan.cetak');
    Route::get('/admin/detail-siswa/cetak', [LaporanCetakController::class, 'cetakSiswa'])->name('admin.detail-siswa.cetak');
});
