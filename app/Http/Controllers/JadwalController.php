<?php

namespace App\Http\Controllers;

use App\Models\Jadwal;
use App\Models\Guru;
use App\Models\Kelas;
use App\Models\Mapel;
use App\Models\TahunPelajaran;
use App\Models\MasterHari;
use App\Models\MasterWaktu;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\JadwalExport;

class JadwalController extends Controller
{
    /**
     * Menampilkan Grid Jadwal di Website (Dinamis berdasarkan Master Hari & Waktu)
     */
    // =======================================================
    // 1. FUNGSI INDEX (MENAMPILKAN GRID)
    // =======================================================
    public function index(Request $request)
    {
        $reqGuru = $request->input('guru_id');
        $reqKelas = $request->input('kelas_id');

        $gurusList = Guru::orderBy('nama_guru')->get();
        $kelassList = Kelas::orderBy('nama_kelas')->get();

        $dataHari = MasterHari::getActiveDays(); 
        $hariList = $dataHari->pluck('nama_hari')->toArray();
        $dataWaktu = MasterWaktu::getOrdered(); 
        $totalSlotJam = $dataWaktu->count();

        $kelass = $reqKelas ? Kelas::where('id', $reqKelas)->orderBy('nama_kelas')->get() : Kelas::orderBy('nama_kelas')->get();

        $query = Jadwal::with(['guru', 'mapel', 'kelas'])->whereNotNull('hari')->whereNotNull('jam');
        if ($reqGuru) $query->where('guru_id', $reqGuru);
        if ($reqKelas) $query->where('kelas_id', $reqKelas);
        $rawJadwals = $query->get();
        $jadwals = [];

        foreach ($kelass as $k) {
            foreach ($hariList as $h) {
                for ($j = 1; $j <= $totalSlotJam; $j++) {
                    $jadwals[$k->id][$h][$j] = null;
                }
            }
        }

        // --- BIKIN ARRAY LOMPATAN (AMBIL JAM YANG HANYA "BELAJAR" SAJA) ---
        $belajarSlots = [];
        foreach ($dataHari as $hariObj) {
            $namaHari = $hariObj->nama_hari;
            $namaHariLower = strtolower($namaHari);
            $belajarSlots[$namaHari] = [];
            foreach ($dataWaktu as $waktuObj) {
                $tipeSlot = $waktuObj->tipe;
                if ($namaHariLower == 'senin' && $waktuObj->tipe_senin) $tipeSlot = $waktuObj->tipe_senin;
                if ($namaHariLower == 'jumat' && $waktuObj->tipe_jumat) $tipeSlot = $waktuObj->tipe_jumat;

                if (!in_array($tipeSlot, ['Istirahat', 'Upacara', 'Senam', 'Sholat Dhuha', 'Jumat Bersih', 'Pramuka']) && $tipeSlot !== 'Tidak Ada') {
                    $belajarSlots[$namaHari][] = $waktuObj->jam_ke;
                }
            }
        }

        // --- MAPPING DATA DARI DB KE GRID (DENGAN LOMPATAN) ---
        foreach ($rawJadwals as $row) {
            $durasi = $row->jumlah_jam;
            $hari = $row->hari;
            $jamMulaiFisik = $row->jam; 
            
            $slotsTersedia = $belajarSlots[$hari] ?? [];
            $startIndex = array_search($jamMulaiFisik, $slotsTersedia); 
            
            $color = match ($row->tipe_jam) {
                'double' => 'bg-blue-100 text-blue-800 border-blue-200',
                'triple' => 'bg-purple-100 text-purple-800 border-purple-200',
                default => 'bg-white text-slate-700 border-slate-200'
            };

            if ($startIndex !== false) {
                for ($i = 0; $i < $durasi; $i++) {
                    if (isset($slotsTersedia[$startIndex + $i])) {
                        $jamSekarang = $slotsTersedia[$startIndex + $i]; 
                        
                        if (!isset($jadwals[$row->kelas_id])) continue;
                        $jadwals[$row->kelas_id][$hari][$jamSekarang] = [
                            'id' => $row->id,
                            'mapel' => $row->mapel->nama_mapel ?? '-',
                            'guru' => $row->guru->nama_guru ?? '-',
                            'kode_mapel' => $row->mapel->kode_mapel ?? '?',
                            'kode_guru' => $row->guru->kode_guru ?? '?',
                            'color' => $color,
                            'tipe' => $row->tipe_jam
                        ];
                    }
                }
            }
        }

        // --- RENDER VISUAL JAM ISTIRAHAT/KOSONG ---
        foreach ($kelass as $k) {
            foreach ($dataHari as $hariObj) {
                $namaHari = $hariObj->nama_hari;
                $namaHariLower = strtolower($namaHari);

                foreach ($dataWaktu as $waktuObj) {
                    $j = $waktuObj->jam_ke;
                    $tipeSlot = $waktuObj->tipe;
                    if ($namaHariLower == 'senin' && $waktuObj->tipe_senin) $tipeSlot = $waktuObj->tipe_senin;
                    if ($namaHariLower == 'jumat' && $waktuObj->tipe_jumat) $tipeSlot = $waktuObj->tipe_jumat;

                    if ($tipeSlot === 'Tidak Ada') {
                        unset($jadwals[$k->id][$namaHari][$j]); 
                        continue;
                    }

                    if ($tipeSlot === 'Istirahat') {
                        $jadwals[$k->id][$namaHari][$j] = [ 'id' => null, 'mapel' => 'ISTIRAHAT', 'guru' => '', 'color' => 'bg-amber-50 text-amber-600 font-bold italic text-[10px]', 'tipe' => 'break' ];
                    } elseif (in_array($tipeSlot, ['Upacara', 'Senam', 'Sholat Dhuha', 'Jumat Bersih', 'Pramuka'])) {
                        $jadwals[$k->id][$namaHari][$j] = [ 'id' => null, 'mapel' => strtoupper($tipeSlot), 'guru' => '', 'color' => 'bg-cyan-50 text-cyan-600 font-bold italic text-[10px]', 'tipe' => 'kegiatan' ];
                    }

                    if (!isset($jadwals[$k->id][$namaHari][$j]['id']) && !isset($jadwals[$k->id][$namaHari][$j]['tipe'])) {
                        $jadwals[$k->id][$namaHari][$j] = [ 'id' => null, 'mapel' => '', 'guru' => '', 'kode_mapel' => '', 'kode_guru' => '', 'color' => 'bg-slate-50', 'tipe' => 'empty' ];
                    }
                }
            }
        }

        $tahunAktif = TahunPelajaran::getActive();
        $judulTahun = $tahunAktif ? "{$tahunAktif->tahun} Semester {$tahunAktif->semester}" : date('Y') . '/' . (date('Y') + 1);

        return view('penjadwalan.jadwal', compact('kelass', 'jadwals', 'judulTahun', 'gurusList', 'kelassList', 'reqGuru', 'reqKelas', 'dataHari', 'dataWaktu'));
    }

