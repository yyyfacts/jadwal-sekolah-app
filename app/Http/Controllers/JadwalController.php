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
    public function index(Request $request)
    {
        $reqGuru = $request->input('guru_id');
        $reqKelas = $request->input('kelas_id');

        $gurusList = Guru::orderBy('nama_guru')->get();
        $kelassList = Kelas::orderBy('nama_kelas')->get();

        $dataHari = MasterHari::getActiveDays(); 
        $hariList = $dataHari->pluck('nama_hari')->toArray();
        $dataWaktu = MasterWaktu::orderBy('waktu_mulai')->get(); 
        
        $minJam = $dataWaktu->min('jam_ke') ?? 0;
        $maxJam = $dataWaktu->max('jam_ke') ?? 15;

        $kelass = $reqKelas ? Kelas::with('waliKelas')->where('id', $reqKelas)->orderBy('nama_kelas')->get() : Kelas::with('waliKelas')->orderBy('nama_kelas')->get();

        // 1. AMBIL JADWAL OFFLINE UNTUK TABEL RAKSASA
        $query = Jadwal::with(['guru', 'mapel', 'kelas'])
            ->whereNotNull('hari')->whereNotNull('jam')
            ->where(function($q) {
                $q->where('status', 'offline')->orWhereNull('status');
            });
            
        if ($reqGuru) $query->where('guru_id', $reqGuru);
        if ($reqKelas) $query->where('kelas_id', $reqKelas);
        $rawJadwals = $query->get();
        $jadwals = [];

        // Siapkan Canvas Kosong
        foreach ($kelass as $k) {
            foreach ($hariList as $h) {
                foreach ($dataWaktu as $waktu) {
                    if ($waktu->jam_ke !== null) {
                        $jadwals[$k->id][$h][$waktu->jam_ke] = null;
                    }
                }
            }
        }

        // Cari Jam Belajar Saja (Abaikan Istirahat/Upacara)
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
                    if ($waktuObj->jam_ke !== null) {
                        $belajarSlots[$namaHari][] = $waktuObj->jam_ke;
                    }
                }
            }
        }

        // Petakan Mapel ke Tabel agar lompatin Istirahat
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

        // 2. AMBIL JADWAL ONLINE
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
            $waktuList = MasterWaktu::orderBy('waktu_mulai')->get();
            $slotMapping = []; 

            // Hitung Max JP Murni Belajar per Hari
            $hariAktif = MasterHari::getActiveDays()->map(function($h) use ($waktuList, &$slotMapping) {
                $namaHariLower = strtolower($h->nama_hari);
                $teachingSlotCounter = 1;

                foreach($waktuList as $w) {
                    $tipeSlot = $w->tipe;
                    if ($namaHariLower == 'senin' && $w->tipe_senin) $tipeSlot = $w->tipe_senin;
                    if ($namaHariLower == 'jumat' && $w->tipe_jumat) $tipeSlot = $w->tipe_jumat;

                    if ($tipeSlot !== 'Tidak Ada' && !in_array($tipeSlot, ['Istirahat', 'Upacara', 'Senam', 'Sholat Dhuha', 'Jumat Bersih', 'Pramuka'])) {
                        // Petakan urutan belajar (1,2,3) ke jam_ke fisik di DB (1,2,4)
                        $slotMapping[$h->nama_hari][$teachingSlotCounter] = $w->jam_ke;
                        $teachingSlotCounter++;
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
                    'waktu_kosong' => $guru->waktu_kosong ? json_decode($guru->waktu_kosong, true) : [],
                ];
            });

            // AMBIL YANG OFFLINE SAJA
            $assignments = Jadwal::where(function($q) {
                    $q->where('status', 'offline')->orWhereNull('status');
                })->get()->map(function ($j) {
                    return [ 'id' => $j->id, 'guru_id' => $j->guru_id, 'kelas_id' => $j->kelas_id, 'mapel_id' => $j->mapel_id, 'jumlah_jam' => $j->jumlah_jam ];
                });

            $kelassData = Kelas::all()->map(function ($k) {
                return [ 'id' => $k->id, 'nama_kelas' => $k->nama_kelas, 'limit_harian' => $k->limit_harian ?? 10, 'limit_jumat' => $k->limit_jumat ?? 7, 'max_jam_total' => $k->max_jam ?? 48 ];
            });

            $dataInput = [
                'hari_aktif' => $hariAktif, 'gurus' => $gurus, 'kelass' => $kelassData, 'assignments' => $assignments,
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
                        
                        // TERJEMAHKAN: Slot Ngajar AI ke ID jam_ke Database pakai Mapping
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