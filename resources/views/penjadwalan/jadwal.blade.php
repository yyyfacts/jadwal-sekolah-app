@extends('layouts.app')

@section('content')

<div class="w-full max-w-[100vw] mx-auto px-2 sm:px-4 pt-2 flex flex-col">

    <div class="bg-white rounded-xl border shadow-md overflow-auto">

        <table class="w-full border text-center text-sm">

            <thead class="bg-slate-800 text-white">
                <tr>
                    <th>Hari</th>
                    <th>JP</th>
                    <th>Waktu</th>
                    @foreach($kelass as $kelas)
                    <th>{{ $kelas->nama_kelas }}</th>
                    @endforeach
                </tr>
            </thead>

            <tbody>

                @foreach($dataHari as $hariItem)

                @php
                $namaHari = $hariItem->nama_hari;
                $namaHariLower = strtolower($namaHari);
                @endphp

                @foreach($dataWaktu as $waktuItem)

                @php
                $j = $waktuItem->jam_ke;

                // waktu default
                $waktuTampil = \Carbon\Carbon::parse($waktuItem->waktu_mulai)->format('H:i') . ' - ' .
                \Carbon\Carbon::parse($waktuItem->waktu_selesai)->format('H:i');

                $tipeTampil = $waktuItem->tipe;
                $isFixed = $waktuItem->is_fixed;

                // override senin
                if ($namaHariLower == 'senin' && $waktuItem->mulai_senin) {
                $waktuTampil = \Carbon\Carbon::parse($waktuItem->mulai_senin)->format('H:i') . ' - ' .
                \Carbon\Carbon::parse($waktuItem->selesai_senin)->format('H:i');
                $tipeTampil = $waktuItem->tipe_senin;
                }

                // override jumat
                elseif ($namaHariLower == 'jumat' && $waktuItem->mulai_jumat) {
                $waktuTampil = \Carbon\Carbon::parse($waktuItem->mulai_jumat)->format('H:i') . ' - ' .
                \Carbon\Carbon::parse($waktuItem->selesai_jumat)->format('H:i');
                $tipeTampil = $waktuItem->tipe_jumat;
                }
                @endphp

                @if($tipeTampil !== 'Tidak Ada')

                <tr>

                    {{-- HARI --}}
                    <td>{{ $namaHari }}</td>

                    {{-- JP --}}
                    <td>
                        @if($isFixed)
                        -
                        @else
                        {{ $j }}
                        @endif
                    </td>

                    {{-- WAKTU --}}
                    <td>{{ $waktuTampil }}</td>

                    {{-- ================= FIXED ================= --}}
                    @if($isFixed)

                    <td colspan="{{ $kelass->count() }}" class="bg-amber-50">
                        <div class="flex items-center justify-center gap-2">

                            <span class="font-bold uppercase italic text-slate-700">
                                {{ $tipeTampil }}
                            </span>

                            <span class="text-xs bg-red-100 text-red-600 px-2 py-1 rounded font-bold animate-pulse">
                                FIXED
                            </span>

                        </div>
                    </td>

                    {{-- ================= NORMAL ================= --}}
                    @else

                    @foreach($kelass as $kelas)

                    @php
                    $data = $jadwals[$kelas->id][$namaHari][$j] ?? null;
                    @endphp

                    <td class="border">

                        @if($data && is_array($data))

                        <div class="flex flex-col items-center">

                            <span class="font-bold text-sm">
                                {{ $data['mapel'] ?? '-' }}
                            </span>

                            <span class="text-xs text-gray-500">
                                {{ $data['kode_guru'] ?? '-' }}
                            </span>

                        </div>

                        @else

                        <span class="text-gray-300">-</span>

                        @endif

                    </td>

                    @endforeach

                    @endif

                </tr>

                @endif

                @endforeach

                @endforeach

            </tbody>
        </table>

    </div>

</div>

@endsection