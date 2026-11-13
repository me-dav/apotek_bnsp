<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('log_stok', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cabang_id')->constrained('cabang');
            $table->foreignId('obat_id')->constrained('obat');
            $table->foreignId('penjualan_id')->nullable()->constrained('penjualan')->nullOnDelete();
            $table->integer('perubahan');      // negatif = keluar, positif = masuk
            $table->integer('stok_akhir');
            $table->string('keterangan', 100);
            $table->timestamp('created_at')->useCurrent();

            $table->index(['cabang_id', 'obat_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('log_stok');
    }
};