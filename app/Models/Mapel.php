<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mapel extends Model
{
    use HasFactory;

    protected $table = 'mapels';

    protected $fillable = [
        'nama_mapel',
        'kode_mapel',
        'status',
        'batas_maksimal_jam',
        'jenis_batas', // <--- INI BIANG KEROKNYA, SEKARANG SUDAH DITAMBAHKAN
    ];

    public function jadwals()
    {
        return $this->hasMany(Jadwal::class);
    }
}