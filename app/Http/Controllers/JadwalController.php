<?php

namespace App\Http\Controllers;

use App\Models\Jadwal;
use App\Models\Guru;
use App\Models\Kelas;
use App\Models\Mapel;
use App\Models\TahunPelajaran; // Penting: Model Tahun
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
    public function index()
    {
        $kelass = Kelas::orderBy('nama_kelas')->get();

        // Ambil data jadwal yang sudah valid (punya hari & jam)
        $rawJadwals = Jadwal::with(['guru', 'mapel', 'kelas'])
            ->whereNotNull('hari')
            ->whereNotNull('jam')
            ->get();

        $jadwals = [];
        $hariList = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'];

        // 1. Inisialisasi grid kosong (Agar tidak error undefined index di View)
        foreach ($kelass as $k) {
            foreach ($hariList as $h) {
                // Siapkan slot sampai jam 11
                for ($j = 0; $j <= 11; $j++) {
                    $jadwals[$k->id][$h][$j] = null;
                }
            }
        }

        // 2. Mapping data jadwal dari Database ke Grid Array
        foreach ($rawJadwals as $row) {
            $durasi = $row->jumlah_jam;
            $kode_mapel = $row->mapel->kode_mapel ?? '?';
            $kode_guru = $row->guru->kode_guru ?? '?';

            // Styling warna berdasarkan tipe jam
            $color = match ($row->tipe_jam) {
                'double' => 'bg-blue-100 text-blue-800 border-blue-200',
                'triple' => 'bg-purple-100 text-purple-800 border-purple-200',
                default => 'bg-white text-slate-700 border-slate-200'
            };

            for ($i = 0; $i < $durasi; $i++) {
                $jamSekarang = $row->jam + $i;
                // Pastikan tidak tembus batas array (jam 11)
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

        // 3. Logika "Gap Detection" (Menandai Jam Kosong)
        foreach ($kelass as $k) {
            foreach ($hariList as $h) {
                $startJam = 1;
                $endJam = ($h == 'Jumat') ? 8 : 10;

                for ($j = $startJam; $j <= $endJam; $j++) {
                    // SKIP Jam Istirahat
                    if (($j == 4 || $j == 8) && $h != 'Jumat') continue;
                    if (($j == 4 || $j == 7) && $h == 'Jumat') continue; // Sesuaikan istirahat Jumat jika perlu

                    // Jika slot masih NULL, tandai sebagai KOSONG
                    if (($jadwals[$k->id][$h][$j] ?? null) === null) {
                        $jadwals[$k->id][$h][$j] = [
                            'id' => null,
                            'mapel' => '',
                            'guru' => '',
                            'kode_mapel' => '',
                            'kode_guru' => '',
                            'color' => 'bg-slate-50', // Warna abu-abu polos untuk kosong
                            'tipe' => 'empty'
                        ];
                    }
                }
            }
        }

        // 4. Ambil Tahun Pelajaran Aktif untuk Judul
        $tahunAktif = TahunPelajaran::getActive();
        $judulTahun = $tahunAktif
            ? "{$tahunAktif->tahun} Semester {$tahunAktif->semester}"
            : date('Y') . '/' . (date('Y') + 1);

        return view('penjadwalan.jadwal', compact('kelass', 'jadwals', 'judulTahun'));
    }

    /**
     * Proses Generate AI (Solver Python)
     */
    public function generate(Request $request)
    {
        set_time_limit(600); // Timeout 10 menit

        try {
            // A. Siapkan Data Guru & Waktu Sibuk
            $gurus = Guru::with('waktuKosong')->get()->map(function ($guru) {
                return [
                    'id' => $guru->id,
                    'nama' => $guru->nama_guru,
                    'waktu_kosong' => $guru->waktuKosong->map(function ($wk) {
                        return ['hari' => $wk->hari, 'jam' => $wk->jam];
                    })->toArray()
                ];
            });

            // B. Siapkan Constraint Mapel (Mapel yang punya waktu khusus)
            $mapelConstraints = Mapel::whereHas('waktuKosong')->with('waktuKosong')->get()->map(function ($m) {
                return [
                    'id' => $m->id,
                    'waktu_kosong' => $m->waktuKosong->map(function ($wk) {
                        return ['hari' => $wk->hari, 'jam' => $wk->jam];
                    })->toArray()
                ];
            });

            // C. Siapkan Data Beban Mengajar (Assignments)
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

            // D. Siapkan Data Kelas & Limit Harian Dinamis
            $kelassData = Kelas::all()->map(function ($k) use ($rawAssignments) {
                $totalJamMingguan = $rawAssignments->where('kelas_id', $k->id)->sum('jumlah_jam');
                
                $maxHarian = 10;
                $kapasitasSeninKamis = $maxHarian * 4;
                $sisaUntukJumat = $totalJamMingguan - $kapasitasSeninKamis;
                
                // Logika agar Jumat tidak overload atau terlalu kosong
                $limitJumat = max(4, min($sisaUntukJumat, 11));
                if ($sisaUntukJumat <= 0) $limitJumat = 5;

                return [
                    'id' => $k->id,
                    'nama_kelas' => $k->nama_kelas,
                    'limit_harian' => $maxHarian,
                    'limit_jumat' => $limitJumat
                ];
            });

            // E. Susun Payload JSON
            $dataInput = [
                'gurus' => $gurus,
                'kelass' => $kelassData,
                'assignments' => $assignments,
                'mapel_constraints' => $mapelConstraints
            ];

            // F. Simpan JSON & Eksekusi Python
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

            // G. Proses Hasil Output
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

    /**
     * Export Excel
     */
    public function export()
    {
        // 1. Ambil Tahun Aktif
        $tahunAktif = TahunPelajaran::getActive();

        // 2. Siapkan Judul Header (Agar tidak error undefined variable di View)
        $judulTahun = $tahunAktif 
            ? "{$tahunAktif->tahun} Semester {$tahunAktif->semester}" 
            : date('Y') . '/' . (date('Y') + 1);

        // 3. Siapkan Nama File
        $fileName = 'Jadwal_Pelajaran_';
        if ($tahunAktif) {
            $cleanTahun = str_replace(['/', '\\'], '-', $tahunAktif->tahun);
            $fileName .= "{$cleanTahun}_{$tahunAktif->semester}";
        } else {
            $fileName .= date('Y');
        }
        $fileName .= '.xlsx';

        // 4. FIX: Kirim $judulTahun ke Constructor Class Export
        return Excel::download(new JadwalExport($judulTahun), $fileName);
    }
}