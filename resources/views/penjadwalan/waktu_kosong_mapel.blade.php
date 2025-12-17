@extends('layouts.app')

@section('content')
<div class="max-w-5xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Atur Waktu Terlarang Mapel</h1>
            <p class="text-slate-500">Mata Pelajaran: <span
                    class="font-bold text-blue-600">{{ $mapel->nama_mapel }}</span></p>
            <p class="text-xs text-slate-400 mt-1">Centang jam di mana mapel ini <b>TIDAK BOLEH</b> dijadwalkan.</p>
        </div>
        <a href="{{ route('mapel.index') }}"
            class="px-4 py-2 bg-slate-200 text-slate-700 rounded-lg hover:bg-slate-300 font-bold transition">
            &larr; Kembali
        </a>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
        <form action="{{ route('mapel.simpanWaktuKosong', $mapel->id) }}" method="POST">
            @csrf

            <div class="overflow-x-auto">
                <table class="w-full text-sm text-center border-collapse">
                    <thead>
                        <tr>
                            <th class="p-3 border border-slate-200 bg-slate-50 w-24">Hari / Jam</th>
                            @for ($j = 1; $j <= 10; $j++) <th class="p-3 border border-slate-200 bg-slate-50">{{ $j }}
                                </th>
                                @endfor
                        </tr>
                    </thead>
                    <tbody>
                        @foreach (['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'] as $hari)
                        <tr>
                            <td class="p-3 border border-slate-200 font-bold text-slate-700 bg-slate-50">{{ $hari }}
                            </td>
                            @for ($j = 1; $j <= 10; $j++) @php $key=$hari . '-' . $j; $isChecked=in_array($key,
                                $selected); @endphp <td
                                class="border border-slate-200 p-0 relative h-12 hover:bg-red-50 transition cursor-pointer">
                                <label
                                    class="absolute inset-0 flex items-center justify-center cursor-pointer w-full h-full">
                                    {{-- Value checkbox array: libur[Senin][] = 1 --}}
                                    <input type="checkbox" name="libur[{{ $hari }}][]" value="{{ $j }}"
                                        class="w-5 h-5 text-red-600 border-slate-300 rounded focus:ring-red-500 peer"
                                        {{ $isChecked ? 'checked' : '' }}>

                                    <div
                                        class="absolute inset-0 bg-red-100 opacity-0 peer-checked:opacity-50 pointer-events-none transition">
                                    </div>
                                    <span
                                        class="absolute text-[10px] font-bold text-red-600 opacity-0 peer-checked:opacity-100">X</span>
                                </label>
                                </td>
                                @endfor
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <button type="reset"
                    class="px-5 py-2.5 rounded-lg border border-slate-300 text-slate-600 hover:bg-slate-50 font-bold transition">
                    Reset
                </button>
                <button type="submit"
                    class="px-5 py-2.5 rounded-lg bg-blue-600 text-white hover:bg-blue-700 font-bold shadow-lg transition transform active:scale-95">
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>
@endsection