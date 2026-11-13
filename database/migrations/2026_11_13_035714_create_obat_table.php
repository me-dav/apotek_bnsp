<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('obat', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 20)->unique();
            $table->string('nama', 150)->index();
            $table->foreignId('kategori_id')->constrained('kategori_obat')
                ->cascadeOnUpdate()->restrictOnDelete();
            $table->string('satuan', 20);
            $table->decimal('harga_jual', 12, 2);
            $table->integer('stok_minimum')->default(0);
            $table->timestamps();
        });

        DB::statement('ALTER TABLE obat ADD CONSTRAINT obat_harga_check CHECK (harga_jual >= 0)');
        DB::statement('ALTER TABLE obat ADD CONSTRAINT obat_stok_min_check CHECK (stok_minimum >= 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('obat');
    }
};