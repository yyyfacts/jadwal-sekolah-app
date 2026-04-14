<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WaktuHari extends Model
{
    protected $table = 'waktu_hari';
    public $timestamps = false; // Karena nggak pakai created_at/updated_at

    protected $fillable = [
        'master_hari_id', 'jam_ke', 'waktu_mulai', 'waktu_selesai', 'tipe'
    ];

    public function masterHari()
    {
        return $this->belongsTo(MasterHari::class);
    }
}