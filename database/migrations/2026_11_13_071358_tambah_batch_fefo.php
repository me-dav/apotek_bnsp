<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('batch_stok', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cabang_id')->constrained('cabang')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('obat_id')->constrained('obat')->cascadeOnUpdate()->restrictOnDelete();
            $table->string('no_batch', 50);
            $table->date('tanggal_kedaluwarsa');
            $table->integer('jumlah')->default(0);
            $table->timestamps();

            $table->unique(['cabang_id', 'obat_id', 'no_batch']);
            // Indeks FEFO: cari batch tercepat kedaluwarsa per cabang + obat
            $table->index(['cabang_id', 'obat_id', 'tanggal_kedaluwarsa']);
        });
        DB::statement('ALTER TABLE batch_stok ADD CONSTRAINT batch_jumlah_check CHECK (jumlah >= 0)');

        Schema::create('alokasi_batch', function (Blueprint $table) {
            $table->id();
            $table->foreignId('detail_penjualan_id')->constrained('detail_penjualan')->cascadeOnDelete();
            $table->foreignId('batch_id')->constrained('batch_stok')->restrictOnDelete();
            $table->integer('jumlah');
            $table->index('detail_penjualan_id');
        });

        // Stok lama belum punya data kedaluwarsa. Dimasukkan ke satu batch awal
        // dengan tanggal placeholder jauh ke depan, sehingga terjual paling akhir.
        // Ganti dengan data asli setelah stok opname.
        DB::statement("
            INSERT INTO batch_stok (cabang_id, obat_id, no_batch, tanggal_kedaluwarsa, jumlah, created_at, updated_at)
            SELECT cabang_id, obat_id, 'BATCH-AWAL', DATE '2099-12-31', jumlah, now(), now()
              FROM stok
             WHERE jumlah > 0
        ");

        // Trigger pengurang stok versi FEFO
        DB::unprepared(<<<'SQL'
        CREATE OR REPLACE FUNCTION trg_kurangi_stok()
        RETURNS trigger
        LANGUAGE plpgsql
        SECURITY DEFINER
        SET search_path = public
        AS $$
        DECLARE
            v_cabang_id  bigint;
            v_stok_akhir integer;
            v_sisa       integer := NEW.jumlah;
            v_ambil      integer;
            v_batch      record;
        BEGIN
            SELECT cabang_id INTO v_cabang_id
              FROM penjualan WHERE id = NEW.penjualan_id;

            -- FEFO: batch tercepat kedaluwarsa dulu, batch kedaluwarsa dilewati.
            -- FOR UPDATE mengunci baris agar aman dari transaksi bersamaan.
            FOR v_batch IN
                SELECT id, jumlah
                  FROM batch_stok
                 WHERE cabang_id = v_cabang_id
                   AND obat_id = NEW.obat_id
                   AND jumlah > 0
                   AND tanggal_kedaluwarsa >= CURRENT_DATE
                 ORDER BY tanggal_kedaluwarsa, id
                   FOR UPDATE
            LOOP
                EXIT WHEN v_sisa = 0;
                v_ambil := LEAST(v_batch.jumlah, v_sisa);

                UPDATE batch_stok SET jumlah = jumlah - v_ambil, updated_at = now()
                 WHERE id = v_batch.id;

                INSERT INTO alokasi_batch (detail_penjualan_id, batch_id, jumlah)
                VALUES (NEW.id, v_batch.id, v_ambil);

                v_sisa := v_sisa - v_ambil;
            END LOOP;

            IF v_sisa > 0 THEN
                RAISE EXCEPTION 'Stok tidak cukup (batch belum kedaluwarsa) untuk obat_id % di cabang_id %',
                    NEW.obat_id, v_cabang_id;
            END IF;

            UPDATE stok
               SET jumlah = jumlah - NEW.jumlah, updated_at = now()
             WHERE cabang_id = v_cabang_id AND obat_id = NEW.obat_id
            RETURNING jumlah INTO v_stok_akhir;

            INSERT INTO log_stok (cabang_id, obat_id, penjualan_id, perubahan, stok_akhir, keterangan)
            VALUES (v_cabang_id, NEW.obat_id, NEW.penjualan_id, -NEW.jumlah, v_stok_akhir, 'Penjualan');

            RETURN NEW;
        END;
        $$;
        SQL);

        // Barang masuk per batch
        DB::unprepared(<<<'SQL'
        CREATE OR REPLACE PROCEDURE terima_batch(
            p_pengguna_id bigint,
            p_cabang_id   bigint,
            p_obat_id     bigint,
            p_no_batch    varchar,
            p_kedaluwarsa date,
            p_jumlah      integer
        )
        LANGUAGE plpgsql
        SECURITY DEFINER
        SET search_path = public
        AS $$
        DECLARE
            v_role        varchar;
            v_cabang_user bigint;
            v_stok_akhir  integer;
        BEGIN
            SELECT role, cabang_id INTO v_role, v_cabang_user
              FROM pengguna WHERE id = p_pengguna_id;
            IF NOT FOUND THEN
                RAISE EXCEPTION 'Pengguna tidak ditemukan';
            END IF;

            IF v_role = 'kasir' THEN
                RAISE EXCEPTION 'Kasir tidak boleh menerima barang masuk';
            END IF;
            IF v_role = 'apoteker' AND v_cabang_user <> p_cabang_id THEN
                RAISE EXCEPTION 'Pengguna tidak boleh mengubah stok cabang lain';
            END IF;
            IF p_jumlah <= 0 THEN
                RAISE EXCEPTION 'Jumlah harus lebih dari 0';
            END IF;
            IF p_kedaluwarsa <= CURRENT_DATE THEN
                RAISE EXCEPTION 'Tanggal kedaluwarsa harus setelah hari ini';
            END IF;
            IF EXISTS (SELECT 1 FROM batch_stok
                        WHERE cabang_id = p_cabang_id AND obat_id = p_obat_id
                          AND no_batch = p_no_batch AND tanggal_kedaluwarsa <> p_kedaluwarsa) THEN
                RAISE EXCEPTION 'Nomor batch % sudah ada dengan tanggal kedaluwarsa berbeda', p_no_batch;
            END IF;

            INSERT INTO batch_stok (cabang_id, obat_id, no_batch, tanggal_kedaluwarsa, jumlah, created_at, updated_at)
            VALUES (p_cabang_id, p_obat_id, p_no_batch, p_kedaluwarsa, p_jumlah, now(), now())
            ON CONFLICT (cabang_id, obat_id, no_batch)
            DO UPDATE SET jumlah = batch_stok.jumlah + EXCLUDED.jumlah, updated_at = now();

            INSERT INTO stok (cabang_id, obat_id, jumlah, created_at, updated_at)
            VALUES (p_cabang_id, p_obat_id, p_jumlah, now(), now())
            ON CONFLICT (cabang_id, obat_id)
            DO UPDATE SET jumlah = stok.jumlah + EXCLUDED.jumlah, updated_at = now()
            RETURNING jumlah INTO v_stok_akhir;

            INSERT INTO log_stok (cabang_id, obat_id, penjualan_id, perubahan, stok_akhir, keterangan)
            VALUES (p_cabang_id, p_obat_id, NULL, p_jumlah, v_stok_akhir, 'Barang masuk batch ' || p_no_batch);
        END;
        $$;
        SQL);
    }

    public function down(): void
    {
        DB::unprepared('DROP PROCEDURE IF EXISTS terima_batch(bigint, bigint, bigint, varchar, date, integer)');

        // Kembalikan trigger ke versi sebelum FEFO (stok.jumlah tetap sumber kebenaran)
        DB::unprepared(<<<'SQL'
        CREATE OR REPLACE FUNCTION trg_kurangi_stok()
        RETURNS trigger
        LANGUAGE plpgsql
        SECURITY DEFINER
        SET search_path = public
        AS $$
        DECLARE
            v_cabang_id  bigint;
            v_stok_akhir integer;
        BEGIN
            SELECT cabang_id INTO v_cabang_id
              FROM penjualan WHERE id = NEW.penjualan_id;

            UPDATE stok
               SET jumlah = jumlah - NEW.jumlah, updated_at = now()
             WHERE cabang_id = v_cabang_id
               AND obat_id   = NEW.obat_id
               AND jumlah   >= NEW.jumlah
            RETURNING jumlah INTO v_stok_akhir;

            IF NOT FOUND THEN
                RAISE EXCEPTION 'Stok tidak cukup untuk obat_id % di cabang_id %',
                    NEW.obat_id, v_cabang_id;
            END IF;

            INSERT INTO log_stok (cabang_id, obat_id, penjualan_id, perubahan, stok_akhir, keterangan)
            VALUES (v_cabang_id, NEW.obat_id, NEW.penjualan_id, -NEW.jumlah, v_stok_akhir, 'Penjualan');

            RETURN NEW;
        END;
        $$;
        SQL);

        Schema::dropIfExists('alokasi_batch');
        Schema::dropIfExists('batch_stok');
    }
};