<?php

namespace App\Http\Controllers;

use App\Models\Penjualan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $user = Auth::user();

        // Rentang tanggal (bukan fungsi YEAR/MONTH) agar indeks tanggal bisa dipakai
        $bulanIni = fn() => Penjualan::whereBetween('tanggal', [
            now()->startOfMonth(),
            now()->endOfMonth(),
        ]);

        // View stok kritis: non-admin hanya melihat cabangnya sendiri
        $kritis = DB::table('v_stok_kritis')
            ->when(!$user->isAdminPusat(), fn($q) => $q->where('cabang_id', $user->cabang_id))
            ->orderBy('kode_cabang')
            ->orderBy('kode_obat')
            ->get();

        return view('dashboard', [
            'jumlahTransaksi' => $bulanIni()->count(),
            'omzet' => $bulanIni()->sum('total'),
            'kritis' => $kritis,
        ]);
    }
}