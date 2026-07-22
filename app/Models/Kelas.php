<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kelas extends Model
{
    use HasFactory;

    protected $table = 'kelas';

    protected $fillable = [
        'nama_kelas', 
        'kode_kelas', 
        'max_jam',
        'limit_harian', 
        'limit_jumat',
        'wali_guru_id',
        // 'blocked_slots' sengaja dihapus dari sini -> udah gak dipake lagi,
        // diganti tabel kelas_waktu_khusus (lihat relasi waktuKhusus() di bawah)
    ];

    public function jadwals()
    {
        return $this->hasMany(Jadwal::class, 'kelas_id');
    }

    // Relasi ke Wali Kelas
    public function waliKelas()
    {
        return $this->belongsTo(Guru::class, 'wali_guru_id');
    }

    // [BARU] Daftar pengecualian jam kelas ini (jam yang tipe-nya selain 'Belajar')
    public function waktuKhusus()
    {
        return $this->hasMany(KelasWaktuKhusus::class, 'kelas_id');
    }


}