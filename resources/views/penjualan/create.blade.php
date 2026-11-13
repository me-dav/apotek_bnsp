@extends('layouts.app')

@section('title', 'Penjualan Baru')

@section('content')
    {{-- Tambah item ke keranjang --}}
    <form method="POST" action="{{ route('penjualan.keranjang.tambah') }}" class="card flex flex-wrap items-end gap-3 p-5">
        @csrf
        <div class="min-w-64 flex-1">
            <label class="label" for="obat_id">Obat</label>
            <select id="obat_id" name="obat_id" class="input" required>
                @foreach ($stok as $s)
                    <option value="{{ $s->obat_id }}">
                        {{ $s->obat->nama }} ({{ $s->obat->kategori->nama }}) - stok {{ $s->jumlah }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="w-28">
            <label class="label" for="jumlah">Jumlah</label>
            <input id="jumlah" name="jumlah" type="number" min="1" value="1" class="input" required>
        </div>
        <button class="btn">Tambah</button>
    </form>

    {{-- Keranjang --}}
    <div class="card mt-6 overflow-x-auto">
        <div class="border-b border-neutral-200 px-5 py-3 font-medium">Keranjang</div>
        <table class="tbl">
            <thead>
                <tr>
                    <th>Obat</th>
                    <th class="text-right">Harga</th>
                    <th class="text-right">Jumlah</th>
                    <th class="text-right">Subtotal</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($keranjang as $obatId => $jumlah)
                    @php $o = $obatDiKeranjang[$obatId]; @endphp
                    <tr>
                        <td>
                            {{ $o->nama }}
                            @if ($o->isObatKeras())
                                <span class="badge ml-1">Obat Keras</span>
                            @endif
                        </td>
                        <td class="text-right">Rp {{ number_format($o->harga_jual, 0, ',', '.') }}</td>
                        <td class="text-right">{{ $jumlah }}</td>
                        <td class="text-right">Rp {{ number_format($jumlah * $o->harga_jual, 0, ',', '.') }}</td>
                        <td class="text-right">
                            <form method="POST" action="{{ route('penjualan.keranjang.hapus', $obatId) }}">
                                @csrf
                                @method('DELETE')
                                <button class="underline">Hapus</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="py-6 text-center text-neutral-500">Keranjang kosong.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <form method="POST" action="{{ route('penjualan.store') }}"
            class="flex items-center justify-between border-t border-neutral-200 px-5 py-4">
            @csrf
            <div class="text-sm">
                Total <span class="ml-2 text-lg font-semibold">Rp {{ number_format($total, 0, ',', '.') }}</span>
            </div>
            <button class="btn" @disabled(!$keranjang)>Simpan Transaksi</button>
        </form>
    </div>
@endsection