<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Jadwal extends Model
{
    use HasFactory;

    protected $table = 'jadwals';

    protected $fillable = [
        'guru_id',
        'mapel_id',
        'kelas_id',
        'jumlah_jam',
        'tipe_jam',
        'hari',
        'jam',
    ];

    // Relasi ke guru
    public function guru()
    {
        return $this->belongsTo(Guru::class);
    }

    // Relasi ke mapel
    public function mapel()
    {
        return $this->belongsTo(Mapel::class);
    }

    // Relasi ke kelas
    public function kelas()
    {
        return $this->belongsTo(Kelas::class);
    }

    // Accessor untuk tampilan
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

    public function getLamanyaAttribute()
    {
        if ($this->tipe_jam === 'double')
            return 2;
        if ($this->tipe_jam === 'triple')
            return 3;
        return 1;
    }
}