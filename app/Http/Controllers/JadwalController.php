<?php

namespace App\Http\Controllers;

use App\Models\Jadwal;
use App\Models\Guru;
use App\Models\Kelas;
use App\Models\Mapel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\JadwalExport;

class JadwalController extends Controller
{
    public function index()
    {
        $kelass = Kelas::orderBy('nama_kelas')->get();

        // Ambil data jadwal yang sudah ada hari & jam-nya
        $rawJadwals = Jadwal::with(['guru', 'mapel', 'kelas'])
            ->whereNotNull('hari')
            ->whereNotNull('jam')
            ->get();

        $jadwals = [];
        $hariList = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'];

        // 1. Inisialisasi grid kosong (Semua diset NULL dulu)
        foreach ($kelass as $k) {
            foreach ($hariList as $h) {
                // Kita siapkan sampai jam 10 atau 11 sesuai kebutuhan view
                for ($j = 0; $j <= 11; $j++) {
                    $jadwals[$k->id][$h][$j] = null;
                }
            }
        }

        // 2. Mapping data jadwal dari Database ke Grid
        foreach ($rawJadwals as $row) {
            $durasi = $row->jumlah_jam;
            $kode_mapel = $row->mapel->kode_mapel ?? '?';
            $kode_guru = $row->guru->kode_guru ?? '?';

            $color = match ($row->tipe_jam) {
                'double' => 'bg-blue-100 text-blue-800 border-blue-200',
                'triple' => 'bg-purple-100 text-purple-800 border-purple-200',
                default => 'bg-white text-slate-700 border-slate-200'
            };

            for ($i = 0; $i < $durasi; $i++) {
                $jamSekarang = $row->jam + $i;
                // Pastikan tidak error offset jika jam melebihi batas
                if ($jamSekarang <= 11) {
                    $jadwals[$row->kelas_id][$row->hari][$jamSekarang] = [
                        'id' => $row->id,
                        'mapel' => $row->mapel->nama_mapel ?? '-',
                        'guru' => $row->guru->nama_guru ?? '-',
                        'kode_mapel' => $kode_mapel,
                        'kode_guru' => $kode_guru,
                        'color' => $color,
                        'tipe' => $row->tipe_jam
                    ];
                }
            }
        }

        // 3. LOGIKA TAMBAHAN: SCANNING & LABELING JAM BOLONG
        // Ini menjawab request: "JIKA MASIH BOLONG DIKASI KOMENT"
        foreach ($kelass as $k) {
            foreach ($hariList as $h) {
                
                // Tentukan range jam efektif belajar (sesuaikan dengan sekolah Anda)
                // Senin & Jumat biasanya mulai jam 0 (Upacara/Senam), lainnya jam 1
                $startJam = ($h == 'Senin' || $h == 'Jumat') ? 1 : 1; 
                $endJam = ($h == 'Jumat') ? 8 : 10; // Contoh: Jumat pulang jam 8, lainnya jam 10

                for ($j = $startJam; $j <= $endJam; $j++) {
                    
                    // Lewati jam istirahat agar tidak ditandai "KOSONG" (Sesuaikan jam istirahat Anda)
                    // Contoh: Jam ke-4 dan ke-8 adalah istirahat
                    if ($j == 4 || $j == 8) continue;

                    // Jika slot di array masih NULL, berarti ini BOLONG
                    if (($jadwals[$k->id][$h][$j] ?? null) === null) {
                        
                        $jadwals[$k->id][$h][$j] = [
                            'id' => null,
                            'mapel' => 'JAM KOSONG',   // <-- Label Komentar
                            'guru' => 'Tidak ada KBM',
                            'kode_mapel' => '',
                            'kode_guru' => '',
                            // Styling: Merah putus-putus agar terlihat sebagai warning/kosong
                            'color' => 'bg-red-50 text-red-500 border-red-300 border-dashed italic opacity-80', 
                            'tipe' => 'empty'
                        ];
                    }
                }
            }
        }

        return view('penjadwalan.jadwal', compact('kelass', 'jadwals'));
    }

