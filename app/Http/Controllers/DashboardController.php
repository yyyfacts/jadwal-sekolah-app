<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Guru;
use App\Models\Kelas;
use App\Models\Mapel;
use App\Models\Jadwal;

class DashboardController extends Controller
{
    public function index()
    {
        $totalGuru = Guru::count();
        $totalKelas = Kelas::count();
        $totalMapel = Mapel::count();
        $totalJadwal = Jadwal::count();

        return view('dashboard', compact('totalGuru', 'totalKelas', 'totalMapel', 'totalJadwal'));
    }
}