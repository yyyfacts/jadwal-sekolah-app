<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WaktuKosong extends Model
{
     protected $fillable = ['guru_id', 'kelas_id', 'mapel_id', 'hari', 'jam'];
}