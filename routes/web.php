<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

// Controllers
use App\Http\Controllers\GuruController;
use App\Http\Controllers\MapelController;
use App\Http\Controllers\KelasController;
use App\Http\Controllers\JadwalController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TahunPelajaranController; 
use App\Http\Controllers\MasterHariController;
use App\Http\Controllers\MasterWaktuController;

// ==================================================================
// 1. RUTE KHUSUS DEPLOY & DEBUG (PUBLIC)
// ==================================================================

Route::get('/tembak-db', function () {
    try {
        Artisan::call('migrate --force');
        Artisan::call('route:clear');
        Artisan::call('config:clear');
        Artisan::call('view:clear');
        
        return '<h1>SUKSES! <br>✅ Database Migrated. <br>✅ Route Cache DIBERSIHKAN. <br>✅ View Cache DIBERSIHKAN.</h1>';
    } catch (\Exception $e) {
        return 'Gagal: ' . $e->getMessage();
    }
});

Route::get('/fix-zain', function () {
    $username = 'zain'; 
    $password_baru = 'zain1234';
    try {
        $user = User::where('username', $username)->first();
        if ($user) {
            $user->password = Hash::make($password_baru);
            $user->save();
            $pesan = "Akun DITEMUKAN. Password berhasil di-reset.";
        } else {
            User::create([
                'name' => 'Zain',
                'username' => $username,
                'password' => Hash::make($password_baru),
            ]);
            $pesan = "Akun TIDAK ADA, berhasil dibuatkan BARU.";
        }
        return "<h1>SUKSES! $pesan <br>Username: <b>$username</b> <br>Password: <b>$password_baru</b> <br><br><a href='/login'>LOGIN DISINI</a></h1>";
    } catch (\Exception $e) {
        return 'Error: ' . $e->getMessage();
    }
});

Route::get('/fix-storage', function () {
    try {
        Artisan::call('storage:link');
        return '<h1>SUKSES! Symlink Storage berhasil.</h1>';
    } catch (\Exception $e) {
        return 'Gagal: ' . $e->getMessage();
    }
});

// ==================================================================
// 2. AUTHENTICATION (GUEST)
// ==================================================================
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');


