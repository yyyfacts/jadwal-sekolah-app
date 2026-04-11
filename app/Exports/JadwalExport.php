<?php

namespace App\Exports;

use App\Models\Kelas;
use App\Models\Jadwal;
use App\Models\Guru;
use App\Models\Mapel;
use App\Models\MasterHari;   // <-- TAMBAHKAN INI
use App\Models\MasterWaktu;  // <-- TAMBAHKAN INI
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

    // 1. CONSTRUCTOR: Menerima data judul dari Controller
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

        $rawJadwals = Jadwal::with(['guru', 'mapel', 'kelas'])->whereNotNull('hari')->whereNotNull('jam')->get();
        $jadwals = [];

        foreach ($kelass as $k) {
            foreach ($hariList as $h) {
                foreach ($dataWaktu as $waktu) {
                    $jadwals[$k->id][$h][$waktu->jam_ke] = null;
                }
            }
        }

        // BIKIN ARRAY LOMPATAN SAMA SEPERTI CONTROLLER
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
                    $belajarSlots[$namaHari][] = $waktuObj->jam_ke;
                }
            }
        }

        // ISI GRID EXCEL SAMBIL MELOMPATI ISTIRAHAT
        foreach ($rawJadwals as $row) {
            $durasi = $row->jumlah_jam;
            $hari = $row->hari;
            $jamMulaiFisik = $row->jam;
            
            $slotsTersedia = $belajarSlots[$hari] ?? [];
            $startIndex = array_search($jamMulaiFisik, $slotsTersedia);

            if ($startIndex !== false) {
                for ($i = 0; $i < $durasi; $i++) {
                    if (isset($slotsTersedia[$startIndex + $i])) {
                        $jamSekarang = $slotsTersedia[$startIndex + $i];
                        
                        if ($jamSekarang <= $maxJam && isset($jadwals[$row->kelas_id][$hari])) {
                            $jadwals[$row->kelas_id][$hari][$jamSekarang] = [
                                'kode_mapel' => $row->mapel->kode_mapel ?? '-',
                                'kode_guru'  => $row->guru->kode_guru ?? '-',
                                'color'      => $row->tipe_jam == 'double' || $row->tipe_jam == 'triple' ? 'd9e1f2' : 'ffffff'
                            ];
                        }
                    }
                }
            }
        }

        return view('exports.jadwal_excel', [
            'kelass' => $kelass, 'jadwals' => $jadwals, 'gurus' => $gurus, 'mapels' => $mapels, 
            'judulTahun' => $this->judulTahun, 'dataHari' => $dataHari, 'dataWaktu' => $dataWaktu
        ]);
    }

    public function title(): string
    {
        return 'Jadwal Pelajaran';
    }

    public function styles(Worksheet $sheet)
    {
        // Styling Excel Global (Rata Tengah & Font Arial)
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