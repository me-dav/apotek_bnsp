<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PenjualanController;
use App\Http\Controllers\StokController;
use Illuminate\Support\Facades\Route;

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

    Route::get('/penjualan', [PenjualanController::class, 'index'])->name('penjualan.index');
    Route::get('/penjualan/baru', [PenjualanController::class, 'create'])->name('penjualan.create');
    Route::post('/penjualan', [PenjualanController::class, 'store'])->name('penjualan.store');
    Route::post('/penjualan/keranjang', [PenjualanController::class, 'tambah'])->name('penjualan.keranjang.tambah');
    Route::delete('/penjualan/keranjang/{obat}', [PenjualanController::class, 'hapus'])->name('penjualan.keranjang.hapus');
    Route::get('/penjualan/{id}', [PenjualanController::class, 'show'])->name('penjualan.show');
});