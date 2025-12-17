<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan; // <--- Wajib ada biar Artisan::call jalan
use App\Http\Controllers\GuruController;
use App\Http\Controllers\MapelController;
use App\Http\Controllers\KelasController;
use App\Http\Controllers\JadwalController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ProfileController;

// ==================================================================
// 1. RUTE KHUSUS DEPLOY (PUBLIC / TANPA LOGIN)
// ==================================================================
// Ini ditaruh di luar middleware auth supaya bisa dijalankan saat database masih kosong
Route::get('/tembak-db', function () {
    try {
        Artisan::call('migrate --force');
        return '<h1>SUKSES! Database berhasil di-migrate. <br> Silakan <a href="/">KLIK DISINI</a> untuk Login.</h1>';
    } catch (\Exception $e) {
        return 'Gagal: ' . $e->getMessage();
    }
});

// ==================================================================
// 2. AUTHENTICATION ROUTES (GUEST)
// ==================================================================
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

// Logout butuh login dulu
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

// ==================================================================
// 3. PROTECTED ROUTES (Hanya Bisa Diakses Setelah Login)
// ==================================================================
Route::middleware(['auth'])->group(function () {

    // Redirect home ke guru index
    Route::get('/', function () {
        return redirect()->route('guru.index');
    })->name('home');

    // GROUP: GURU
    Route::prefix('guru')->name('guru.')->group(function () {
        // Fitur Jadwal & Waktu Kosong Guru
        Route::post('/{id}/jadwal', [GuruController::class, 'simpanJadwal'])->name('simpanJadwal')->where('id', '[0-9]+');
        Route::put('/jadwal/{id}', [GuruController::class, 'updateJadwal'])->name('updateJadwal')->where('id', '[0-9]+');
        Route::delete('/jadwal/{id}', [GuruController::class, 'hapusJadwal'])->name('hapusJadwal')->where('id', '[0-9]+');
        Route::get('/{id}/waktu-kosong', [GuruController::class, 'waktuKosongForm'])->name('waktuKosong');
        Route::post('/{id}/waktu-kosong', [GuruController::class, 'simpanWaktuKosong'])->name('simpanWaktuKosong');
        
        // CRUD Guru
        Route::get('/', [GuruController::class, 'index'])->name('index');
        Route::post('/', [GuruController::class, 'store'])->name('store');
        Route::put('/{id}', [GuruController::class, 'update'])->name('update');
        Route::delete('/{id}', [GuruController::class, 'destroy'])->name('destroy');
    });

    // GROUP: MAPEL
    Route::prefix('mapel')->name('mapel.')->group(function () {
        // Fitur Jadwal (Distribusi)
        Route::post('/{id}/jadwal', [MapelController::class, 'simpanJadwal'])->name('simpanJadwal');
        Route::put('/jadwal/{id}', [MapelController::class, 'updateJadwal'])->name('updateJadwal');
        Route::delete('/jadwal/{id}', [MapelController::class, 'hapusJadwal'])->name('hapusJadwal');

        // Fitur Waktu Kosong Mapel
        Route::get('/{id}/waktu-kosong', [MapelController::class, 'waktuKosongForm'])->name('waktuKosong');
        Route::post('/{id}/waktu-kosong', [MapelController::class, 'simpanWaktuKosong'])->name('simpanWaktuKosong');

        // CRUD Mapel
        Route::get('/', [MapelController::class, 'index'])->name('index');
        Route::post('/', [MapelController::class, 'store'])->name('store');
        Route::put('/{id}', [MapelController::class, 'update'])->name('update');
        Route::delete('/{id}', [MapelController::class, 'destroy'])->name('destroy');
    });

    // GROUP: KELAS
    Route::prefix('kelas')->name('kelas.')->group(function () {
        Route::post('/{id}/jadwal', [KelasController::class, 'simpanJadwal'])->name('simpanJadwal');
        Route::put('/jadwal/{id}', [KelasController::class, 'updateJadwal'])->name('updateJadwal');
        Route::delete('/jadwal/{id}', [KelasController::class, 'hapusJadwal'])->name('hapusJadwal');
        
        Route::get('/', [KelasController::class, 'index'])->name('index');
        Route::post('/', [KelasController::class, 'store'])->name('store');
        Route::put('/{id}', [KelasController::class, 'update'])->name('update');
        Route::delete('/{id}', [KelasController::class, 'destroy'])->name('destroy');
    });

    // GROUP: JADWAL (Generate & Export)
    Route::prefix('jadwal')->name('jadwal.')->group(function () {
        Route::post('/generate', [JadwalController::class, 'generate'])->name('generate');
        Route::get('/export', [JadwalController::class, 'export'])->name('export');
        Route::get('/', [JadwalController::class, 'index'])->name('index');
    });

    // GROUP: MANAJEMEN USER
    Route::prefix('users')->name('user.')->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('index');
        Route::post('/', [UserController::class, 'store'])->name('store');
        Route::delete('/{id}', [UserController::class, 'destroy'])->name('destroy');
    });

    // GROUP: PROFILE (Edit Profil User Login)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
});