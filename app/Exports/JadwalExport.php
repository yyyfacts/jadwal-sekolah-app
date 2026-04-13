<?php

namespace App\Exports;

use App\Models\Kelas;
use App\Models\Jadwal;
use App\Models\Guru;
use App\Models\Mapel;
use App\Models\MasterHari;
use App\Models\MasterWaktu;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class JadwalExport implements FromView, ShouldAutoSize, WithTitle, WithStyles
{
    protected $judulTahun;

    public function __construct($judulTahun)
    {
        $this->judulTahun = $judulTahun;
    }

    public function view(): View
    {
        $kelass = Kelas::orderBy('nama_kelas')->get();
        $gurus = Guru::orderBy('kode_guru')->get();
        $mapels = Mapel::orderBy('kode_mapel')->get();

        $dataHari = MasterHari::getActiveDays();
        $hariList = $dataHari->pluck('nama_hari')->toArray();
        $dataWaktu = MasterWaktu::getOrdered();

        $maxJam = $dataWaktu->max('jam_ke');

        $rawJadwals = Jadwal::with(['guru', 'mapel', 'kelas'])
            ->whereNotNull('hari')
            ->whereNotNull('jam')
            ->get();

        $jadwals = [];

        // ==========================================
        // 1. BUAT CANVAS KOSONG
        // ==========================================
        foreach ($kelass as $k) {
            foreach ($hariList as $h) {
                foreach ($dataWaktu as $waktu) {
                    $jadwals[$k->id][$h][$waktu->jam_ke] = null;
                }
            }
        }

        // ==========================================
        // 2. MAPPING SLOT BELAJAR (SKIP FIXED)
        // ==========================================
        $belajarSlots = [];

        foreach ($dataHari as $hariObj) {
            $namaHari = $hariObj->nama_hari;
            $namaHariLower = strtolower($namaHari);

            $belajarSlots[$namaHari] = [];
            $urutanBelajar = 1;

            foreach ($dataWaktu as $waktuObj) {
                $tipeSlot = $waktuObj->tipe;

                if ($namaHariLower == 'senin' && $waktuObj->tipe_senin) {
                    $tipeSlot = $waktuObj->tipe_senin;
                }

                if ($namaHariLower == 'jumat' && $waktuObj->tipe_jumat) {
                    $tipeSlot = $waktuObj->tipe_jumat;
                }

                // 🔥 INTI: SKIP FIXED SLOT
                if (!$waktuObj->is_fixed && $tipeSlot !== 'Tidak Ada') {
                    $belajarSlots[$namaHari][$urutanBelajar] = $waktuObj->jam_ke;
                    $urutanBelajar++;
                }
            }
        }

        // ==========================================
        // 3. MASUKKAN JADWAL HASIL GENERATE
        // ==========================================
        foreach ($rawJadwals as $row) {
            $durasi = $row->jumlah_jam;
            $hari = $row->hari;
            $jamMulaiFisik = $row->jam;

            $slotsTersedia = $belajarSlots[$hari] ?? [];
            $urutanMulai = array_search($jamMulaiFisik, $slotsTersedia);

            if ($urutanMulai !== false) {
                for ($i = 0; $i < $durasi; $i++) {
                    $urutanSekarang = $urutanMulai + $i;

                    if (isset($slotsTersedia[$urutanSekarang])) {
                        $jamFisikSekarang = $slotsTersedia[$urutanSekarang];

                        if ($jamFisikSekarang <= $maxJam && isset($jadwals[$row->kelas_id][$hari])) {
                            $jadwals[$row->kelas_id][$hari][$jamFisikSekarang] = [
                                'kode_mapel' => $row->mapel->kode_mapel ?? '-',
                                'kode_guru'  => $row->guru->kode_guru ?? '-',
                                'color'      => $row->tipe_jam == 'double' || $row->tipe_jam == 'triple'
                                    ? 'd9e1f2'
                                    : 'ffffff'
                            ];
                        }
                    }
                }
            }
        }

        // ==========================================
        // 4. MASUKKAN SLOT FIXED (ISTIRAHAT DLL)
        // ==========================================
        foreach ($dataWaktu as $waktuObj) {
            if ($waktuObj->is_fixed) {

                foreach ($hariList as $hari) {
                    foreach ($kelass as $k) {

                        $tipeSlot = $waktuObj->tipe;

                        if (strtolower($hari) == 'senin' && $waktuObj->tipe_senin) {
                            $tipeSlot = $waktuObj->tipe_senin;
                        }

                        if (strtolower($hari) == 'jumat' && $waktuObj->tipe_jumat) {
                            $tipeSlot = $waktuObj->tipe_jumat;
                        }

                        if ($tipeSlot !== 'Tidak Ada') {
                            $jadwals[$k->id][$hari][$waktuObj->jam_ke] = [
                                'kode_mapel' => strtoupper($tipeSlot),
                                'kode_guru'  => '',
                                'color'      => 'fef3c7' // kuning (fixed)
                            ];
                        }
                    }
                }
            }
        }

        // ==========================================
        // RETURN VIEW
        // ==========================================
        return view('exports.jadwal_excel', [
            'kelass' => $kelass,
            'jadwals' => $jadwals,
            'gurus' => $gurus,
            'mapels' => $mapels,
            'judulTahun' => $this->judulTahun,
            'dataHari' => $dataHari,
            'dataWaktu' => $dataWaktu
        ]);
    }

    public function title(): string
    {
        return 'Jadwal Pelajaran';
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle($sheet->calculateWorksheetDimension())->applyFromArray([
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'font' => [
                'name' => 'Arial',
                'size' => 9
            ]
        ]);

        return [];
    }
}