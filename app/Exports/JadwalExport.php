<?php

namespace App\Exports;

use App\Models\Kelas;
use App\Models\Jadwal;
use App\Models\Guru;
use App\Models\Mapel;
use App\Models\MasterHari;
use App\Models\WaktuHari; 
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
        $kelass = Kelas::with('waliKelas')->orderBy('nama_kelas')->get();
        $gurus = Guru::orderBy('kode_guru')->get();
        $mapels = Mapel::orderBy('kode_mapel')->get();

        // --- 👇 FIX UTAMA: Filter 'Tidak Ada' biar baris 9 & 10 yang kosong gak ikut ke-Export 👇 ---
        $dataHari = MasterHari::with(['waktuHaris' => function($q) {
            $q->where('tipe', '!=', 'Tidak Ada')->orderBy('waktu_mulai');
        }])->where('is_active', true)->get();
        
        $hariList = $dataHari->pluck('nama_hari')->toArray();
        $dataWaktu = WaktuHari::select('jam_ke')->distinct()->orderBy('jam_ke')->get();

        // Query persis sama kayak di index() Web Controller
        $rawJadwals = Jadwal::with(['guru', 'mapel', 'kelas'])
            ->whereNotNull('hari_id')
            ->whereNotNull('jam')
            ->where(function($q) {
                $q->where('status', 'offline')->orWhereNull('status');
            })
            ->get();

        $jadwals = [];

        // 1. BUAT CANVAS KOSONG
        foreach ($kelass as $k) {
            foreach ($dataHari as $hariObj) {
                $namaHari = $hariObj->nama_hari;
                foreach ($hariObj->waktuHaris as $waktu) {
                    if ($waktu->jam_ke !== null) {
                        $jadwals[$k->id][$namaHari][$waktu->jam_ke] = null;
                    }
                }
            }
        }

        // 2. MAPPING SLOT BELAJAR 
        $belajarSlots = [];
        foreach ($dataHari as $hariObj) {
            $namaHari = $hariObj->nama_hari;
            $belajarSlots[$namaHari] = [];
            
            foreach ($hariObj->waktuHaris as $waktuObj) {
                $tipeSlot = $waktuObj->tipe;

                if (!in_array($tipeSlot, ['Istirahat', 'Upacara', 'Senam', 'Sholat', 'Sholat Dhuha', 'Jumat Bersih', 'Pramuka']) && $tipeSlot !== 'Tidak Ada') {
                    if ($waktuObj->jam_ke !== null) {
                        $belajarSlots[$namaHari][] = $waktuObj->jam_ke;
                    }
                }
            }
        }

        // 3. MAPPING ID HARI KE NAMA HARI
        $hariMap = MasterHari::pluck('nama_hari', 'id')->toArray();

        // 4. MASUKKAN JADWAL HASIL GENERATE
        foreach ($rawJadwals as $row) {
            $durasi = $row->jumlah_jam;
            
            $hari_id_angka = $row->hari_id;
            $hari = $hariMap[$hari_id_angka] ?? null; 

            if (!$hari) continue;

            $jamMulaiFisik = $row->jam; 
            
            $slotsTersedia = $belajarSlots[$hari] ?? [];
            $startIndex = array_search($jamMulaiFisik, $slotsTersedia); 
            
            if ($startIndex !== false) {
                for ($i = 0; $i < $durasi; $i++) {
                    if (isset($slotsTersedia[$startIndex + $i])) {
                        $jamSekarang = $slotsTersedia[$startIndex + $i]; 
                        
                        if (!isset($jadwals[$row->kelas_id])) continue;
                        
                        // Masukin datanya ke array Excel
                        $jadwals[$row->kelas_id][$hari][$jamSekarang] = [
                            'kode_mapel' => $row->mapel->kode_mapel ?? '-',
                            'kode_guru'  => $row->guru->kode_guru ?? '-',
                            'color'      => $row->tipe_jam == 'double' || $row->tipe_jam == 'triple' ? 'd9e1f2' : 'ffffff'
                        ];
                    }
                }
            }
        }

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