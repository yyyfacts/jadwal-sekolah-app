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
        
        $kelass = $reqKelas 
            ? Kelas::with('waliKelas')->where('id', $reqKelas)->orderBy('nama_kelas')->get() 
            : Kelas::with('waliKelas')->orderBy('nama_kelas')->get();

        $query = Jadwal::with(['guru', 'mapel', 'kelas', 'masterHari'])
            ->whereNotNull('master_hari_id')->whereNotNull('jam')
            ->where(function($q) {
                $q->where('status', 'offline')->orWhereNull('status');
            });
            
        if ($reqGuru) $query->where('guru_id', $reqGuru);
        if ($reqKelas) $query->where('kelas_id', $reqKelas);
        
        $rawJadwals = $query->get();
        $jadwals = [];

        // Inisialisasi Grid Jadwal
        foreach ($kelass as $k) {
            foreach ($dataHari as $hariObj) {
                foreach ($hariObj->waktuHaris as $waktu) {
                    if ($waktu->jam_ke !== null) {
                        $jadwals[$k->id][$hariObj->nama_hari][$waktu->jam_ke] = null;
                    }
                }
            }
        }

        // Mapping Slot Belajar (Menghindari Istirahat/Upacara)
        $belajarSlots = [];
        foreach ($dataHari as $hariObj) {
            $belajarSlots[$hariObj->nama_hari] = $hariObj->waktuHaris
                ->whereNotIn('tipe', ['Istirahat', 'Upacara', 'Senam', 'Sholat', 'Sholat Dhuha', 'Jumat Bersih', 'Pramuka', 'Tidak Ada'])
                ->pluck('jam_ke')->toArray();
        }

        foreach ($rawJadwals as $row) {
            $durasi = $row->jumlah_jam;
            $hari = $row->masterHari->nama_hari ?? null;
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

        $onlineJadwals = Jadwal::with(['guru', 'mapel', 'kelas'])->where('status', 'online');
        if ($reqGuru) $onlineJadwals->where('guru_id', $reqGuru);
        if ($reqKelas) $onlineJadwals->where('kelas_id', $reqKelas);
        $onlineJadwals = $onlineJadwals->orderBy('kelas_id')->get();

        $tahunAktif = TahunPelajaran::getActive();
        $judulTahun = $tahunAktif ? "{$tahunAktif->tahun} Semester {$tahunAktif->semester}" : date('Y') . '/' . (date('Y') + 1);

        return view('penjadwalan.jadwal', compact('kelass', 'jadwals', 'onlineJadwals', 'judulTahun', 'gurusList', 'kelassList', 'reqGuru', 'reqKelas', 'dataHari'));
    }

    public function generate(Request $request)
    {
        set_time_limit(1500);
        try {
            $dataHari = MasterHari::with(['waktuHaris' => function($q) {
                $q->orderBy('waktu_mulai');
            }])->where('is_active', true)->get();

            $slotMapping = []; 
            $reverseSlotMapping = [];

            $hariAktif = $dataHari->map(function($hariObj) use (&$slotMapping, &$reverseSlotMapping) {
                $teachingSlotCounter = 1;
                foreach($hariObj->waktuHaris as $w) {
                    if ($w->tipe !== 'Tidak Ada' && !in_array($w->tipe, ['Istirahat', 'Upacara', 'Senam', 'Sholat', 'Sholat Dhuha', 'Jumat Bersih', 'Pramuka'])) {
                        $slotMapping[$hariObj->nama_hari][$teachingSlotCounter] = (int)$w->jam_ke;
                        $reverseSlotMapping[$hariObj->nama_hari][(int)$w->jam_ke] = $teachingSlotCounter; 
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
                    'id' => (int)$guru->id,
                    'nama' => $guru->nama_guru,
                    'hari_mengajar' => is_array($guru->hari_mengajar) ? $guru->hari_mengajar : json_decode($guru->hari_mengajar, true) ?? [],
                ];
            });

            $mapels = Mapel::all()->map(function ($m) {
                return [
                    'id' => (int)$m->id,
                    'nama_mapel' => $m->nama_mapel
                ];
            });

            $assignments = Jadwal::with(['mapel', 'masterHari'])
                ->where(function($q) {
                    $q->where('status', 'offline')->orWhereNull('status');
                })->get()->map(function ($j) use ($reverseSlotMapping) {
                    $locked_hari = $j->masterHari->nama_hari ?? null;
                    $locked_jam_fisik = $j->jam ?? null;
                    $locked_teaching_slot = null;
                    
                    if ($locked_hari && $locked_jam_fisik !== null) {
                        $locked_teaching_slot = $reverseSlotMapping[$locked_hari][(int)$locked_jam_fisik] ?? null;
                    }

                    return [ 
                        'id' => (int)$j->id, 
                        'guru_id' => (int)$j->guru_id, 
                        'kelas_id' => (int)$j->kelas_id, 
                        'mapel_id' => (int)$j->mapel_id, 
                        'jumlah_jam' => (int)$j->jumlah_jam,
                        'status' => $j->status ?? 'offline',
                        'locked_hari' => $locked_hari,
                        'locked_jam' => $locked_teaching_slot,
                    ];
                });

            $kelassData = Kelas::all()->map(function ($k) {
                return [ 
                    'id' => (int)$k->id, 
                    'nama_kelas' => $k->nama_kelas, 
                    'limit_harian' => (int)($k->limit_harian ?? 10), 
                    'limit_jumat' => (int)($k->limit_jumat ?? 7)
                ];
            });

            $dataInput = [
                'hari_aktif' => $hariAktif, 
                'gurus' => $gurus, 
                'mapels' => $mapels,
                'kelass' => $kelassData, 
                'assignments' => $assignments,
            ];

            $jsonPath = storage_path('app/input_solver.json');
            file_put_contents($jsonPath, json_encode($dataInput, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            $scriptPath = base_path('python/scheduler.py');
            $process = new Process(['python', $scriptPath, $jsonPath]);
            $process->setTimeout(1500);
            $process->run();

            if (!$process->isSuccessful()) throw new ProcessFailedException($process);
            
            $result = json_decode($process->getOutput(), true);

            if (isset($result['status']) && ($result['status'] === 'OPTIMAL' || $result['status'] === 'FEASIBLE')) {
                DB::beginTransaction();
                try {
                    foreach ($result['solution'] as $item) {
                        $hariString = $item['hari'];
                        $tSlot = $item['jam']; 
                        $pSlot = $slotMapping[$hariString][$tSlot] ?? $tSlot;
                        $masterHari = $dataHari->firstWhere('nama_hari', $hariString);

                        DB::table('jadwals')->where('id', $item['id'])->update([ 
                            'master_hari_id' => $masterHari ? $masterHari->id : null,
                            'jam' => $pSlot, 
                            'updated_at' => now() 
                        ]);
                    }
                    DB::commit();
                    return redirect()->route('jadwal.index')->with('success', $result['message']);
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