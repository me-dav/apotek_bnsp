<?php

namespace App\Http\Controllers;

use App\Support\Algoritma;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class LaporanController extends Controller
{
    private const BATAS_TERLARIS = 20;

    public function terlaris(Request $request)
    {
        $user = Auth::user();

        $input = $request->validate([
            'dari' => ['nullable', 'date'],
            'sampai' => ['nullable', 'date', 'after_or_equal:dari'],
        ]);

        $dari = Carbon::parse($input['dari'] ?? now()->startOfMonth())->startOfDay();
        $sampai = Carbon::parse($input['sampai'] ?? now())->endOfDay();

        $sql = 'SELECT o.id, o.kode, o.nama,
                       SUM(d.jumlah)   AS terjual,
                       SUM(d.subtotal) AS omzet
                  FROM detail_penjualan d
                  JOIN penjualan p ON p.id = d.penjualan_id
                  JOIN obat o      ON o.id = d.obat_id
                 WHERE p.tanggal BETWEEN ? AND ?';
        $parameter = [$dari->toDateTimeString(), $sampai->toDateTimeString()];

        // Query mentah tidak kena global scope, jadi batasi cabang secara eksplisit
        if (!$user->isAdminPusat()) {
            $sql .= ' AND p.cabang_id = ?';
            $parameter[] = $user->cabang_id;
        }

        $sql .= ' GROUP BY o.id, o.kode, o.nama';

        $terlaris = array_slice(
            Algoritma::urutkanTerlaris(DB::select($sql, $parameter)),
            0,
            self::BATAS_TERLARIS
        );

        return view('laporan.terlaris', [
            'terlaris' => $terlaris,
            'dari' => $dari->toDateString(),
            'sampai' => $sampai->toDateString(),
        ]);
    }
}