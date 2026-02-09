<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TahunPelajaran extends Model
{
    use HasFactory;

    protected $fillable = ['tahun', 'semester', 'is_active'];

    // Cast agar 'is_active' otomatis jadi true/false, bukan 1/0
    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Helper statis untuk mengambil tahun ajaran yang sedang aktif.
     * Cara pakainya di Controller: TahunPelajaran::getActive()
     */
    public static function getActive()
    {
        return self::where('is_active', true)->first();
    }

    // OPSI TAMBAHAN:
    // Jika nanti di tabel 'jadwals' kamu menambahkan kolom 'tahun_pelajaran_id',
    // kamu bisa buka komentar relasi di bawah ini:
    /*
    public function jadwals()
    {
        return $this->hasMany(Jadwal::class);
    }
    */
}