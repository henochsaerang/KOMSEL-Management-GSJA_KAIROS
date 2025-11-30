<?php

use Illuminate\Support\Facades\Route;
use App\Http\Middleware\CekApiSession;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\OikosController;
use App\Http\Controllers\KomselController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\KunjunganController;
use App\Http\Controllers\StatistikController; // [FIX 1] Pastikan ini di-import (uncomment)

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
    Route::patch('/admin/appoint-coordinator', [DashboardController::class, 'appointCoordinator'])->name('admin.appointCoordinator');

    // Rute Oikos
    Route::get('/oikos', [OikosController::class, 'daftarOikos'])->name('oikos');
    Route::get('/formInput', [OikosController::class, 'formInputOikos'])->name('formInput');
    Route::post('/oikos', [OikosController::class, 'storeOikosVisit'])->name('oikos.store');
    Route::post('/oikos-visits/{oikosVisit}/report', [OikosController::class, 'storeReport'])->name('oikos.report.store');
    Route::patch('/oikos-visits/{oikosVisit}/delegate', [OikosController::class, 'delegateVisit'])->name('oikos.delegate');
    Route::get('/api/oikos-visits/{oikosVisit}', [OikosController::class, 'getReportDetails'])->name('oikos.report.show');
    Route::patch('/oikos-visits/{oikosVisit}/confirm', [OikosController::class, 'confirmVisit'])->name('oikos.confirm');

    Route::patch('/oikos-visits/{oikosVisit}/revision', [OikosController::class, 'requestRevision'])->name('oikos.revision');
    Route::delete('/oikos-visits/{oikosVisit}', [OikosController::class, 'destroy'])->name('oikos.destroy');

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

    Route::get('/admin/komsel-aktif', [DashboardController::class, 'komselAktif'])->name('admin.komselAktif');
    Route::patch('/admin/reset-coordinator', [DashboardController::class, 'resetCoordinator'])->name('admin.resetCoordinator');

    Route::get('/oikos/{oikosVisit}/detail', [OikosController::class, 'detailOikos'])->name('oikos.detail');
    Route::delete('/oikos/bulk-destroy', [OikosController::class, 'bulkDestroy'])->name('oikos.bulk_destroy');

    Route::get('/kunjungan', [KunjunganController::class, 'index'])->name('kunjungan');
    Route::get('/kunjungan/create', [KunjunganController::class, 'create'])->name('kunjungan.create');
    Route::post('/kunjungan', [KunjunganController::class, 'store'])->name('kunjungan.store');
     // [BARU] Route untuk update laporan/realisasi
    Route::patch('/kunjungan/{kunjungan}/report', [KunjunganController::class, 'updateReport'])->name('kunjungan.report');

    Route::patch('/kunjungan/{kunjungan}/confirm', [KunjunganController::class, 'confirm'])->name('kunjungan.confirm');
    
    // [BARU] Route untuk hapus (opsional, untuk melengkapi CRUD)
    Route::delete('/kunjungan/{kunjungan}', [KunjunganController::class, 'destroy'])->name('kunjungan.destroy');
    // --- END SECTION KUNJUNGAN ---

    

    // Rute Logout
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});