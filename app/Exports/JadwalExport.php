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

        // Ambil Data Hari dan Waktu Dinamis dari Database
        $dataHari = MasterHari::getActiveDays();
        $hariList = $dataHari->pluck('nama_hari')->toArray();
        $dataWaktu = MasterWaktu::getOrdered();
        $maxJam = $dataWaktu->max('jam_ke'); // Cari jam maksimal yang disetting admin

        // Ambil Data Jadwal
        $rawJadwals = Jadwal::with(['guru', 'mapel', 'kelas'])
            ->whereNotNull('hari')
            ->whereNotNull('jam')
            ->get();

        // Buat Grid Kosong
        $jadwals = [];

        // Inisialisasi Array Kosong berdasarkan Master Waktu (BUKAN hardcode 0-11)
        foreach ($kelass as $k) {
            foreach ($hariList as $h) {
                foreach ($dataWaktu as $waktu) {
                    $jadwals[$k->id][$h][$waktu->jam_ke] = null;
                }
            }
        }

        // Isi Grid dengan Data Database
        foreach ($rawJadwals as $row) {
            $durasi = $row->jumlah_jam;
            for ($i = 0; $i < $durasi; $i++) {
                $jamSekarang = $row->jam + $i;
                
                // Pastikan tidak tembus batas maksimal jam yang diset admin
                if ($jamSekarang <= $maxJam) {
                    // Cek apakah slot ini benar-benar ada di array (mencegah error jika jadwal AI meleset)
                    if (isset($jadwals[$row->kelas_id][$row->hari])) {
                        $jadwals[$row->kelas_id][$row->hari][$jamSekarang] = [
                            'kode_mapel' => $row->mapel->kode_mapel ?? '-',
                            'kode_guru'  => $row->guru->kode_guru ?? '-',
                            // Logika warna: Putih (Single), Biru Muda (Double/Triple)
                            'color'      => $row->tipe_jam == 'double' || $row->tipe_jam == 'triple' ? 'd9e1f2' : 'ffffff'
                        ];
                    }
                }
            }
        }

        // 3. RETURN VIEW: Kirim semua variabel yang dibutuhkan termasuk dataHari & dataWaktu
        return view('exports.jadwal_excel', [
            'kelass' => $kelass,
            'jadwals' => $jadwals,
            'gurus' => $gurus,
            'mapels' => $mapels,
            'judulTahun' => $this->judulTahun,
            'dataHari' => $dataHari,    // <-- KIRIM KE BLADE
            'dataWaktu' => $dataWaktu   // <-- KIRIM KE BLADE
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