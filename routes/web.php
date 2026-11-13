<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\MonitoringController;
use App\Http\Controllers\ObatController;
use App\Http\Controllers\PenjualanController;
use App\Http\Controllers\StokController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BatchController;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:5,1')   // maks. 5 percobaan per menit
        ->name('login.proses');
});

Route::middleware(['auth', 'koneksi.role'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/', DashboardController::class)->name('dashboard');
    Route::get('/stok', [StokController::class, 'index'])->name('stok.index');

    // Obat
    Route::get('/obat', [ObatController::class, 'index'])->name('obat.index');
    Route::get('/obat/baru', [ObatController::class, 'create'])->name('obat.create');
    Route::post('/obat', [ObatController::class, 'store'])->name('obat.store');
    Route::get('/obat/{id}/ubah', [ObatController::class, 'edit'])->name('obat.edit');
    Route::put('/obat/{id}', [ObatController::class, 'update'])->name('obat.update');
    Route::delete('/obat/{id}', [ObatController::class, 'destroy'])->name('obat.destroy');

    // Laporan
    Route::get('/laporan/obat-terlaris', [LaporanController::class, 'terlaris'])->name('laporan.terlaris');

    // Monitoring (khusus Admin Pusat, dicek di controller)
    Route::get('/monitoring', [MonitoringController::class, 'index'])->name('monitoring.index');
    Route::get('/monitoring/data', [MonitoringController::class, 'data'])->name('monitoring.data');

    // Penjualan
    Route::get('/penjualan', [PenjualanController::class, 'index'])->name('penjualan.index');
    Route::get('/penjualan/baru', [PenjualanController::class, 'create'])->name('penjualan.create');
    Route::post('/penjualan', [PenjualanController::class, 'store'])->name('penjualan.store');
    Route::post('/penjualan/keranjang', [PenjualanController::class, 'tambah'])->name('penjualan.keranjang.tambah');
    Route::delete('/penjualan/keranjang/{obat}', [PenjualanController::class, 'hapus'])->name('penjualan.keranjang.hapus');
    Route::get('/penjualan/{id}', [PenjualanController::class, 'show'])->name('penjualan.show');

    Route::get('/batch', [BatchController::class, 'index'])->name('batch.index');
    Route::post('/batch', [BatchController::class, 'store'])->name('batch.store');
});