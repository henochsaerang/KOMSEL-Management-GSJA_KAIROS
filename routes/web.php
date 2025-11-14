<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\KomselController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\OikosController;
use App\Http\Controllers\StatistikController; // [FIX 1] Pastikan ini di-import (uncomment)
use App\Http\Middleware\CekApiSession;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Rute publik
Route::get('/', function () {
    return view('welcome');
});

Route::get('/form-signup', [AuthController::class, 'signup'])->name('signup');
Route::post('/register', [AuthController::class, 'register'])->name('register');
Route::get('/form-login', [AuthController::class, 'login'])->name('login');
Route::post('/authenticate', [AuthController::class, 'authenticate'])->name('autentikasi');

// Rute yang dilindungi
Route::middleware('auth')->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Rute Oikos
    Route::get('/oikos', [OikosController::class, 'daftarOikos'])->name('oikos');
    Route::get('/formInput', [OikosController::class, 'formInputOikos'])->name('formInput');
    Route::post('/oikos', [OikosController::class, 'storeOikosVisit'])->name('oikos.store');
    Route::post('/oikos-visits/{oikosVisit}/report', [OikosController::class, 'storeReport'])->name('oikos.report.store');
    Route::get('/api/oikos-visits/{oikosVisit}', [OikosController::class, 'getReportDetails'])->name('oikos.report.show');
    Route::patch('/oikos-visits/{oikosVisit}/confirm', [OikosController::class, 'confirmVisit'])->name('oikos.confirm');

    // Rute Manajemen KOMSEL & Anggota
    Route::get('/daftarkomsel', [KomselController::class, 'daftar'])->name('daftarKomsel');
    Route::patch('/users/{user}/assign-komsel', [KomselController::class, 'assignKomsel'])->name('users.assignKomsel');

    // Rute Manajemen Jadwal
    Route::get('/jadwal', [KomselController::class, 'jadwal'])->name('jadwal');
    Route::post('/jadwal/store', [KomselController::class, 'storeJadwal'])->name('jadwal.store');
    Route::patch('/jadwal/{schedule}', [KomselController::class, 'updateJadwal'])->name('jadwal.update');
    Route::delete('/jadwal/{schedule}', [KomselController::class, 'destroyJadwal'])->name('jadwal.destroy');

    // Rute API Internal
    Route::get('/api/schedules/{schedule}/attendances', [KomselController::class, 'getAttendance'])->name('api.schedule.attendances.get');
    Route::post('/api/schedules/{schedule}/attendances', [KomselController::class, 'storeAttendance'])->name('api.schedule.attendances.store');

    // [FIX 2] Tambahkan kembali rute Statistik di sini
    Route::get('/statistik', [StatistikController::class, 'statistik'])->name('statistik');
    // (Asumsi Anda juga memerlukan rute ekspor yang mungkin ada sebelumnya)
    Route::get('/statistik/export/excel', [StatistikController::class, 'exportExcel'])->name('statistik.export.excel');
    Route::get('/statistik/export/pdf', [StatistikController::class, 'exportPdf'])->name('statistik.export.pdf');


    // Rute Logout
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});