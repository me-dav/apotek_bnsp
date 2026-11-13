<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('detail_penjualan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('penjualan_id')->constrained('penjualan')
                ->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('obat_id')->constrained('obat')
                ->cascadeOnUpdate()->restrictOnDelete();
            $table->integer('jumlah');
            $table->decimal('harga_satuan', 12, 2); // harga saat transaksi (snapshot)
            // Subtotal dihitung otomatis oleh database, tidak bisa tidak konsisten
            $table->decimal('subtotal', 14, 2)->storedAs('jumlah * harga_satuan');

            $table->unique(['penjualan_id', 'obat_id']);
            $table->index('obat_id'); // untuk laporan obat terlaris
        });

        DB::statement('ALTER TABLE detail_penjualan ADD CONSTRAINT detail_jumlah_check CHECK (jumlah > 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('detail_penjualan');
    }
};