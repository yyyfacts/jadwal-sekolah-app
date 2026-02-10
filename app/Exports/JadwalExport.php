<?php

namespace App\Exports;

use App\Models\Kelas;
use App\Models\Jadwal;
use App\Models\Guru;
use App\Models\Mapel;
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

        // Ambil Data Jadwal
        $rawJadwals = Jadwal::with(['guru', 'mapel', 'kelas'])
            ->whereNotNull('hari')
            ->whereNotNull('jam')
            ->get();

        // Buat Grid Kosong
        $jadwals = [];
        $hariList = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'];

        // Inisialisasi Array Kosong agar tidak Error "Undefined array key"
        foreach ($kelass as $k) {
            foreach ($hariList as $h) {
                // Inisialisasi dari jam 0 sampai 11 (termasuk upacara & istirahat)
                for ($j = 0; $j <= 11; $j++) {
                    $jadwals[$k->id][$h][$j] = null;
                }
            }
        }

        // Isi Grid dengan Data Database
        foreach ($rawJadwals as $row) {
            $durasi = $row->jumlah_jam;
            for ($i = 0; $i < $durasi; $i++) {
                $jamSekarang = $row->jam + $i;
                
                // Pastikan tidak tembus batas
                if ($jamSekarang <= 11) {
                    // 2. STRUKTUR DATA: Sesuaikan dengan View Blade
                    $jadwals[$row->kelas_id][$row->hari][$jamSekarang] = [
                        'kode_mapel' => $row->mapel->kode_mapel ?? '-',
                        'kode_guru'  => $row->guru->kode_guru ?? '-',
                        // Logika warna: Putih (Single), Biru Muda (Double/Triple)
                        'color'      => $row->tipe_jam == 'double' || $row->tipe_jam == 'triple' ? 'd9e1f2' : 'ffffff'
                    ];
                }
            }
        }

        // 3. RETURN VIEW: Kirim semua variabel yang dibutuhkan
        return view('exports.jadwal_excel', [
            'kelass' => $kelass,
            'jadwals' => $jadwals,
            'gurus' => $gurus,
            'mapels' => $mapels,
            'judulTahun' => $this->judulTahun // <-- PENTING: Kirim judul ke View
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