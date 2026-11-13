<?php

namespace App\Http\Controllers;

use App\Models\Stok;

class StokController extends Controller
{
    public function index()
    {
        // Global scope otomatis membatasi cabang untuk Kasir/Apoteker
        $stok = Stok::with(['cabang', 'obat.kategori'])
            ->orderBy('cabang_id')
            ->orderBy('obat_id')
            ->paginate(20);

        return view('stok.index', compact('stok'));
    }
}