<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mapel extends Model
{
    use HasFactory;

    protected $fillable = ['nama_mapel', 'kode_mapel', 'mode'];


    // Relasi ke jadwal (Satu Mapel punya banyak Jadwal/Distribusi)
    public function jadwals()
    {
        return $this->hasMany(Jadwal::class);
    }

    // Relasi ke waktu kosong (Satu Mapel punya banyak aturan jam libur)

}