    public function generate(Request $request)
    {
        set_time_limit(600);

        try {
            // 1. DATA GURU & WAKTU SIBUK GURU
            $gurus = Guru::with('waktuKosong')->get()->map(function ($guru) {
                return [
                    'id' => $guru->id,
                    'nama' => $guru->nama_guru,
                    'waktu_kosong' => $guru->waktuKosong->map(function ($wk) {
                        return ['hari' => $wk->hari, 'jam' => $wk->jam];
                    })->toArray()
                ];
            });

            // 2. DATA MAPEL & WAKTU SIBUK MAPEL
            $mapelConstraints = Mapel::whereHas('waktuKosong')->with('waktuKosong')->get()->map(function ($m) {
                return [
                    'id' => $m->id,
                    'waktu_kosong' => $m->waktuKosong->map(function ($wk) {
                        return ['hari' => $wk->hari, 'jam' => $wk->jam];
                    })->toArray()
                ];
            });

            // 3. DATA BEBAN MENGAJAR
            $assignments = Jadwal::with(['mapel', 'guru', 'kelas'])->get()->map(function ($j) {
                return [
                    'id' => $j->id,
                    'guru_id' => $j->guru_id,
                    'kelas_id' => $j->kelas_id,
                    'mapel_id' => $j->mapel_id,
                    'jumlah_jam' => $j->jumlah_jam, 
                    'tipe_jam' => $j->tipe_jam,
                ];
            });

            // 4. DATA KELAS & LIMIT JAM
            $kelassData = Kelas::all()->map(function ($k) {
                $limitHarian = $k->limit_harian ?? 10;
                $limitJumat = $k->limit_jumat ?? 7;

                return [
                    'id' => $k->id,
                    'nama_kelas' => $k->nama_kelas,
                    'limit_harian' => $limitHarian,
                    'limit_jumat' => $limitJumat
                ];
            });
            
            // Susun data untuk dikirim ke Python
            $dataInput = [
                'gurus' => $gurus,
                'kelass' => $kelassData,
                'assignments' => $assignments,
                'mapel_constraints' => $mapelConstraints
            ];

            // Simpan JSON
            $jsonPath = storage_path('app/input_solver.json');
            file_put_contents($jsonPath, json_encode($dataInput));

            // Jalankan Python
            $scriptPath = base_path('python/scheduler.py');
            if (!file_exists($scriptPath)) {
                return redirect()->route('jadwal.index')->with('error', 'Script Python tidak ditemukan.');
            }

            $process = new Process(['python', $scriptPath, $jsonPath]);
            $process->setTimeout(600);
            $process->run();

            if (!$process->isSuccessful()) {
                throw new ProcessFailedException($process);
            }

            $output = $process->getOutput();
            $result = json_decode($output, true);

            if (!$result) {
                return redirect()->route('jadwal.index')->with('error', 'Output Python kosong/invalid.');
            }

            // Simpan Hasil jika Sukses
            if (isset($result['status']) && ($result['status'] === 'OPTIMAL' || $result['status'] === 'FEASIBLE')) {
                DB::beginTransaction();
                try {
                    foreach ($result['solution'] as $item) {
                        Jadwal::where('id', $item['id'])->update([
                            'hari' => $item['hari'],
                            'jam' => $item['jam']
                        ]);
                    }
                    DB::commit();
                    return redirect()->route('jadwal.index')->with('success', $result['message']);
                } catch (\Exception $e) {
                    DB::rollBack();
                    throw $e;
                }
            } else {
                return redirect()->route('jadwal.index')->with('error', 'Gagal: ' . ($result['message'] ?? 'Infeasible'));
            }

        } catch (\Exception $e) {
            return redirect()->route('jadwal.index')->with('error', 'Error: ' . $e->getMessage());
        }
    }

    public function export()
    {
        return Excel::download(new JadwalExport, 'Jadwal_Pelajaran.xlsx');
    }
}