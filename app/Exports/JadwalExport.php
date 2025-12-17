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
use PhpOffice\PhpSpreadsheet\Style\Border;

class JadwalExport implements FromView, ShouldAutoSize, WithTitle, WithStyles
{
    public function view(): View
    {
        $kelass = Kelas::orderBy('nama_kelas')->get();
        $gurus = Guru::orderBy('kode_guru')->get();
        $mapels = Mapel::orderBy('kode_mapel')->get();

        // Ambil Data Jadwal
        $rawJadwals = Jadwal::with(['guru', 'mapel', 'kelas'])
            ->whereNotNull('hari')->whereNotNull('jam')->get();

        // Buat Grid Kosong (0 s/d 10 agar lengkap)
        $jadwals = [];
        $hariList = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'];

        foreach ($kelass as $k) {
            foreach ($hariList as $h) {
                // [FIX] Inisialisasi dari jam 0 sampai 10
                for ($j = 0; $j <= 10; $j++) {
                    $jadwals[$k->id][$h][$j] = null;
                }
            }
        }

        // Isi Grid
        foreach ($rawJadwals as $row) {
            $durasi = $row->jumlah_jam;
            for ($i = 0; $i < $durasi; $i++) {
                $jamSekarang = $row->jam + $i;
                // [FIX] Pastikan data sampai jam 10 masuk
                if ($jamSekarang <= 10) {
                    $k_mapel = $row->mapel->kode_mapel ?? '?';
                    $k_guru = $row->guru->kode_guru ?? '?';

                    $jadwals[$row->kelas_id][$row->hari][$jamSekarang] = [
                        'teks' => "{$k_mapel}-{$k_guru}",
                        'color' => $row->tipe_jam === 'single' ? 'ffffff' : ($row->tipe_jam === 'double' ? 'e6f7ff' : 'f9f0ff')
                    ];
                }
            }
        }

        return view('exports.jadwal_excel', compact('kelass', 'jadwals', 'gurus', 'mapels'));
    }

    public function title(): string
    {
        return 'Jadwal Pelajaran';
    }

    public function styles(Worksheet $sheet)
    {
        // Styling Excel agar rapi
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