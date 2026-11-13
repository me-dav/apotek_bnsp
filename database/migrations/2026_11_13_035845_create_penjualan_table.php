<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('penjualan', function (Blueprint $table) {
            $table->id();
            $table->string('no_faktur', 30)->unique();
            $table->timestamp('tanggal')->useCurrent();
            $table->foreignId('cabang_id')->constrained('cabang')
                ->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('pengguna_id')->constrained('pengguna')
                ->cascadeOnUpdate()->restrictOnDelete();
            $table->decimal('total', 14, 2)->default(0);

            // Indeks untuk laporan per periode / per cabang
            $table->index('tanggal');
            $table->index(['cabang_id', 'tanggal']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('penjualan');
    }
};