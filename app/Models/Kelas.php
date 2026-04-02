<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kelas extends Model
{
    use HasFactory;

    protected $table = 'kelas'; // Pastikan nama tabel benar (biasanya plural 'kelas' atau 'classes')

    // TAMBAHKAN 'limit_harian' DAN 'limit_jumat' DI SINI
    protected $fillable = [
        'nama_kelas', 
        'kode_kelas', 
        'max_jam',
        'limit_harian', // <--- Wajib ada
        'limit_jumat'   // <--- Wajib ada
    ];

    public function jadwals()
    {
        return $this->hasMany(Jadwal::class, 'kelas_id');
    }


}