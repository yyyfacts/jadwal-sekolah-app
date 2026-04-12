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

        $rawJadwals = Jadwal::with(['guru', 'mapel', 'kelas'])->whereNotNull('hari')->whereNotNull('jam')->get();
        $jadwals = [];

        // 1. Siapkan Canvas Excel Kosong sesuai batas Master Waktu (misal 1 sampai 11)
        foreach ($kelass as $k) {
            foreach ($hariList as $h) {
                foreach ($dataWaktu as $waktu) {
                    $jadwals[$k->id][$h][$waktu->jam_ke] = null;
                }
            }
        }

        // 2. Petakan Jam Belajar Saja (Abaikan Istirahat)
        $belajarSlots = [];
        foreach ($dataHari as $hariObj) {
            $namaHari = $hariObj->nama_hari;
            $namaHariLower = strtolower($namaHari);
            $belajarSlots[$namaHari] = [];
            
            // Kita bikin mapping dari "Jam Belajar Ke-Berapa" ke "Jam Fisik Ke-Berapa"
            // Contoh: Belajar ke-5 -> Jatuhnya di Jam Fisik ke-6 (karena ke-5 buat Istirahat)
            $urutanBelajar = 1; 

            foreach ($dataWaktu as $waktuObj) {
                $tipeSlot = $waktuObj->tipe;
                if ($namaHariLower == 'senin' && $waktuObj->tipe_senin) $tipeSlot = $waktuObj->tipe_senin;
                if ($namaHariLower == 'jumat' && $waktuObj->tipe_jumat) $tipeSlot = $waktuObj->tipe_jumat;

                if (!in_array($tipeSlot, ['Istirahat', 'Upacara', 'Senam', 'Sholat Dhuha', 'Jumat Bersih', 'Pramuka']) && $tipeSlot !== 'Tidak Ada') {
                    // Simpan mapping: [urutan_belajar] = jam_ke_fisik
                    $belajarSlots[$namaHari][$urutanBelajar] = $waktuObj->jam_ke;
                    $urutanBelajar++;
                }
            }
        }

        // 3. Masukkan Jadwal dari DB ke Canvas Excel
        foreach ($rawJadwals as $row) {
            $durasi = $row->jumlah_jam;
            $hari = $row->hari;
            // Ingat: Karena kita udah update logic generate(), $row->jam SEKARANG ADALAH JAM FISIK!
            $jamMulaiFisik = $row->jam; 
            
            // Kita cari "Jam Fisik" ini posisinya di "Urutan Belajar" ke berapa
            $slotsTersedia = $belajarSlots[$hari] ?? [];
            $urutanMulai = array_search($jamMulaiFisik, $slotsTersedia);

            if ($urutanMulai !== false) {
                // Loop sebanyak durasi SKS (misal 2 jam)
                for ($i = 0; $i < $durasi; $i++) {
                    // Cari jam fisik untuk urutan belajar selanjutnya
                    $urutanSekarang = $urutanMulai + $i;
                    
                    if (isset($slotsTersedia[$urutanSekarang])) {
                        $jamFisikSekarang = $slotsTersedia[$urutanSekarang];
                        
                        if ($jamFisikSekarang <= $maxJam && isset($jadwals[$row->kelas_id][$hari])) {
                            $jadwals[$row->kelas_id][$hari][$jamFisikSekarang] = [
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