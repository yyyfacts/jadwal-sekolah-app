<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mapel extends Model
{
    use HasFactory;

    protected $table = 'mapels';

    // Tambahkan 'mode' agar bisa disimpan
    protected $fillable = ['nama_mapel', 'kode_mapel', 'kelompok', 'mode'];

    // Relasi ke jadwal (Satu Mapael punya banyak Jadwal/Distribusi)
    public function jadwals()
    {
        return $this->hasMany(Jadwal::class);
    }
}