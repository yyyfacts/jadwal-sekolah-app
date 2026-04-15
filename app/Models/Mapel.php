<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mapel extends Model
{
    use HasFactory;

    protected $table = 'mapels';

    // <--- KOREKSI: Hapus 'kelompok' karena tidak ada di tabel database
    protected $fillable = [
        'nama_mapel', 
        'kode_mapel',              // Wajib ditambahkan (karena ada fitur updateMode)
    'status',
    ];

    public function jadwals()
    {
        return $this->hasMany(Jadwal::class);
    }
}