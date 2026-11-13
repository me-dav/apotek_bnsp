<?php

namespace App\Http\Controllers;

use App\Models\BatchStok;
use App\Models\Cabang;
use App\Models\Obat;
use App\Support\PesanDb;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class BatchController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        // Urut tercepat kedaluwarsa (urutan FEFO)
        $batch = BatchStok::with(['cabang', 'obat'])
            ->where('jumlah', '>', 0)
            ->orderBy('tanggal_kedaluwarsa')
            ->orderBy('id')
            ->paginate(20);

        return view('batch.index', [
            'batch' => $batch,
            'bolehInput' => !$user->isKasir(),
            'obat' => Obat::orderBy('nama')->get(['id', 'kode', 'nama']),
            'cabang' => $user->isAdminPusat() ? Cabang::orderBy('nama')->get(['id', 'nama']) : collect(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = Auth::user();
        abort_if($user->isKasir(), 403, 'Kasir tidak boleh menerima barang masuk.');

        $data = $request->validate([
            'cabang_id' => [Rule::requiredIf($user->isAdminPusat()), 'nullable', 'integer', 'exists:cabang,id'],
            'obat_id' => ['required', 'integer', 'exists:obat,id'],
            'no_batch' => ['required', 'string', 'max:50'],
            'tanggal_kedaluwarsa' => ['required', 'date_format:Y-m-d', 'after:today'],
            'jumlah' => ['required', 'integer', 'min:1', 'max:100000'],
        ]);

        // Apoteker selalu di cabangnya sendiri; hanya Admin Pusat yang memilih cabang
        $cabangId = $user->isAdminPusat() ? $data['cabang_id'] : $user->cabang_id;
        $noBatch = strtoupper(trim($data['no_batch']));

        try {
            DB::statement(
                'CALL terima_batch(CAST(? AS bigint), CAST(? AS bigint), CAST(? AS bigint),
                                   CAST(? AS varchar), CAST(? AS date), CAST(? AS integer))',
                [$user->id, $cabangId, $data['obat_id'], $noBatch, $data['tanggal_kedaluwarsa'], $data['jumlah']]
            );
        } catch (QueryException $e) {
            return back()->withInput()->with('error', PesanDb::dari($e));
        }

        Log::info('Batch diterima', ['username' => $user->username, 'batch' => $noBatch, 'jumlah' => $data['jumlah']]);

        return back()->with('sukses', "Batch $noBatch berhasil dicatat.");
    }
}