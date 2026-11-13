@extends('layouts.app')

@section('title', 'Obat Terlaris')

@section('content')
    <form method="GET" action="{{ route('laporan.terlaris') }}" class="mb-4 flex flex-wrap items-end gap-3">
        <div>
            <label class="label" for="dari">Dari</label>
            <input id="dari" name="dari" type="date" value="{{ $dari }}" class="input">
        </div>
        <div>
            <label class="label" for="sampai">Sampai</label>
            <input id="sampai" name="sampai" type="date" value="{{ $sampai }}" class="input">
        </div>
        <button class="btn">Tampilkan</button>
    </form>

    <div class="card overflow-x-auto">
        <table class="tbl">
            <thead>
                <tr>
                    <th class="w-12">#</th>
                    <th>Kode</th>
                    <th>Obat</th>
                    <th class="text-right">Terjual</th>
                    <th class="text-right">Omzet</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($terlaris as $i => $o)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td>{{ $o->kode }}</td>
                        <td class="font-medium">{{ $o->nama }}</td>
                        <td class="text-right">{{ number_format($o->terjual, 0, ',', '.') }}</td>
                        <td class="text-right">Rp {{ number_format($o->omzet, 0, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="py-6 text-center text-neutral-500">Tidak ada penjualan pada periode ini.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <p class="mt-2 text-xs text-neutral-500">Diurutkan dengan merge sort, O(n log n). Maksimal 20 obat.</p>
@endsection