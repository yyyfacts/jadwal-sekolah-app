<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Jadwal extends Model
{
    use HasFactory;

    protected $table = 'jadwals';

    // Pastikan 'status' ada di dalam fillable
    protected $fillable = [
        'guru_id',
        'mapel_id',
        'kelas_id',
        'jumlah_jam',
        'tipe_jam',
        'hari',
        'jam',
        'status', 
    ];

    public function guru()
    {
        return $this->belongsTo(Guru::class);
    }

    public function mapel()
    {
        return $this->belongsTo(Mapel::class);
    }

    public function kelas()
    {
        return $this->belongsTo(Kelas::class);
    }

    public function getNamaGuruAttribute()
    {
        return $this->guru->nama_guru ?? '-';
    }

    public function getNamaMapelAttribute()
    {
        return $this->mapel->nama_mapel ?? '-';
    }

    public function getNamaKelasAttribute()
    {
        return $this->kelas->nama_kelas ?? '-';
    }
}