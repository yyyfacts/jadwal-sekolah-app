<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Guru extends Model
{
    use HasFactory;

    // Nama tabel di database (default: gurus)
    protected $table = 'gurus';

    // Kolom yang boleh diisi (Mass Assignment)
    protected $fillable = [
        'nama_guru',
        'kode_guru'
    ];

    // Relasi: Satu guru punya banyak jadwal
    public function jadwals()
    {
        return $this->hasMany(Jadwal::class, 'guru_id');
    }

    // Relasi: Satu guru punya banyak waktu kosong (jam sibuk)
    public function waktuKosong()
    {
        // Pastikan model WaktuKosong ada di App\Models\WaktuKosong
        return $this->hasMany(WaktuKosong::class, 'guru_id');
    }
}