<?php

namespace App\Http\Controllers;

use App\Models\KategoriObat;
use App\Models\Obat;
use App\Support\Algoritma;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ObatController extends Controller
{
    private const PER_HALAMAN = 20;

    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        // COLLATE "C" = urutan byte, sama dengan strcmp() di pencarian biner.
        // Tanpa ini urutan collation database bisa berbeda dan pencarian biner meleset.
        $semua = Obat::with('kategori')->orderByRaw('kode COLLATE "C"')->get()->all();

        if ($q === '') {
            $hasil = $semua;
            $metode = 'Semua data';
        } elseif ($persis = Algoritma::cariBinerKode($semua, strtoupper($q))) {
            $hasil = [$persis];
            $metode = 'Pencarian biner (kode persis), O(log n)';
        } else {
            $hasil = Algoritma::cariObat($semua, $q);
            $metode = 'Pencarian linear (nama/kode), O(n·m)';
        }

        $halaman = LengthAwarePaginator::resolveCurrentPage();
        $obat = new LengthAwarePaginator(
            array_slice($hasil, ($halaman - 1) * self::PER_HALAMAN, self::PER_HALAMAN),
            count($hasil),
            self::PER_HALAMAN,
            $halaman,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('obat.index', compact('obat', 'q', 'metode'));
    }

    public function create()
    {
        $this->pastikanAdmin();

        return view('obat.form', ['obat' => null, 'kategori' => KategoriObat::orderBy('nama')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->pastikanAdmin();
        $data = $request->validate($this->aturan());

        DB::transaction(function () use ($data) {
            $obat = Obat::create($data);

            // Siapkan baris stok 0 untuk setiap cabang (query berparameter)
            DB::insert(
                'INSERT INTO stok (cabang_id, obat_id, jumlah, created_at, updated_at)
                 SELECT id, ?, 0, now(), now() FROM cabang',
                [$obat->id]
            );
        });

        return redirect()->route('obat.index')->with('sukses', 'Obat berhasil ditambahkan.');
    }

    public function edit(int $id)
    {
        $this->pastikanAdmin();

        return view('obat.form', [
            'obat' => Obat::findOrFail($id),
            'kategori' => KategoriObat::orderBy('nama')->get(),
        ]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $this->pastikanAdmin();
        $obat = Obat::findOrFail($id);
        $obat->update($request->validate($this->aturan($id)));

        return redirect()->route('obat.index')->with('sukses', 'Obat berhasil diperbarui.');
    }

    public function destroy(int $id): RedirectResponse
    {
        $this->pastikanAdmin();
        $obat = Obat::findOrFail($id);

        try {
            DB::transaction(function () use ($obat) {
                // Hanya baris stok kosong yang dibersihkan.
                // Obat yang sudah punya stok/transaksi akan ditolak oleh foreign key.
                DB::delete('DELETE FROM stok WHERE obat_id = ? AND jumlah = 0', [$obat->id]);
                $obat->delete();
            });
        } catch (QueryException $e) {
            if ($e->getCode() !== '23503') { // 23503 = pelanggaran foreign key
                throw $e;
            }

            return back()->with('error', 'Obat tidak dapat dihapus karena sudah memiliki stok atau riwayat transaksi.');
        }

        return redirect()->route('obat.index')->with('sukses', 'Obat berhasil dihapus.');
    }

    private function aturan(?int $abaikanId = null): array
    {
        return [
            'kode' => ['required', 'string', 'max:20', Rule::unique('obat', 'kode')->ignore($abaikanId)],
            'nama' => ['required', 'string', 'max:150'],
            'kategori_id' => ['required', 'integer', 'exists:kategori_obat,id'],
            'satuan' => ['required', 'string', 'max:20'],
            'harga_jual' => ['required', 'numeric', 'min:0'],
            'stok_minimum' => ['required', 'integer', 'min:0'],
        ];
    }

    private function pastikanAdmin(): void
    {
        abort_unless(Auth::user()->isAdminPusat(), 403, 'Hanya Admin Pusat yang boleh mengubah data obat.');
    }
}