@extends('layouts.app')

@section('title', 'Stok Obat')

@section('content')
<div class="card overflow-x-auto">
    <table class="tbl">
        <thead>
            <tr>
                <th>Cabang</th>
                <th>Kode</th>
                <th>Obat</th>
                <th>Kategori</th>
                <th class="text-right">Stok</th>
                <th class="text-right">Minimum</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($stok as $s)
            @php $kritis = $s->jumlah <= $s->obat->stok_minimum; @endphp
                <tr>
                    <td>{{ $s->cabang->nama }}</td>
                    <td>{{ $s->obat->kode }}</td>
                    <td>{{ $s->obat->nama }}</td>
                    <td>{{ $s->obat->kategori->nama }}</td>
                    <td class="text-right font-medium">{{ $s->jumlah }}</td>
                    <td class="text-right">{{ $s->obat->stok_minimum }}</td>
                    <td>
                        @if ($kritis)
                        <span class="badge border-black font-semibold text-black">Kritis</span>
                        @else
                        <span class="text-neutral-400">Aman</span>
                        @endif
                    </td>
                </tr>
                @endforeach
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $stok->links() }}</div>
@endsection