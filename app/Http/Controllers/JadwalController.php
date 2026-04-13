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
    // =======================================================
    // 1. INDEX (TAMPILAN JADWAL)
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

        $minJam = $dataWaktu->min('jam_ke') ?? 0;
        $maxJam = $dataWaktu->max('jam_ke') ?? 10;

        $kelass = $reqKelas 
            ? Kelas::where('id', $reqKelas)->get() 
            : Kelas::orderBy('nama_kelas')->get();

        $query = Jadwal::with(['guru','mapel','kelas'])
            ->whereNotNull('hari')
            ->whereNotNull('jam');

        if ($reqGuru) $query->where('guru_id', $reqGuru);
        if ($reqKelas) $query->where('kelas_id', $reqKelas);

        $rawJadwals = $query->get();
        $jadwals = [];

        // =========================
        // GRID KOSONG
        // =========================
        foreach ($kelass as $k) {
            foreach ($hariList as $h) {
                for ($j = $minJam; $j <= $maxJam; $j++) {
                    $jadwals[$k->id][$h][$j] = null;
                }
            }
        }

        // =========================
        // SLOT BELAJAR (SKIP FIXED)
        // =========================
        $belajarSlots = [];

        foreach ($dataHari as $hariObj) {
            $namaHari = $hariObj->nama_hari;
            $namaHariLower = strtolower($namaHari);

            $belajarSlots[$namaHari] = [];

            foreach ($dataWaktu as $waktuObj) {
                $tipeSlot = $waktuObj->tipe;

                if ($namaHariLower == 'senin' && $waktuObj->tipe_senin) {
                    $tipeSlot = $waktuObj->tipe_senin;
                }

                if ($namaHariLower == 'jumat' && $waktuObj->tipe_jumat) {
                    $tipeSlot = $waktuObj->tipe_jumat;
                }

                if (!$waktuObj->is_fixed && $tipeSlot !== 'Tidak Ada') {
                    $belajarSlots[$namaHari][] = $waktuObj->jam_ke;
                }
            }
        }

        // =========================
        // MAPPING HASIL DB
        // =========================
        foreach ($rawJadwals as $row) {
            $durasi = $row->jumlah_jam;
            $hari = $row->hari;
            $jamMulai = $row->jam;

            $slots = $belajarSlots[$hari] ?? [];
            $startIndex = array_search($jamMulai, $slots);

            $color = match ($row->tipe_jam) {
                'double' => 'bg-blue-100 text-blue-800',
                'triple' => 'bg-purple-100 text-purple-800',
                default => 'bg-white'
            };

            if ($startIndex !== false) {
                for ($i = 0; $i < $durasi; $i++) {
                    if (isset($slots[$startIndex + $i])) {
                        $jam = $slots[$startIndex + $i];

                        $jadwals[$row->kelas_id][$hari][$jam] = [
                            'mapel' => $row->mapel->nama_mapel,
                            'guru' => $row->guru->nama_guru,
                            'kode_guru' => $row->guru->kode_guru,
                            'color' => $color
                        ];
                    }
                }
            }
        }

        // =========================
        // RENDER FIXED SLOT
        // =========================
        foreach ($kelass as $k) {
            foreach ($dataHari as $hariObj) {
                $hari = $hariObj->nama_hari;

                foreach ($dataWaktu as $w) {

                    $tipeSlot = $w->tipe;

                    if (strtolower($hari) == 'senin' && $w->tipe_senin) {
                        $tipeSlot = $w->tipe_senin;
                    }

                    if (strtolower($hari) == 'jumat' && $w->tipe_jumat) {
                        $tipeSlot = $w->tipe_jumat;
                    }

                    if ($tipeSlot === 'Tidak Ada') {
                        unset($jadwals[$k->id][$hari][$w->jam_ke]);
                        continue;
                    }

                    if ($w->is_fixed) {
                        $jadwals[$k->id][$hari][$w->jam_ke] = [
                            'mapel' => strtoupper($tipeSlot),
                            'guru' => '',
                            'color' => 'bg-amber-50 text-amber-600 font-bold'
                        ];
                    }

                    if (!isset($jadwals[$k->id][$hari][$w->jam_ke])) {
                        $jadwals[$k->id][$hari][$w->jam_ke] = null;
                    }
                }
            }
        }

        $tahunAktif = TahunPelajaran::getActive();
        $judulTahun = $tahunAktif
            ? "{$tahunAktif->tahun} Semester {$tahunAktif->semester}"
            : date('Y');

        return view('penjadwalan.jadwal', compact(
            'kelass','jadwals','judulTahun',
            'gurusList','kelassList','reqGuru','reqKelas',
            'dataHari','dataWaktu'
        ));
    }

    // =======================================================
    // 2. GENERATE (CSP / PYTHON)
    // =======================================================
    public function generate(Request $request)
    {
        set_time_limit(600);

        try {
            $waktuList = MasterWaktu::orderBy('jam_ke')->get();

            $slotMapping = [];

            $hariAktif = MasterHari::getActiveDays()->map(function ($h) use ($waktuList, &$slotMapping) {

                $counter = 1;

                foreach ($waktuList as $w) {

                    $tipeSlot = $w->tipe;

                    if (strtolower($h->nama_hari) == 'senin' && $w->tipe_senin) {
                        $tipeSlot = $w->tipe_senin;
                    }

                    if (strtolower($h->nama_hari) == 'jumat' && $w->tipe_jumat) {
                        $tipeSlot = $w->tipe_jumat;
                    }

                    if (!$w->is_fixed && $tipeSlot !== 'Tidak Ada') {
                        $slotMapping[$h->nama_hari][$counter] = $w->jam_ke;
                        $counter++;
                    }
                }

                return [
                    'nama' => $h->nama_hari,
                    'max_jam' => $counter - 1
                ];
            });

            $dataInput = [
                'hari_aktif' => $hariAktif,
                'gurus' => Guru::all(),
                'kelass' => Kelas::all(),
                'assignments' => Jadwal::all()
            ];

            file_put_contents(
                storage_path('app/input_solver.json'),
                json_encode($dataInput)
            );

            $process = new Process([
                'python',
                base_path('python/scheduler.py'),
                storage_path('app/input_solver.json')
            ]);

            $process->run();

            if (!$process->isSuccessful()) {
                throw new ProcessFailedException($process);
            }

            $result = json_decode($process->getOutput(), true);

            DB::beginTransaction();

            foreach ($result['solution'] as $item) {
                $pSlot = $slotMapping[$item['hari']][$item['jam']] ?? $item['jam'];

                DB::table('jadwals')
                    ->where('id', $item['id'])
                    ->update([
                        'hari' => $item['hari'],
                        'jam' => $pSlot
                    ]);
            }

            DB::commit();

            return redirect()->route('jadwal.index')->with('success', 'Generate berhasil');

        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    // =======================================================
    // 3. EXPORT
    // =======================================================
    public function export()
    {
        $tahunAktif = TahunPelajaran::getActive();

        $judulTahun = $tahunAktif
            ? "{$tahunAktif->tahun} Semester {$tahunAktif->semester}"
            : date('Y');

        return Excel::download(
            new JadwalExport($judulTahun),
            'jadwal.xlsx'
        );
    }
}