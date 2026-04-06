<?php

namespace App\Http\Controllers;

use App\Models\Jadwal;
use App\Models\Guru;
use App\Models\Kelas;
use App\Models\Mapel;
use App\Models\TahunPelajaran; 
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\JadwalExport;

class JadwalController extends Controller
{
    /**
     * Menampilkan Grid Jadwal di Website
     */
    public function index(Request $request) 
    {
        // --- FITUR BARU: Tangkap Request Filter ---
        $reqGuru = $request->input('guru_id');
        $reqKelas = $request->input('kelas_id');

        // Ambil data list untuk dropdown select di view
        $gurusList = Guru::orderBy('nama_guru')->get();
        $kelassList = Kelas::orderBy('nama_kelas')->get();

        // 1. Filter Kolom Kelas
        if ($reqKelas) {
            $kelass = Kelas::where('id', $reqKelas)->orderBy('nama_kelas')->get();
        } else {
            $kelass = Kelas::orderBy('nama_kelas')->get();
        }

        // 2. Filter Data Jadwal
        $query = Jadwal::with(['guru', 'mapel', 'kelas'])
            ->whereNotNull('hari')
            ->whereNotNull('jam');

        if ($reqGuru) {
            $query->where('guru_id', $reqGuru);
        }
        if ($reqKelas) {
            $query->where('kelas_id', $reqKelas);
        }

        $rawJadwals = $query->get();

        $jadwals = [];
        $hariList = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'];

        // 3. Inisialisasi grid kosong
        foreach ($kelass as $k) {
            foreach ($hariList as $h) {
                for ($j = 0; $j <= 11; $j++) {
                    $jadwals[$k->id][$h][$j] = null;
                }
            }
        }

        // 4. Mapping data jadwal dari Database ke Grid Array
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
                if ($jamSekarang <= 11) {
                    // Pengecekan ekstra: Jika kelas tidak ada di array (efek filter), skip
                    if(!isset($jadwals[$row->kelas_id])) continue; 

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

        // 5. Logika "Gap Detection" (Visual Jam Kosong di Tabel)
        foreach ($kelass as $k) {
            foreach ($hariList as $h) {
                $startJam = 1;
                $endJam = ($h == 'Jumat') ? 8 : 10;

                for ($j = $startJam; $j <= $endJam; $j++) {
                    if (($j == 4 || $j == 8) && $h != 'Jumat') continue;
                    if (($j == 4 || $j == 7) && $h == 'Jumat') continue; 

                    if (($jadwals[$k->id][$h][$j] ?? null) === null) {
                        $jadwals[$k->id][$h][$j] = [
                            'id' => null,
                            'mapel' => '',
                            'guru' => '',
                            'kode_mapel' => '',
                            'kode_guru' => '',
                            'color' => 'bg-slate-50', 
                            'tipe' => 'empty'
                        ];
                    }
                }
            }
        }

        $tahunAktif = TahunPelajaran::getActive();
        $judulTahun = $tahunAktif
            ? "{$tahunAktif->tahun} Semester {$tahunAktif->semester}"
            : date('Y') . '/' . (date('Y') + 1);

        // --- TAMBAHAN: Melempar variabel list filter ke View ---
        return view('penjadwalan.jadwal', compact(
            'kelass', 'jadwals', 'judulTahun', 
            'gurusList', 'kelassList', 'reqGuru', 'reqKelas'
        ));
    }

    /**
     * Proses Generate Algoritma (Solver Python)
     */
    public function generate(Request $request)
    {
        set_time_limit(600); 

        try {
            // A. Siapkan Data Guru
            $gurus = Guru::all()->map(function ($guru) {
                return [
                    'id' => $guru->id,
                    'nama' => $guru->nama_guru,
                    // --- BAGIAN INI YANG DITAMBAHKAN AGAR PYTHON TAHU HARI MENGAJARNYA ---
                    'hari_mengajar' => $guru->hari_mengajar ? json_decode($guru->hari_mengajar, true) : [],
                    'waktu_kosong' => [] // Kosongkan karena fitur dihapus
                ];
            });

            // B. Data Beban Mengajar (Assignments)
            $rawAssignments = Jadwal::all();
            $assignments = $rawAssignments->map(function ($j) {
                return [
                    'id' => $j->id,
                    'guru_id' => $j->guru_id,
                    'kelas_id' => $j->kelas_id,
                    'mapel_id' => $j->mapel_id,
                    'jumlah_jam' => $j->jumlah_jam,
                    'tipe_jam' => $j->tipe_jam,
                ];
            });

            // C. Data Kelas & Limit Harian
            $kelassData = Kelas::all()->map(function ($k) use ($rawAssignments) {
                $totalJamMingguan = $rawAssignments->where('kelas_id', $k->id)->sum('jumlah_jam');
                
                $maxHarian = 10;
                $kapasitasSeninKamis = $maxHarian * 4;
                $sisaUntukJumat = $totalJamMingguan - $kapasitasSeninKamis;
                
                $limitJumat = max(4, min($sisaUntukJumat, 11));
                if ($sisaUntukJumat <= 0) $limitJumat = 5;

                return [
                    'id' => $k->id,
                    'nama_kelas' => $k->nama_kelas,
                    'limit_harian' => $maxHarian,
                    'limit_jumat' => $limitJumat
                ];
            });

            // D. Susun Payload JSON (Tanpa Mapel Constraints)
            $dataInput = [
                'gurus' => $gurus,
                'kelass' => $kelassData,
                'assignments' => $assignments,
                'mapel_constraints' => [] // Kosongkan karena fitur dihapus
            ];

            // E. Simpan JSON & Eksekusi Python
            $jsonPath = storage_path('app/input_solver.json');
            file_put_contents($jsonPath, json_encode($dataInput));

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

            $result = json_decode($process->getOutput(), true);

            if (!$result) {
                return redirect()->route('jadwal.index')->with('error', 'Output Python kosong/error.');
            }

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
                    
                    return redirect()->route('jadwal.index')
                        ->with('success', $result['message'])
                        ->with('waktu_komputasi', $result['waktu_komputasi_detik'] ?? null);

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

        $judulTahun = $tahunAktif 
            ? "{$tahunAktif->tahun} Semester {$tahunAktif->semester}" 
            : date('Y') . '/' . (date('Y') + 1);

        $fileName = 'Jadwal_Pelajaran_';
        if ($tahunAktif) {
            $cleanTahun = str_replace(['/', '\\'], '-', $tahunAktif->tahun);
            $fileName .= "{$cleanTahun}_{$tahunAktif->semester}";
        } else {
            $fileName .= date('Y');
        }
        $fileName .= '.xlsx';

        return Excel::download(new JadwalExport($judulTahun), $fileName);
    }
}