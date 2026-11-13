@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <div class="grid gap-4 sm:grid-cols-3">
        <div class="card p-5">
            <p class="text-sm text-neutral-500">Transaksi bulan ini</p>
            <p class="mt-1 text-2xl font-semibold">{{ number_format($jumlahTransaksi, 0, ',', '.') }}</p>
        </div>
        <div class="card p-5">
            <p class="text-sm text-neutral-500">Omzet bulan ini</p>
            <p class="mt-1 text-2xl font-semibold">Rp {{ number_format($omzet, 0, ',', '.') }}</p>
        </div>
        <div class="card p-5">
            <p class="text-sm text-neutral-500">Obat stok kritis</p>
            <p class="mt-1 text-2xl font-semibold">{{ $kritis->count() }}</p>
        </div>
    </div>

    <div class="card mt-6 overflow-x-auto">
        <div class="border-b border-neutral-200 px-5 py-3 font-medium">Peringatan stok kritis (stok &le; stok minimum)</div>
        <table class="tbl">
            <thead>
                <tr>
                    <th>Cabang</th>
                    <th>Kode</th>
                    <th>Obat</th>
                    <th class="text-right">Stok</th>
                    <th class="text-right">Minimum</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($kritis as $k)
                    <tr>
                        <td>{{ $k->nama_cabang }}</td>
                        <td>{{ $k->kode_obat }}</td>
                        <td>{{ $k->nama_obat }}</td>
                        <td class="text-right font-semibold">{{ $k->stok }}</td>
                        <td class="text-right">{{ $k->stok_minimum }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="py-6 text-center text-neutral-500">Tidak ada stok kritis.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection