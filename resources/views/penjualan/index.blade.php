@extends('layouts.app')

@section('title', 'Penjualan')

@section('content')
    @unless (auth()->user()->isAdminPusat())
        <div class="mb-4 flex justify-end">
            <a href="{{ route('penjualan.create') }}" class="btn">Penjualan Baru</a>
        </div>
    @endunless

    <div class="card overflow-x-auto">
        <table class="tbl">
            <thead>
                <tr>
                    <th>No. Faktur</th>
                    <th>Tanggal</th>
                    <th>Cabang</th>
                    <th>Petugas</th>
                    <th class="text-right">Total</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($penjualan as $p)
                    <tr>
                        <td class="font-medium">{{ $p->no_faktur }}</td>
                        <td>{{ $p->tanggal->format('d/m/Y H:i') }}</td>
                        <td>{{ $p->cabang->nama }}</td>
                        <td>{{ $p->pengguna->nama_lengkap }}</td>
                        <td class="text-right">Rp {{ number_format($p->total, 0, ',', '.') }}</td>
                        <td class="text-right"><a href="{{ route('penjualan.show', $p->id) }}" class="underline">Detail</a></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="py-6 text-center text-neutral-500">Belum ada transaksi.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $penjualan->links() }}</div>
@endsection