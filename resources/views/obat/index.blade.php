@extends('layouts.app')

@section('title', 'Data Obat')

@section('content')
    @php $admin = auth()->user()->isAdminPusat(); @endphp

    <div class="mb-4 flex flex-wrap items-end justify-between gap-3">
        <form method="GET" action="{{ route('obat.index') }}" class="flex gap-2">
            <input name="q" value="{{ $q }}" placeholder="Cari nama atau kode obat" class="input w-72">
            <button class="btn">Cari</button>
            @if ($q !== '')
                <a href="{{ route('obat.index') }}" class="btn-outline">Reset</a>
            @endif
        </form>
        @if ($admin)
            <a href="{{ route('obat.create') }}" class="btn">Tambah Obat</a>
        @endif
    </div>

    <p class="mb-2 text-xs text-neutral-500">{{ $metode }} &middot; {{ $obat->total() }} hasil</p>

    <div class="card overflow-x-auto">
        <table class="tbl">
            <thead>
                <tr>
                    <th>Kode</th>
                    <th>Nama</th>
                    <th>Kategori</th>
                    <th>Satuan</th>
                    <th class="text-right">Harga</th>
                    <th class="text-right">Stok Min.</th>
                    @if ($admin)
                    <th></th>@endif
                </tr>
            </thead>
            <tbody>
                @forelse ($obat as $o)
                    <tr>
                        <td class="font-medium">{{ $o->kode }}</td>
                        <td>{{ $o->nama }}</td>
                        <td>{{ $o->kategori->nama }}</td>
                        <td>{{ $o->satuan }}</td>
                        <td class="text-right">Rp {{ number_format($o->harga_jual, 0, ',', '.') }}</td>
                        <td class="text-right">{{ $o->stok_minimum }}</td>
                        @if ($admin)
                            <td class="flex justify-end gap-3">
                                <a href="{{ route('obat.edit', $o->id) }}" class="underline">Ubah</a>
                                <form method="POST" action="{{ route('obat.destroy', $o->id) }}"
                                    onsubmit="return confirm('Hapus obat ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="underline">Hapus</button>
                                </form>
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="py-6 text-center text-neutral-500">Obat tidak ditemukan.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $obat->links() }}</div>
@endsection