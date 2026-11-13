<?php

namespace App\Http\Controllers;

use App\Models\Obat;
use App\Models\Penjualan;
use App\Models\Stok;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Support\PesanDb;

class PenjualanController extends Controller
{
    public function index()
    {
        $penjualan = Penjualan::with(['cabang', 'pengguna'])
            ->latest('tanggal')
            ->paginate(15);

        return view('penjualan.index', compact('penjualan'));
    }

    public function create()
    {
        $this->pastikanBisaJual();

        // Keranjang = map [obat_id => jumlah]
        $keranjang = session('keranjang', []);

        $obatDiKeranjang = Obat::with('kategori')
            ->whereIn('id', array_keys($keranjang))
            ->get()
            ->keyBy('id');

        $total = 0;
        foreach ($keranjang as $obatId => $jumlah) {
            $total += $jumlah * $obatDiKeranjang[$obatId]->harga_jual;
        }

        // Daftar pilihan obat: hanya stok cabang pengguna (global scope)
        $stok = Stok::with('obat.kategori')
            ->where('jumlah', '>', 0)
            ->get()
            ->sortBy('obat.nama');

        return view('penjualan.create', compact('keranjang', 'obatDiKeranjang', 'total', 'stok'));
    }

    public function tambah(Request $request): RedirectResponse
    {
        $this->pastikanBisaJual();

        $data = $request->validate([
            'obat_id' => ['required', 'integer', 'exists:obat,id'],
            'jumlah' => ['required', 'integer', 'min:1', 'max:1000'],
        ]);

        $keranjang = session('keranjang', []);
        $keranjang[$data['obat_id']] = ($keranjang[$data['obat_id']] ?? 0) + $data['jumlah'];
        session(['keranjang' => $keranjang]);

        return back();
    }

    public function hapus(int $obat): RedirectResponse
    {
        $keranjang = session('keranjang', []);
        unset($keranjang[$obat]);
        session(['keranjang' => $keranjang]);

        return back();
    }

    public function store(): RedirectResponse
    {
        $this->pastikanBisaJual();

        $user = Auth::user();
        $keranjang = session('keranjang', []);

        if (!$keranjang) {
            return back()->with('error', 'Keranjang masih kosong.');
        }

        // Ubah map keranjang menjadi array item untuk stored procedure
        $items = [];
        foreach ($keranjang as $obatId => $jumlah) {
            $items[] = ['obat_id' => (int) $obatId, 'jumlah' => (int) $jumlah];
        }

        $faktur = 'FKT-' . now()->format('Ymd-His') . '-' . random_int(100, 999);

        try {
            // Satu CALL = satu transaksi di database: semua berhasil atau semua batal
            DB::statement('CALL simpan_penjualan(?, ?, ?, CAST(? AS jsonb))', [
                $faktur,
                $user->cabang_id,
                $user->id,
                json_encode($items),
            ]);
        } catch (QueryException $e) {
            $alasan = PesanDb::dari($e);
            Log::warning('Penjualan ditolak', [
                'faktur' => $faktur,
                'username' => $user->username,
                'alasan' => $alasan,
            ]);

            return back()->with('error', $alasan);
        }

        session()->forget('keranjang');
        Log::info('Penjualan tersimpan', ['faktur' => $faktur, 'username' => $user->username]);

        $penjualan = Penjualan::where('no_faktur', $faktur)->firstOrFail();

        return redirect()
            ->route('penjualan.show', $penjualan->id)
            ->with('sukses', "Transaksi $faktur berhasil disimpan.");
    }

    // Sengaja tanpa route model binding: binding berjalan sebelum middleware
    // pindah koneksi, sehingga akan memakai akun login yang tidak boleh baca penjualan.
    public function show(int $id)
    {
        $penjualan = Penjualan::with(['cabang', 'pengguna', 'detail.obat'])->findOrFail($id);

        // Batch yang terpakai per item (hasil FEFO), dikelompokkan per obat
        $alokasi = DB::table('alokasi_batch as a')
            ->join('batch_stok as b', 'b.id', '=', 'a.batch_id')
            ->join('detail_penjualan as d', 'd.id', '=', 'a.detail_penjualan_id')
            ->where('d.penjualan_id', $penjualan->id)
            ->orderBy('b.tanggal_kedaluwarsa')
            ->select('d.obat_id', 'b.no_batch', 'b.tanggal_kedaluwarsa', 'a.jumlah')
            ->get()
            ->groupBy('obat_id');

        return view('penjualan.show', compact('penjualan', 'alokasi'));
    }

    private function pastikanBisaJual(): void
    {
        // Admin Pusat hanya memantau; transaksi dilakukan di cabang
        abort_if(Auth::user()->isAdminPusat(), 403, 'Admin Pusat tidak melakukan transaksi penjualan.');
    }
}