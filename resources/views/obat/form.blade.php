@extends('layouts.app')

@section('title', $obat ? 'Ubah Obat' : 'Tambah Obat')

@section('content')
    <form method="POST" action="{{ $obat ? route('obat.update', $obat->id) : route('obat.store') }}"
        class="card max-w-2xl space-y-4 p-6">
        @csrf
        @if ($obat)
            @method('PUT')
        @endif

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="label" for="kode">Kode</label>
                <input id="kode" name="kode" value="{{ old('kode', $obat?->kode) }}" class="input" required>
            </div>
            <div>
                <label class="label" for="nama">Nama obat</label>
                <input id="nama" name="nama" value="{{ old('nama', $obat?->nama) }}" class="input" required>
            </div>
            <div>
                <label class="label" for="kategori_id">Kategori</label>
                <select id="kategori_id" name="kategori_id" class="input" required>
                    @foreach ($kategori as $k)
                        <option value="{{ $k->id }}" @selected(old('kategori_id', $obat?->kategori_id) == $k->id)>
                            {{ $k->nama }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label" for="satuan">Satuan</label>
                <input id="satuan" name="satuan" value="{{ old('satuan', $obat?->satuan) }}" class="input" required>
            </div>
            <div>
                <label class="label" for="harga_jual">Harga jual (Rp)</label>
                <input id="harga_jual" name="harga_jual" type="number" min="0" step="0.01"
                    value="{{ old('harga_jual', $obat?->harga_jual) }}" class="input" required>
            </div>
            <div>
                <label class="label" for="stok_minimum">Stok minimum</label>
                <input id="stok_minimum" name="stok_minimum" type="number" min="0"
                    value="{{ old('stok_minimum', $obat?->stok_minimum ?? 0) }}" class="input" required>
            </div>
        </div>

        <div class="flex gap-2">
            <button class="btn">Simpan</button>
            <a href="{{ route('obat.index') }}" class="btn-outline">Batal</a>
        </div>
    </form>
@endsection