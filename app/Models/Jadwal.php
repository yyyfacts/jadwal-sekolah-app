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
        'master_hari_id', // <--- UBAH: dari 'hari' menjadi 'master_hari_id'
        'jumlah_jam',
        'tipe_jam',
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

    // --- TAMBAHAN: Relasi ke Master Hari ---
    public function masterHari()
    {
        return $this->belongsTo(MasterHari::class, 'master_hari_id');
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

    // --- TAMBAHAN: Accessor untuk mendapatkan nama hari (Opsional tapi berguna) ---
    public function getNamaHariAttribute()
    {
        return $this->masterHari->nama_hari ?? '-';
    }
}