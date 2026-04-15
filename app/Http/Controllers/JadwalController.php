<?php

namespace App\Http\Controllers;

use App\Models\Jadwal;
use App\Models\Guru;
use App\Models\Kelas;
use App\Models\Mapel;
use App\Models\TahunPelajaran;
use App\Models\MasterHari;
use App\Models\WaktuHari;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\JadwalExport;

class JadwalController extends Controller
{
    public function index(Request $request)
    {
        $reqGuru = $request->input('guru_id');
        $reqKelas = $request->input('kelas_id');

        $gurusList = Guru::orderBy('nama_guru')->get();
        $kelassList = Kelas::orderBy('nama_kelas')->get();

        $dataHari = MasterHari::with(['waktuHaris' => function($q) {
            $q->orderBy('waktu_mulai');
        }])->where('is_active', true)->get(); 
        
        $hariList = $dataHari->pluck('nama_hari')->toArray();
        
        $dataWaktu = WaktuHari::select('jam_ke')->distinct()->orderBy('jam_ke')->get(); 
        
        $minJam = WaktuHari::min('jam_ke') ?? 0;
        $maxJam = WaktuHari::max('jam_ke') ?? 15;

        $kelass = $reqKelas ? Kelas::with('waliKelas')->where('id', $reqKelas)->orderBy('nama_kelas')->get() : Kelas::with('waliKelas')->orderBy('nama_kelas')->get();

        $query = Jadwal::with(['guru', 'mapel', 'kelas'])
            ->whereNotNull('hari')->whereNotNull('jam')
            ->where(function($q) {
                $q->where('status', 'offline')->orWhereNull('status');
            });
            
        if ($reqGuru) $query->where('guru_id', $reqGuru);
        if ($reqKelas) $query->where('kelas_id', $reqKelas);
        $rawJadwals = $query->get();
        $jadwals = [];

        foreach ($kelass as $k) {
            foreach ($dataHari as $hariObj) {
                $namaHari = $hariObj->nama_hari;
                foreach ($hariObj->waktuHaris as $waktu) {
                    if ($waktu->jam_ke !== null) {
                        $jadwals[$k->id][$namaHari][$waktu->jam_ke] = null;
                    }
                }
            }
        }

        $belajarSlots = [];
        foreach ($dataHari as $hariObj) {
            $namaHari = $hariObj->nama_hari;
            $belajarSlots[$namaHari] = [];
            
            foreach ($hariObj->waktuHaris as $waktuObj) {
                $tipeSlot = $waktuObj->tipe;
                
                // DISAMAKAN DAN DILENGKAPI: Sholat masuk sini!
                if (!in_array($tipeSlot, ['Istirahat', 'Upacara', 'Senam', 'Sholat', 'Sholat Dhuha', 'Jumat Bersih', 'Pramuka']) && $tipeSlot !== 'Tidak Ada') {
                    if ($waktuObj->jam_ke !== null) {
                        $belajarSlots[$namaHari][] = $waktuObj->jam_ke;
                    }
                }
            }
        }

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
                            'kode_guru' => $row->guru->kode_guru ?? '?',
                            'color' => $color,
                            'tipe' => $row->tipe_jam
                        ];
                    }
                }
            }
        }

        $queryOnline = Jadwal::with(['guru', 'mapel', 'kelas'])->where('status', 'online');
        if ($reqGuru) $queryOnline->where('guru_id', $reqGuru);
        if ($reqKelas) $queryOnline->where('kelas_id', $reqKelas);
        $onlineJadwals = $queryOnline->orderBy('kelas_id')->get();

        $tahunAktif = TahunPelajaran::getActive();
        $judulTahun = $tahunAktif ? "{$tahunAktif->tahun} Semester {$tahunAktif->semester}" : date('Y') . '/' . (date('Y') + 1);

        return view('penjadwalan.jadwal', compact('kelass', 'jadwals', 'onlineJadwals', 'judulTahun', 'gurusList', 'kelassList', 'reqGuru', 'reqKelas', 'dataHari', 'dataWaktu'));
    }

    public function generate(Request $request)
    {
        set_time_limit(600);
        try {
            $dataHari = MasterHari::with(['waktuHaris' => function($q) {
                $q->orderBy('waktu_mulai');
            }])->where('is_active', true)->get();

            $slotMapping = []; 

            $hariAktif = $dataHari->map(function($hariObj) use (&$slotMapping) {
                $teachingSlotCounter = 1;

                foreach($hariObj->waktuHaris as $w) {
                    $tipeSlot = $w->tipe;

                    // DISAMAKAN DAN DILENGKAPI: Sholat masuk sini agar AI melompatinya!
                    if ($tipeSlot !== 'Tidak Ada' && !in_array($tipeSlot, ['Istirahat', 'Upacara', 'Senam', 'Sholat', 'Sholat Dhuha', 'Jumat Bersih', 'Pramuka'])) {
                        $slotMapping[$hariObj->nama_hari][$teachingSlotCounter] = $w->jam_ke;
                        $teachingSlotCounter++;
                    }
                }
                return [
                    'nama' => $hariObj->nama_hari,
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

            $assignments = Jadwal::with('mapel')->where(function($q) {
                    $q->where('status', 'offline')->orWhereNull('status');
                })->get()->map(function ($j) {
                    return [ 
                        'id' => $j->id, 
                        'guru_id' => $j->guru_id, 
                        'kelas_id' => $j->kelas_id, 
                        'mapel_id' => $j->mapel_id, 
                        'jumlah_jam' => $j->jumlah_jam,
                        'nama_mapel' => $j->mapel->nama_mapel ?? '',
                        'batas_maksimal_jam' => $j->mapel->batas_maksimal_jam ?? null
                    ];
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

            // ==== TAMBAHKAN KODE INI SEMENTARA ====
            dd($dataInput);
            // ======================================

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

    public function export()
    {
        $tahunAktif = TahunPelajaran::getActive();
        $judulTahun = $tahunAktif ? "{$tahunAktif->tahun} Semester {$tahunAktif->semester}" : date('Y');
        return Excel::download(new JadwalExport($judulTahun), 'Jadwal_Pelajaran.xlsx');
    }
}