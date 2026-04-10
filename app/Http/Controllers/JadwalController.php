<?php

namespace App\Http\Controllers;

use App\Models\Jadwal;
use App\Models\Guru;
use App\Models\Kelas;
use App\Models\Mapel;
use App\Models\TahunPelajaran;
use App\Models\MasterHari; // Import Model Baru
use App\Models\MasterWaktu; // Import Model Baru
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
    public function index(Request $request)
    {
        $reqGuru = $request->input('guru_id');
        $reqKelas = $request->input('kelas_id');

        $gurusList = Guru::orderBy('nama_guru')->get();
        $kelassList = Kelas::orderBy('nama_kelas')->get();

        // --- 1. AMBIL DATA HARI & WAKTU DARI DATABASE ---
        $dataHari = MasterHari::getActiveDays(); // Ambil hari yang is_active = true
        $hariList = $dataHari->pluck('nama_hari')->toArray();
        
        $dataWaktu = MasterWaktu::getOrdered(); // Ambil urutan jam pelajaran
        $totalSlotJam = $dataWaktu->count();

        if ($reqKelas) {
            $kelass = Kelas::where('id', $reqKelas)->orderBy('nama_kelas')->get();
        } else {
            $kelass = Kelas::orderBy('nama_kelas')->get();
        }

        $query = Jadwal::with(['guru', 'mapel', 'kelas'])
            ->whereNotNull('hari')
            ->whereNotNull('jam');

        if ($reqGuru) { $query->where('guru_id', $reqGuru); }
        if ($reqKelas) { $query->where('kelas_id', $reqKelas); }

        $rawJadwals = $query->get();
        $jadwals = [];

        // --- 2. INISIALISASI GRID KOSONG (BERDASARKAN MASTER WAKTU) ---
        foreach ($kelass as $k) {
            foreach ($hariList as $h) {
                // Inisialisasi sesuai jumlah slot di Master Waktu
                for ($j = 1; $j <= $totalSlotJam; $j++) {
                    $jadwals[$k->id][$h][$j] = null;
                }
            }
        }

        // --- 3. MAPPING DATA DARI DB KE GRID ---
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
                // Cek agar tidak melebihi kapasitas slot jam yang tersedia
                if ($jamSekarang <= $totalSlotJam) {
                    if (!isset($jadwals[$row->kelas_id])) continue;

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

        // --- 4. LOGIKA VISUAL JAM KOSONG & ISTIRAHAT ---
        foreach ($kelass as $k) {
            foreach ($dataHari as $hariObj) {
                $namaHari = $hariObj->nama_hari;
                $limitJamHariIni = $hariObj->max_jam;

                foreach ($dataWaktu as $waktuObj) {
                    $j = $waktuObj->jam_ke;

                    // Skip jika jam melebihi batas max harian hari tersebut
                    if ($j > $limitJamHariIni) continue;

                    // Jika ini jam Istirahat, beri tanda visual khusus
                    if ($waktuObj->tipe === 'Istirahat') {
                        $jadwals[$k->id][$namaHari][$j] = [
                            'id' => null,
                            'mapel' => 'ISTIRAHAT',
                            'guru' => '',
                            'color' => 'bg-amber-50 text-amber-600 font-bold italic text-[10px]',
                            'tipe' => 'break'
                        ];
                        continue;
                    }

                    // Jika masih null setelah mapping, berarti ini jam kosong
                    if (($jadwals[$k->id][$namaHari][$j] ?? null) === null) {
                        $jadwals[$k->id][$namaHari][$j] = [
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
        $judulTahun = $tahunAktif ? "{$tahunAktif->tahun} Semester {$tahunAktif->semester}" : date('Y') . '/' . (date('Y') + 1);

        return view('penjadwalan.jadwal', compact(
            'kelass',
            'jadwals',
            'judulTahun',
            'gurusList',
            'kelassList',
            'reqGuru',
            'reqKelas',
            'dataHari',
            'dataWaktu'
        ));
    }

    /**
     * Proses Generate Algoritma (Sekarang Mengirim Data Hari Dinamis ke Python)
     */
    public function generate(Request $request)
    {
        set_time_limit(600);

        try {
            // A. Data Hari Aktif (Dikirim agar Python tahu batas per hari)
            $hariAktif = MasterHari::getActiveDays()->map(function($h) {
                return [
                    'nama' => $h->nama_hari,
                    'max_jam' => $h->max_jam
                ];
            });

            // B. Siapkan Data Guru
            $gurus = Guru::all()->map(function ($guru) {
                return [
                    'id' => $guru->id,
                    'nama' => $guru->nama_guru,
                    'hari_mengajar' => $guru->hari_mengajar ? json_decode($guru->hari_mengajar, true) : [],
                ];
            });

            // C. Data Beban Mengajar (Assignments)
            $assignments = Jadwal::all()->map(function ($j) {
                return [
                    'id' => $j->id,
                    'guru_id' => $j->guru_id,
                    'kelas_id' => $j->kelas_id,
                    'mapel_id' => $j->mapel_id,
                    'jumlah_jam' => $j->jumlah_jam, // Nanti bisa dipisah offline/online di sini
                ];
            });

            // D. Data Kelas
            $kelassData = Kelas::all()->map(function ($k) {
                return [
                    'id' => $k->id,
                    'nama_kelas' => $k->nama_kelas
                ];
            });

            // E. Susun Payload JSON Lengkap
            $dataInput = [
                'hari_aktif' => $hariAktif,
                'gurus' => $gurus,
                'kelass' => $kelassData,
                'assignments' => $assignments,
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

            $result = json_decode($process->getOutput(), true);

            if (!$result) {
                return redirect()->route('jadwal.index')->with('error', 'Output Python kosong/error.');
            }

            if (isset($result['status']) && ($result['status'] === 'OPTIMAL' || $result['status'] === 'FEASIBLE')) {
                DB::beginTransaction();
                try {
                    foreach ($result['solution'] as $item) {
                        DB::table('jadwals')
                            ->where('id', $item['id'])
                            ->update([
                                'hari' => $item['hari'],
                                'jam' => $item['jam'],
                                'updated_at' => now()
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
        $judulTahun = $tahunAktif ? "{$tahunAktif->tahun} Semester {$tahunAktif->semester}" : date('Y');

        $fileName = 'Jadwal_Pelajaran_' . str_replace(['/', '\\'], '-', $judulTahun) . '.xlsx';

        return Excel::download(new JadwalExport($judulTahun), $fileName);
    }
}