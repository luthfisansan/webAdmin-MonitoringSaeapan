<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\OnboardingController;
use App\Http\Controllers\Api\ProgramHarianController;
use Illuminate\Support\Facades\Route;

// Endpoint Publik
Route::get('/kelas', [AuthController::class, 'kelas'])->name('api.kelas');
Route::post('/auth/register', [AuthController::class, 'register'])->name('api.auth.register');
Route::post('/auth/login', [AuthController::class, 'login'])->name('api.auth.login');

// Endpoint Terautentikasi (Sanctum)
Route::middleware('auth:sanctum')->group(function (): void {
    Route::post('/auth/logout', [AuthController::class, 'logout'])->name('api.auth.logout');
    Route::get('/me', [AuthController::class, 'me'])->name('api.me');

    // Onboarding (dapat diakses sebelum TERVERIFIKASI)
    Route::get('/pretest', [OnboardingController::class, 'pretest'])->name('api.pretest');
    Route::post('/pretest/submit', [OnboardingController::class, 'submitPretest'])->name('api.pretest.submit');
    Route::get('/materi', [OnboardingController::class, 'daftarMateri'])->name('api.materi.index');
    Route::get('/materi/{materi_edukasi_id}', [OnboardingController::class, 'detailMateri'])->name('api.materi.show');
    Route::post('/materi/{materi_edukasi_id}/selesai', [OnboardingController::class, 'selesaiMateri'])->name('api.materi.selesai');
    Route::get('/progres-edukasi', [OnboardingController::class, 'progresEdukasi'])->name('api.progres-edukasi');

    // Khusus Siswa berstatus TERVERIFIKASI (Middleware status)
    Route::middleware('siswa.terverifikasi')->group(function (): void {
        Route::get('/beranda', [ProgramHarianController::class, 'beranda'])->name('api.beranda');
        Route::get('/referensi', [ProgramHarianController::class, 'referensi'])->name('api.referensi');
        Route::get('/planner/{tanggal}', [ProgramHarianController::class, 'getPlanner'])->name('api.planner.show');
        Route::put('/planner/{tanggal}', [ProgramHarianController::class, 'putPlanner'])->name('api.planner.update');
        Route::get('/food-record/{tanggal}', [ProgramHarianController::class, 'getFoodRecord'])->name('api.food-record.show');
        Route::put('/food-record/{tanggal}', [ProgramHarianController::class, 'putFoodRecord'])->name('api.food-record.update');
        Route::get('/rekap', [ProgramHarianController::class, 'rekap'])->name('api.rekap');
        Route::get('/posttest', [ProgramHarianController::class, 'posttest'])->name('api.posttest');
        Route::post('/posttest/submit', [ProgramHarianController::class, 'submitPosttest'])->name('api.posttest.submit');
    });
});

