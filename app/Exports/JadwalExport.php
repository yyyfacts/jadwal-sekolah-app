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
        // Wali Kelas sudah ke-load di sini jadi aman dipakai di Blade
        $kelass = Kelas::with('waliKelas')->orderBy('nama_kelas')->get();
        $gurus = Guru::orderBy('kode_guru')->get();
        $mapels = Mapel::orderBy('kode_mapel')->get();

        // 1. Tarik HARI tanpa filter SQL (biar Istirahat yang NULL nggak ikut hilang)
        $dataHari = MasterHari::with(['waktuHaris' => function($q) {
            $q->orderBy('waktu_mulai');
        }])->where('is_active', true)->get();
        
        // --- FIX FINAL: Buang jam 'Tidak Ada' murni pakai PHP Collection ---
        foreach ($dataHari as $hariObj) {
            $filteredWaktu = $hariObj->waktuHaris->filter(function($w) {
                return $w->tipe !== 'Tidak Ada'; // Kalau tipe NULL/Istirahat, tetap aman masuk
            })->values(); // Reset urutan array
            
            $hariObj->setRelation('waktuHaris', $filteredWaktu);
        }

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

        // 2. BUAT CANVAS KOSONG
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

        // 3. MAPPING SLOT BELAJAR 
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

        // 4. MAPPING ID HARI KE NAMA HARI
        $hariMap = MasterHari::pluck('nama_hari', 'id')->toArray();

        // 5. MASUKKAN JADWAL HASIL GENERATE
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
                        
                        // Masukin datanya ke array Excel (Warna sekarang tidak diperlukan lagi di Blade)
                        $jadwals[$row->kelas_id][$hari][$jamSekarang] = [
                            'kode_mapel' => $row->mapel->kode_mapel ?? '-',
                            'kode_guru'  => $row->guru->kode_guru ?? '-'
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