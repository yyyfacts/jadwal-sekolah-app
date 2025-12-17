<table>
    <thead>
        <!-- JUDUL UTAMA -->
        <tr>
            <th colspan="{{ 3 + $kelass->count() + 6 }}" height="50"
                style="font-family: Arial, sans-serif; font-size: 18px; font-weight: bold; text-align: center; vertical-align: middle; border: 2px solid #000000; background-color: #ffffff; color: #000000;">
                JADWAL PELAJARAN SMA NEGERI 1 SAMPANG TAHUN AJARAN 2024/2025
            </th>
        </tr>

        <!-- HEADER TABEL -->
        <tr height="40">
            <th width="20"
                style="border: 1px solid #000000; background-color: #4472c4; color: #ffffff; font-weight: bold; text-align: center; vertical-align: middle;">
                HARI</th>
            <th width="8"
                style="border: 1px solid #000000; background-color: #4472c4; color: #ffffff; font-weight: bold; text-align: center; vertical-align: middle;">
                JAM</th>
            <th width="20"
                style="border: 1px solid #000000; background-color: #4472c4; color: #ffffff; font-weight: bold; text-align: center; vertical-align: middle;">
                WAKTU</th>

            <!-- Header Kelas -->
            @foreach($kelass as $kelas)
            <th width="15"
                style="border: 1px solid #000000; background-color: #d9e1f2; color: #000000; font-weight: bold; text-align: center; vertical-align: middle; word-wrap: break-word;">
                {{ $kelas->nama_kelas }}
            </th>
            @endforeach

            <!-- Gap -->
            <th width="3" style="background-color: #ffffff;"></th>

            <!-- Legenda Mapel -->
            <th width="10"
                style="border: 1px solid #000000; background-color: #fff2cc; color: #000000; font-weight: bold; text-align: center; vertical-align: middle;">
                KODE</th>
            <th width="60"
                style="border: 1px solid #000000; background-color: #fff2cc; color: #000000; font-weight: bold; text-align: center; vertical-align: middle;">
                MATA PELAJARAN</th>

            <!-- Gap -->
            <th width="3" style="background-color: #ffffff;"></th>

            <!-- Legenda Guru -->
            <th width="10"
                style="border: 1px solid #000000; background-color: #e2efda; color: #000000; font-weight: bold; text-align: center; vertical-align: middle;">
                KODE</th>
            <th width="60"
                style="border: 1px solid #000000; background-color: #e2efda; color: #000000; font-weight: bold; text-align: center; vertical-align: middle;">
                NAMA GURU</th>
        </tr>
    </thead>
    <tbody>
        @php
        $hariList = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'];

        $waktu = [
        'Senin' => [0=>'07.00-07.45', 1=>'07.45-08.25', 2=>'08.25-09.05', 3=>'09.05-09.45', 4=>'10.00-10.40',
        5=>'10.40-11.20', 6=>'11.20-12.00', 7=>'12.50-13.30', 8=>'13.30-14.10', 9=>'14.10-14.50', 10=>'14.50-15.30'],
        'Jumat' => [0=>'07.00-07.45', 1=>'07.45-08.20', 2=>'08.20-08.55', 3=>'08.55-09.30', 4=>'09.30-10.00',
        5=>'10.15-10.45', 6=>'10.45-11.15', 7=>'11.15-11.45', 8=>'12.45-13.15', 9=>'13.15-13.45', 10=>'13.45-14.15'],
        'Default' => [0=>'07.00-07.40', 1=>'07.40-08.20', 2=>'08.20-09.00', 3=>'09.00-09.40', 4=>'09.50-10.30',
        5=>'10.30-11.10', 6=>'11.10-11.50', 7=>'12.35-13.15', 8=>'13.15-13.55', 9=>'13.55-14.35', 10=>'14.35-15.15']
        ];

        $mapelCount = isset($mapels) ? $mapels->count() : 0;
        $guruCount = isset($gurus) ? $gurus->count() : 0;
        $idxLegenda = 0;
        @endphp

        @foreach($hariList as $hari)
        @php
        $maxJam = 10;
        $startJam = ($hari == 'Senin' || $hari == 'Jumat') ? 0 : 1;

        // Hitung Rowspan
        $countPelajaran = $maxJam - $startJam + 1;
        $countIstirahat = ($hari != 'Jumat') ? 2 : 0;
        $rowSpan = $countPelajaran + $countIstirahat;
        @endphp

        <!-- BARIS NAMA HARI + LEGENDA -->
        <tr height="30">
            <!-- Kolom Hari (Merged ke bawah) -->
            <td rowspan="{{ $rowSpan + 1 }}"
                style="border: 2px solid #000000; background-color: #203764; color: #ffffff; font-weight: bold; text-align: center; vertical-align: middle; font-size: 14px; text-transform: uppercase; transform: rotate(-90deg);">
                {{ $hari }}
            </td>

            <!-- Filler untuk Jam & Waktu & Kelas (Baris ini khusus Header Hari + Legenda) -->
            <td style="border: 1px solid #000000; background-color: #404040; color: #fff; font-weight:bold; text-align:center;"
                colspan="{{ 2 + $kelass->count() }}">
                HARI : {{ strtoupper($hari) }}
            </td>

            <!-- LEGENDA (KANAN) - Mulai isi dari baris Header Hari agar muat banyak -->
            <td style="background-color: #ffffff;"></td> <!-- GAP -->

            @if($idxLegenda < $mapelCount) <td
                style="border: 1px solid #000000; text-align: center; vertical-align: middle;">
                {{ $mapels[$idxLegenda]->kode_mapel }}</td>
                <td style="border: 1px solid #000000; text-align: left; vertical-align: middle; padding-left: 5px;">
                    {{ $mapels[$idxLegenda]->nama_mapel }}</td>
                @else
                <td style="border: none;"></td>
                <td style="border: none;"></td>
                @endif

                <td style="background-color: #ffffff;"></td> <!-- GAP -->

                @if($idxLegenda < $guruCount) <td
                    style="border: 1px solid #000000; text-align: center; vertical-align: middle;">
                    {{ $gurus[$idxLegenda]->kode_guru }}</td>
                    <td style="border: 1px solid #000000; text-align: left; vertical-align: middle; padding-left: 5px;">
                        {{ $gurus[$idxLegenda]->nama_guru }}</td>
                    @else
                    <td style="border: none;"></td>
                    <td style="border: none;"></td>
                    @endif

                    @php $idxLegenda++; @endphp
        </tr>

        <!-- Loop Jam Pelajaran -->
        @for($jam = $startJam; $jam <= $maxJam; $jam++) <tr height="30">
            <!-- KOLOM HARI (Sudah di rowspan di atas) -->

            <!-- KOLOM JAM -->
            <td style="border: 1px solid #000000; text-align: center; vertical-align: middle; font-weight: bold;">
                {{ $jam }}</td>

            <!-- KOLOM WAKTU -->
            <td style="border: 1px solid #000000; text-align: center; vertical-align: middle; font-size: 10px;">
                @php
                if($hari == 'Senin') $w = $waktu['Senin'][$jam] ?? '-';
                elseif($hari == 'Jumat') $w = $waktu['Jumat'][$jam] ?? '-';
                else $w = $waktu['Default'][$jam] ?? '-';
                @endphp
                {{ $w }}
            </td>

            <!-- GRID JADWAL -->
            @if($jam == 0)
            <td colspan="{{ $kelass->count() }}"
                style="border: 1px solid #000000; text-align: center; background-color: #bfbfbf; font-weight: bold; letter-spacing: 2px; vertical-align: middle;">
                @if($hari == 'Senin') UPACARA BENDERA @elseif($hari == 'Jumat') SENAM / JALAN SEHAT @else LITERASI
                @endif
            </td>
            @else
            @foreach($kelass as $kelas)
            @php
            $data = $jadwals[$kelas->id][$hari][$jam] ?? null;
            @endphp

            @if($data)
            <td
                style="border: 1px solid #000000; text-align: center; vertical-align: middle; font-weight: bold; background-color: #{{ $data['color'] }}; font-size: 10px; word-wrap: break-word;">
                {{ $data['teks'] }}
            </td>
            @else
            <td style="border: 1px solid #000000; background-color: #ffffff;"></td>
            @endif
            @endforeach
            @endif

            <!-- LEGENDA (KANAN) -->
            <td style="background-color: #ffffff;"></td> <!-- GAP -->

            @if($idxLegenda < $mapelCount) <td
                style="border: 1px solid #000000; text-align: center; vertical-align: middle;">
                {{ $mapels[$idxLegenda]->kode_mapel }}</td>
                <td style="border: 1px solid #000000; text-align: left; vertical-align: middle; padding-left: 5px;">
                    {{ $mapels[$idxLegenda]->nama_mapel }}</td>
                @else
                <td style="border: none;"></td>
                <td style="border: none;"></td>
                @endif

                <td style="background-color: #ffffff;"></td> <!-- GAP -->

                @if($idxLegenda < $guruCount) <td
                    style="border: 1px solid #000000; text-align: center; vertical-align: middle;">
                    {{ $gurus[$idxLegenda]->kode_guru }}</td>
                    <td style="border: 1px solid #000000; text-align: left; vertical-align: middle; padding-left: 5px;">
                        {{ $gurus[$idxLegenda]->nama_guru }}</td>
                    @else
                    <td style="border: none;"></td>
                    <td style="border: none;"></td>
                    @endif

                    @php $idxLegenda++; @endphp
                    </tr>

                    <!-- BARIS ISTIRAHAT (KHUSUS SENIN-KAMIS) -->
                    @if(($jam == 4 || $jam == 8) && $hari != 'Jumat')
                    <tr height="25">
                        <!-- HARI (Sudah di-rowspan) -->
                        <td style="border: 1px solid #000000; background-color: #808080;"></td>
                        <td
                            style="border: 1px solid #000000; background-color: #808080; color: #fff; text-align: center; font-weight: bold; vertical-align: middle;">
                            IST</td>
                        <td colspan="{{ $kelass->count() }}"
                            style="border: 1px solid #000000; background-color: #808080; color: #fff; text-align: center; font-weight: bold; letter-spacing: 5px; vertical-align: middle;">
                            ISTIRAHAT
                        </td>

                        <!-- Lanjut Legenda di Baris Istirahat -->
                        <td style="background-color: #ffffff;"></td>
                        @if($idxLegenda < $mapelCount) <td
                            style="border: 1px solid #000; vertical-align: middle; text-align: center;">
                            {{ $mapels[$idxLegenda]->kode_mapel }}</td>
                            <td style="border: 1px solid #000; vertical-align: middle; font-size: 10px;">
                                {{ $mapels[$idxLegenda]->nama_mapel }}</td>
                            @else <td style="border: none;"></td>
                            <td style="border: none;"></td> @endif

                            <td style="background-color: #ffffff;"></td>
                            @if($idxLegenda < $guruCount) <td
                                style="border: 1px solid #000; vertical-align: middle; text-align: center;">
                                {{ $gurus[$idxLegenda]->kode_guru }}</td>
                                <td style="border: 1px solid #000; vertical-align: middle; font-size: 10px;">
                                    {{ $gurus[$idxLegenda]->nama_guru }}</td>
                                @else <td style="border: none;"></td>
                                <td style="border: none;"></td> @endif

                                @php $idxLegenda++; @endphp
                    </tr>
                    @endif

                    @endfor

                    <!-- PEMISAH ANTAR HARI (GAP) -->
                    <!-- Height dibuat 30px agar jika ada legenda yang jatuh di sini tetap terbaca -->
                    <tr height="30">
                        <td colspan="{{ 3 + $kelass->count() }}"
                            style="border-top: 2px solid #000000; background-color: #ffffff;"></td>

                        <!-- Sisa Legenda -->
                        <td style="background-color: #ffffff;"></td>
                        @if($idxLegenda < $mapelCount) <td style="border: 1px solid #000;">
                            {{$mapels[$idxLegenda]->kode_mapel}}</td>
                            <td style="border: 1px solid #000;">{{$mapels[$idxLegenda]->nama_mapel}}</td> @else <td
                                style="border: none;"></td>
                            <td style="border: none;"></td> @endif
                            <td style="background-color: #ffffff;"></td>
                            @if($idxLegenda < $guruCount) <td style="border: 1px solid #000;">
                                {{$gurus[$idxLegenda]->kode_guru}}</td>
                                <td style="border: 1px solid #000;">{{$gurus[$idxLegenda]->nama_guru}}</td> @else <td
                                    style="border: none;"></td>
                                <td style="border: none;"></td> @endif
                                @php $idxLegenda++; @endphp
                    </tr>
                    @endforeach
    </tbody>
</table>