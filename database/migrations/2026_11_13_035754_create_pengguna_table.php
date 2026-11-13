<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pengguna', function (Blueprint $table) {
            $table->id();
            $table->string('username', 50)->unique();
            $table->string('password');
            $table->string('nama_lengkap', 100);
            $table->string('role', 20);
            $table->foreignId('cabang_id')->nullable()->constrained('cabang')
                ->cascadeOnUpdate()->restrictOnDelete();
            $table->rememberToken();
            $table->timestamps();
        });

        DB::statement("ALTER TABLE pengguna ADD CONSTRAINT pengguna_role_check
            CHECK (role IN ('admin_pusat', 'apoteker', 'kasir'))");
        // Selain Admin Pusat, pengguna wajib punya cabang
        DB::statement("ALTER TABLE pengguna ADD CONSTRAINT pengguna_cabang_check
            CHECK (role = 'admin_pusat' OR cabang_id IS NOT NULL)");
    }

    public function down(): void
    {
        Schema::dropIfExists('pengguna');
    }
};