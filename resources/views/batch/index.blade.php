@extends('layouts.app')

@section('title', 'Batch & Kedaluwarsa')

@section('content')
    @if ($bolehInput)
        <form method="POST" action="{{ route('batch.store') }}" class="card mb-6 flex flex-wrap items-end gap-3 p-5">
            @csrf
            @if ($cabang->isNotEmpty())
                <div class="w-52">
                    <label class="label" for="cabang_id">Cabang</label>
                    <select id="cabang_id" name="cabang_id" class="input" required>
                        @foreach ($cabang as $c)
                            <option value="{{ $c->id }}" @selected(old('cabang_id') == $c->id)>{{ $c->nama }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            <div class="min-w-56 flex-1">
                <label class="label" for="obat_id">Obat</label>
                <select id="obat_id" name="obat_id" class="input" required>
                    @foreach ($obat as $o)
                        <option value="{{ $o->id }}" @selected(old('obat_id') == $o->id)>{{ $o->kode }} - {{ $o->nama }}</option>
                    @endforeach
                </select>
            </div>
            <div class="w-36">
                <label class="label" for="no_batch">No. batch</label>
                <input id="no_batch" name="no_batch" value="{{ old('no_batch') }}" class="input" required>
            </div>
            <div class="w-40">
                <label class="label" for="tanggal_kedaluwarsa">Kedaluwarsa</label>
                <input id="tanggal_kedaluwarsa" name="tanggal_kedaluwarsa" type="date" value="{{ old('tanggal_kedaluwarsa') }}"
                    class="input" required>
            </div>
            <div class="w-24">
                <label class="label" for="jumlah">Jumlah</label>
                <input id="jumlah" name="jumlah" type="number" min="1" value="{{ old('jumlah', 1) }}" class="input" required>
            </div>
            <button class="btn">Catat Barang Masuk</button>
        </form>
    @endif

    <p class="mb-2 text-xs text-neutral-500">Diurutkan dari yang paling cepat kedaluwarsa (urutan penjualan FEFO).</p>
    <div class="card overflow-x-auto">
        <table class="tbl">
            <thead>
                <tr>
                    <th>Cabang</th>
                    <th>Obat</th>
                    <th>No. Batch</th>
                    <th>Kedaluwarsa</th>
                    <th class="text-right">Jumlah</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($batch as $b)
                    @php $status = $b->status(); @endphp
                    <tr>
                        <td>{{ $b->cabang->nama }}</td>
                        <td>{{ $b->obat->nama }}</td>
                        <td>{{ $b->no_batch }}</td>
                        <td>{{ $b->tanggal_kedaluwarsa->format('d/m/Y') }}</td>
                        <td class="text-right">{{ $b->jumlah }}</td>
                        <td>
                            @if ($status === 'kedaluwarsa')
                                <span class="badge border-red-600 bg-red-50 text-red-700">Kedaluwarsa</span>
                            @elseif ($status === 'segera')
                                <span class="badge border-amber-500 bg-amber-50 text-amber-700">&le; 90 hari</span>
                            @else
                                <span class="text-neutral-400">Aman</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="py-6 text-center text-neutral-500">Belum ada data batch.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $batch->links() }}</div>
@endsection