// ==================================================================
// 3. PROTECTED ROUTES (LOGIN REQUIRED)
// ==================================================================
Route::middleware(['auth'])->group(function () {

    // Dashboard Redirect
    Route::get('/', function () {
        return redirect()->route('guru.index');
    })->name('home');

    // --------------------------------------------------------------
    // GROUP: DATA MASTER (TAHUN PELAJARAN)
    // --------------------------------------------------------------
    Route::prefix('tahun-pelajaran')->name('tahun-pelajaran.')->group(function () {
        Route::get('/', [TahunPelajaranController::class, 'index'])->name('index');
        Route::post('/', [TahunPelajaranController::class, 'store'])->name('store');
        Route::patch('/{id}/activate', [TahunPelajaranController::class, 'activate'])->name('activate');
        Route::delete('/{id}', [TahunPelajaranController::class, 'destroy'])->name('destroy');
    });

    // --------------------------------------------------------------
    // GROUP: DATA MASTER (HARI AKTIF)
    // --------------------------------------------------------------
    Route::prefix('master-hari')->name('master-hari.')->group(function () {
        Route::get('/', [MasterHariController::class, 'index'])->name('index');
        Route::post('/', [MasterHariController::class, 'store'])->name('store');
        Route::put('/{id}', [MasterHariController::class, 'update'])->name('update');
        Route::delete('/{id}', [MasterHariController::class, 'destroy'])->name('destroy');
    });

    // --------------------------------------------------------------
    // GROUP: DATA MASTER (WAKTU / JAM PELAJARAN)
    // --------------------------------------------------------------
    Route::prefix('master-waktu')->name('master-waktu.')->group(function () {
        Route::get('/', [MasterWaktuController::class, 'index'])->name('index');
        Route::post('/', [MasterWaktuController::class, 'store'])->name('store');
        Route::put('/{id}', [MasterWaktuController::class, 'update'])->name('update');
        Route::delete('/{id}', [MasterWaktuController::class, 'destroy'])->name('destroy');
    });

    // --------------------------------------------------------------
    // GROUP: DATA MASTER (GURU)
    // --------------------------------------------------------------
    Route::prefix('guru')->name('guru.')->group(function () {
        // Fitur Jadwal (Ajax)
        Route::post('/{id}/jadwal', [GuruController::class, 'simpanJadwal'])->name('simpanJadwal');
        Route::put('/jadwal/{id}', [GuruController::class, 'updateJadwal'])->name('updateJadwal');
        Route::delete('/jadwal/{id}', [GuruController::class, 'hapusJadwal'])->name('hapusJadwal');
        
        // CRUD Guru Utama
        Route::get('/', [GuruController::class, 'index'])->name('index');
        Route::post('/', [GuruController::class, 'store'])->name('store');
        Route::put('/{id}', [GuruController::class, 'update'])->name('update');
        Route::delete('/{id}', [GuruController::class, 'destroy'])->name('destroy');
    });

    // --------------------------------------------------------------
    // GROUP: DATA MASTER (MAPEL)
    // --------------------------------------------------------------
    Route::prefix('mapel')->name('mapel.')->group(function () {
        // CRUD Mapel
        Route::get('/', [MapelController::class, 'index'])->name('index');
        Route::post('/', [MapelController::class, 'store'])->name('store');
        Route::put('/{id}', [MapelController::class, 'update'])->name('update');
        Route::delete('/{id}', [MapelController::class, 'destroy'])->name('destroy');
        
        // Fitur Ganti Mode (Online/Offline)
        Route::post('/{id}/mode', [MapelController::class, 'updateMode'])->name('updateMode');
        
        // Rute Jadwal Mapel
        Route::post('/{id}/jadwal', [MapelController::class, 'simpanJadwal'])->name('simpanJadwal');
        Route::put('/jadwal/{id}', [MapelController::class, 'updateJadwal'])->name('updateJadwal');
        Route::delete('/jadwal/{id}', [MapelController::class, 'hapusJadwal'])->name('hapusJadwal');
    });

    // --------------------------------------------------------------
    // GROUP: DATA MASTER (KELAS)
    // --------------------------------------------------------------
    Route::prefix('kelas')->name('kelas.')->group(function () {
        // CRUD Kelas
        Route::get('/', [KelasController::class, 'index'])->name('index');
        Route::post('/', [KelasController::class, 'store'])->name('store');
        Route::put('/{id}', [KelasController::class, 'update'])->name('update');
        Route::delete('/{id}', [KelasController::class, 'destroy'])->name('destroy');

        // Rute Khusus Kelas (Jadwal)
        Route::post('/{id}/jadwal', [KelasController::class, 'simpanJadwal'])->name('simpanJadwal');
        Route::put('/jadwal/{id}', [KelasController::class, 'updateJadwal'])->name('updateJadwal');
        Route::delete('/jadwal/{id}', [KelasController::class, 'hapusJadwal'])->name('hapusJadwal');
    });

    // --------------------------------------------------------------
    // GROUP: PENJADWALAN (AI SOLVER & EXPORT)
    // --------------------------------------------------------------
    Route::prefix('jadwal')->name('jadwal.')->group(function () {
        Route::get('/', [JadwalController::class, 'index'])->name('index');
        Route::post('/generate', [JadwalController::class, 'generate'])->name('generate');
        Route::get('/export', [JadwalController::class, 'export'])->name('export');
    });

    // --------------------------------------------------------------
    // GROUP: MANAJEMEN USER
    // --------------------------------------------------------------
    Route::prefix('users')->name('user.')->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('index');
        Route::post('/', [UserController::class, 'store'])->name('store');
        Route::delete('/{id}', [UserController::class, 'destroy'])->name('destroy');
    });

    // --------------------------------------------------------------
    // GROUP: PROFILE SETTINGS
    // --------------------------------------------------------------
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
});