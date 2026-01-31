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
    /**
     * Menampilkan Grid Jadwal
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

        // 1. Inisialisasi grid kosong (Semua diset NULL dulu)
        foreach ($kelass as $k) {
            foreach ($hariList as $h) {
                // Siapkan slot sampai jam 11 untuk keamanan array
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
                // Pastikan tidak error offset
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

        // 3. LOGIKA SCANNING "BOLONG" (GAP DETECTION)
        // Memberi label merah pada jam kosong agar ketahuan
        foreach ($kelass as $k) {
            foreach ($hariList as $h) {

                // Konfigurasi Range Jam Sekolah
                // Senin-Kamis: Jam 1 s.d 10
                // Jumat: Jam 1 s.d 8 (atau sesuaikan dengan kebutuhan visual)
                $startJam = 1;
                $endJam = ($h == 'Jumat') ? 8 : 10;

                for ($j = $startJam; $j <= $endJam; $j++) {

                    // SKIP Jam Istirahat (Misal jam 4 dan 8)
                    // Jangan tandai istirahat sebagai "KOSONG"
                    if ($j == 4 || $j == 8)
                        continue;

                    // Jika slot masih NULL, berarti BOLONG
                    if (($jadwals[$k->id][$h][$j] ?? null) === null) {
                        $jadwals[$k->id][$h][$j] = [
                            'id' => null,
                            'mapel' => 'JAM KOSONG',   // <-- Teks yang muncul di tabel
                            'guru' => 'Tidak ada KBM',
                            'kode_mapel' => '',
                            'kode_guru' => '',
                            // Styling Visual: Merah Putus-putus
                            'color' => 'bg-red-50 text-red-500 border-red-300 border-dashed italic opacity-75',
                            'tipe' => 'empty'
                        ];
                    }
                }
            }
        }

        return view('penjadwalan.jadwal', compact('kelass', 'jadwals'));
    }

    /**
     * Proses Generate AI (Solver Python)
     */
    public function generate(Request $request)
    {
        set_time_limit(600); // Set timeout 10 menit

        try {
            // 1. DATA GURU & WAKTU SIBUK
            $gurus = Guru::with('waktuKosong')->get()->map(function ($guru) {
                return [
                    'id' => $guru->id,
                    'nama' => $guru->nama_guru,
                    'waktu_kosong' => $guru->waktuKosong->map(function ($wk) {
                        return ['hari' => $wk->hari, 'jam' => $wk->jam];
                    })->toArray()
                ];
            });

            // 2. DATA MAPEL CONSTRAINT
            $mapelConstraints = Mapel::whereHas('waktuKosong')->with('waktuKosong')->get()->map(function ($m) {
                return [
                    'id' => $m->id,
                    'waktu_kosong' => $m->waktuKosong->map(function ($wk) {
                        return ['hari' => $wk->hari, 'jam' => $wk->jam];
                    })->toArray()
                ];
            });

            // 3. DATA ASSIGNMENTS (BEBAN MENGAJAR)
            // Kita ambil raw data dulu untuk perhitungan
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

            // 4. DATA KELAS & LIMIT JAM (DINAMIS - SOLUSI AGAR TIDAK BOLONG)
            $kelassData = Kelas::all()->map(function ($k) use ($rawAssignments) {

                // A. Hitung Total Jam Per Minggu untuk Kelas ini
                $totalJamMingguan = $rawAssignments->where('kelas_id', $k->id)->sum('jumlah_jam');

                // B. Hitung Kapasitas Senin-Kamis (4 hari x 10 jam = 40 jam)
                $maxHarian = 10;
                $kapasitasSeninKamis = $maxHarian * 4;

                // C. Hitung Sisa Jam untuk Jumat
                $sisaUntukJumat = $totalJamMingguan - $kapasitasSeninKamis;

                // D. Tentukan Limit Jumat
                // Jika sisa < 0 (total jam < 40), Jumat minimal 4 jam (opsional)
                // Jika sisa 8 jam (total 48), Jumat jadi 8.
                // Jika sisa 10 jam (total 50), Jumat jadi 10.
                $limitJumat = max(4, min($sisaUntukJumat, 11));

                // Pastikan tidak negatif jika beban jam sangat sedikit
                if ($sisaUntukJumat <= 0)
                    $limitJumat = 5; // Default minimal

                return [
                    'id' => $k->id,
                    'nama_kelas' => $k->nama_kelas,
                    'limit_harian' => $maxHarian, // Biasanya 10
                    'limit_jumat' => $limitJumat  // Dinamis sesuai beban
                ];
            });

            // Susun Payload JSON
            $dataInput = [
                'gurus' => $gurus,
                'kelass' => $kelassData,
                'assignments' => $assignments,
                'mapel_constraints' => $mapelConstraints
            ];

            // Simpan JSON Input
            $jsonPath = storage_path('app/input_solver.json');
            file_put_contents($jsonPath, json_encode($dataInput));

            // Eksekusi Script Python
            $scriptPath = base_path('python/scheduler.py');
            if (!file_exists($scriptPath)) {
                return redirect()->route('jadwal.index')->with('error', 'Script Python tidak ditemukan di folder python/.');
            }

            $process = new Process(['python', $scriptPath, $jsonPath]);
            $process->setTimeout(600);
            $process->run();

            // Cek Error Python
            if (!$process->isSuccessful()) {
                throw new ProcessFailedException($process);
            }

            // Baca Output
            $output = $process->getOutput();
            $result = json_decode($output, true);

            if (!$result) {
                return redirect()->route('jadwal.index')->with('error', 'Output Python kosong atau format JSON salah.');
            }

            // Proses Hasil
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
                return redirect()->route('jadwal.index')->with('error', 'Gagal Generate: ' . ($result['message'] ?? 'Solusi tidak ditemukan (Infeasible). Coba kurangi constraint.'));
            }

        } catch (\Exception $e) {
            return redirect()->route('jadwal.index')->with('error', 'System Error: ' . $e->getMessage());
        }
    }

    public function export()
    {
        return Excel::download(new JadwalExport, 'Jadwal_Pelajaran.xlsx');
    }
}