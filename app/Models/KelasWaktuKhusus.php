<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KelasWaktuKhusus extends Model
{
    protected $table = 'kelas_waktu_khusus';

    protected $fillable = [
        'kelas_id',
        'master_hari_id',
        'jam_ke',
        'tipe',
        'keterangan',
    ];

    public function kelas()
    {
        return $this->belongsTo(Kelas::class);
    }

    public function masterHari()
    {
        return $this->belongsTo(MasterHari::class);
    }
}