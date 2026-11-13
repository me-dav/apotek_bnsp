<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('stok', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cabang_id')->constrained('cabang')
                ->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('obat_id')->constrained('obat')
                ->cascadeOnUpdate()->restrictOnDelete();
            $table->integer('jumlah')->default(0);
            $table->timestamps();

            $table->unique(['cabang_id', 'obat_id']); // satu baris stok per obat per cabang
            $table->index('obat_id');
        });

        // Stok tidak boleh minus (pengaman terakhir di level database)
        DB::statement('ALTER TABLE stok ADD CONSTRAINT stok_jumlah_check CHECK (jumlah >= 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('stok');
    }
};