    // =======================================================
    // 2. FUNGSI GENERATE (TRANSLATE JAM AI KE JAM FISIK)
    // =======================================================
    public function generate(Request $request)
    {
        set_time_limit(600);
        try {
            $waktuList = MasterWaktu::orderBy('jam_ke')->get();

            $slotMapping = []; 
            $pToT = []; 

            $hariAktif = MasterHari::getActiveDays()->map(function($h) use ($waktuList, &$slotMapping, &$pToT) {
                $namaHariLower = strtolower($h->nama_hari);
                $teachingSlotCounter = 1;

                foreach($waktuList as $w) {
                    $tipeSlot = $w->tipe;
                    if ($namaHariLower == 'senin' && $w->tipe_senin) $tipeSlot = $w->tipe_senin;
                    // FIX TYPO: Dihapus $waktuObj->tipe_jumat menjadi $w->tipe_jumat
                    if ($namaHariLower == 'jumat' && $w->tipe_jumat) $tipeSlot = $w->tipe_jumat;

                    if ($tipeSlot !== 'Tidak Ada') {
                        if (!in_array($tipeSlot, ['Istirahat', 'Upacara', 'Senam', 'Sholat Dhuha', 'Jumat Bersih', 'Pramuka'])) {
                            $slotMapping[$h->nama_hari][$teachingSlotCounter] = $w->jam_ke;
                            $pToT[$h->nama_hari][$w->jam_ke] = $teachingSlotCounter;
                            $teachingSlotCounter++;
                        }
                    }
                }
                return [
                    'nama' => $h->nama_hari,
                    'max_jam' => $teachingSlotCounter - 1 
                ];
            });

            $gurus = Guru::all()->map(function ($guru) {
                return [
                    'id' => $guru->id,
                    'nama' => $guru->nama_guru,
                    'hari_mengajar' => $guru->hari_mengajar ? json_decode($guru->hari_mengajar, true) : [],
                ];
            });

            $assignments = Jadwal::all()->map(function ($j) {
                return [ 'id' => $j->id, 'guru_id' => $j->guru_id, 'kelas_id' => $j->kelas_id, 'mapel_id' => $j->mapel_id, 'jumlah_jam' => $j->jumlah_jam ];
            });

            $kelassData = Kelas::all()->map(function ($k) {
                return [ 'id' => $k->id, 'nama_kelas' => $k->nama_kelas, 'limit_harian' => $k->limit_harian ?? 10, 'limit_jumat' => $k->limit_jumat ?? 7, 'max_jam_total' => $k->max_jam ?? 48 ];
            });

            $dataInput = [
                'hari_aktif' => $hariAktif,
                'gurus' => $gurus,
                'kelass' => $kelassData,
                'assignments' => $assignments,
            ];

            $jsonPath = storage_path('app/input_solver.json');
            file_put_contents($jsonPath, json_encode($dataInput));

            $scriptPath = base_path('python/scheduler.py');
            $process = new Process(['python', $scriptPath, $jsonPath]);
            $process->setTimeout(600);
            $process->run();

            if (!$process->isSuccessful()) throw new ProcessFailedException($process);
            $result = json_decode($process->getOutput(), true);

            if (isset($result['status']) && ($result['status'] === 'OPTIMAL' || $result['status'] === 'FEASIBLE')) {
                DB::beginTransaction();
                try {
                    foreach ($result['solution'] as $item) {
                        $hari = $item['hari'];
                        $tSlot = $item['jam']; 
                        $pSlot = $slotMapping[$hari][$tSlot] ?? $tSlot; 

                        DB::table('jadwals')->where('id', $item['id'])->update([ 'hari' => $hari, 'jam' => $pSlot, 'updated_at' => now() ]);
                    }
                    DB::commit();
                    return redirect()->route('jadwal.index')->with('success', $result['message'])->with('waktu_komputasi', $result['waktu_komputasi_detik'] ?? null);
                } catch (\Exception $e) {
                    DB::rollBack();
                    throw $e;
                }
            } else {
                return redirect()->route('jadwal.index')->with('error', 'Gagal: ' . ($result['message'] ?? 'Solusi tidak ditemukan.'));
            }

        } catch (\Exception $e) {
            return redirect()->route('jadwal.index')->with('error', 'Error: ' . $e->getMessage());
        }
    }

    /**
     * Export Excel
     */
    public function export()
    {
        $tahunAktif = TahunPelajaran::getActive();
        $judulTahun = $tahunAktif ? "{$tahunAktif->tahun} Semester {$tahunAktif->semester}" : date('Y');

        $fileName = 'Jadwal_Pelajaran_' . str_replace(['/', '\\'], '-', $judulTahun) . '.xlsx';

        return Excel::download(new JadwalExport($judulTahun), $fileName);
    }
}