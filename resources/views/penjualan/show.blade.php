@extends('layouts.app')

@section('title', 'Faktur ' . $penjualan->no_faktur)

@section('content')
    <div class="card mb-6 grid gap-4 p-5 text-sm sm:grid-cols-4">
        <div>
            <div class="text-neutral-500">Tanggal</div>
            <div class="font-medium">{{ $penjualan->tanggal->format('d/m/Y H:i') }}</div>
        </div>
        <div>
            <div class="text-neutral-500">Cabang</div>
            <div class="font-medium">{{ $penjualan->cabang->nama }}</div>
        </div>
        <div>
            <div class="text-neutral-500">Petugas</div>
            <div class="font-medium">{{ $penjualan->pengguna->nama_lengkap }}</div>
        </div>
        <div>
            <div class="text-neutral-500">Total</div>
            <div class="font-semibold">Rp {{ number_format($penjualan->total, 0, ',', '.') }}</div>
        </div>
    </div>

    <div class="card overflow-x-auto">
        <table class="tbl">
            <thead>
                <tr>
                    <th>Obat</th>
                    <th class="text-right">Harga</th>
                    <th class="text-right">Jumlah</th>
                    <th class="text-right">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($penjualan->detail as $d)
                    <tr>
                        <td>{{ $d->obat->nama }}</td>
                        <td class="text-right">Rp {{ number_format($d->harga_satuan, 0, ',', '.') }}</td>
                        <td class="text-right">{{ $d->jumlah }}</td>
                        <td class="text-right">Rp {{ number_format($d->subtotal, 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        <a href="{{ route('penjualan.index') }}" class="btn-outline">Kembali</a>
    </div>
@endsection