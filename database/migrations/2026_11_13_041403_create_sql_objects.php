<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // 1) FUNCTION: total faktur = jumlah seluruh subtotal detail
        DB::unprepared(<<<'SQL'
        CREATE OR REPLACE FUNCTION hitung_total_faktur(p_penjualan_id bigint)
        RETURNS numeric
        LANGUAGE sql STABLE
        AS $$
            SELECT COALESCE(SUM(subtotal), 0)
              FROM detail_penjualan
             WHERE penjualan_id = p_penjualan_id;
        $$;
        SQL);

        // 2) TRIGGER FUNCTION: kurangi stok + catat log. Gagal bila stok tidak cukup.
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

            -- Kondisi "jumlah >= NEW.jumlah" membuat pengecekan dan pengurangan
            -- satu langkah atomik (aman dari dua transaksi bersamaan)
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

        DB::unprepared(<<<'SQL'
        CREATE TRIGGER trg_detail_penjualan_kurangi_stok
        AFTER INSERT ON detail_penjualan
        FOR EACH ROW EXECUTE FUNCTION trg_kurangi_stok();
        SQL);

        // 3) STORED PROCEDURE: simpan satu transaksi penjualan (header + banyak detail).
        //    Satu CALL = satu transaksi. Jika ada error, SEMUA otomatis di-rollback.
        DB::unprepared(<<<'SQL'
        CREATE OR REPLACE PROCEDURE simpan_penjualan(
            p_no_faktur   varchar,
            p_cabang_id   bigint,
            p_pengguna_id bigint,
            p_items       jsonb      -- contoh: [{"obat_id":1,"jumlah":2}]
        )
        LANGUAGE plpgsql
        SECURITY DEFINER
        SET search_path = public
        AS $$
        DECLARE
            v_role          varchar;
            v_cabang_user   bigint;
            v_penjualan_id  bigint;
            v_item          jsonb;
            v_obat_id       bigint;
            v_jumlah        integer;
            v_harga         numeric;
            v_kode_kategori varchar;
        BEGIN
            SELECT role, cabang_id INTO v_role, v_cabang_user
              FROM pengguna WHERE id = p_pengguna_id;
            IF NOT FOUND THEN
                RAISE EXCEPTION 'Pengguna tidak ditemukan';
            END IF;

            IF v_role <> 'admin_pusat' AND v_cabang_user <> p_cabang_id THEN
                RAISE EXCEPTION 'Pengguna tidak boleh bertransaksi di cabang lain';
            END IF;

            IF jsonb_typeof(p_items) <> 'array' OR jsonb_array_length(p_items) = 0 THEN
                RAISE EXCEPTION 'Transaksi minimal memiliki satu item';
            END IF;

            INSERT INTO penjualan (no_faktur, cabang_id, pengguna_id)
            VALUES (p_no_faktur, p_cabang_id, p_pengguna_id)
            RETURNING id INTO v_penjualan_id;

            FOR v_item IN SELECT jsonb_array_elements(p_items)
            LOOP
                v_obat_id := (v_item->>'obat_id')::bigint;
                v_jumlah  := (v_item->>'jumlah')::integer;

                SELECT o.harga_jual, k.kode INTO v_harga, v_kode_kategori
                  FROM obat o JOIN kategori_obat k ON k.id = o.kategori_id
                 WHERE o.id = v_obat_id;
                IF NOT FOUND THEN
                    RAISE EXCEPTION 'Obat id % tidak ditemukan', v_obat_id;
                END IF;

                -- Aturan bisnis: Obat Keras (kode OK) hanya boleh dijual Apoteker
                IF v_kode_kategori = 'OK' AND v_role <> 'apoteker' THEN
                    RAISE EXCEPTION 'Obat Keras hanya dapat dijual oleh Apoteker';
                END IF;

                -- Trigger akan mengurangi stok & mencatat log
                INSERT INTO detail_penjualan (penjualan_id, obat_id, jumlah, harga_satuan)
                VALUES (v_penjualan_id, v_obat_id, v_jumlah, v_harga);
            END LOOP;

            UPDATE penjualan
               SET total = hitung_total_faktur(v_penjualan_id)
             WHERE id = v_penjualan_id;
        END;
        $$;
        SQL);

        // 4) VIEW laporan stok kritis (stok <= stok minimum)
        DB::unprepared(<<<'SQL'
        CREATE OR REPLACE VIEW v_stok_kritis AS
        SELECT s.cabang_id,
               c.kode AS kode_cabang,
               c.nama AS nama_cabang,
               o.id   AS obat_id,
               o.kode AS kode_obat,
               o.nama AS nama_obat,
               s.jumlah AS stok,
               o.stok_minimum
          FROM stok s
          JOIN cabang c ON c.id = s.cabang_id
          JOIN obat   o ON o.id = s.obat_id
         WHERE s.jumlah <= o.stok_minimum;
        SQL);
    }

    public function down(): void
    {
        DB::unprepared('DROP VIEW IF EXISTS v_stok_kritis');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_detail_penjualan_kurangi_stok ON detail_penjualan');
        DB::unprepared('DROP PROCEDURE IF EXISTS simpan_penjualan(varchar, bigint, bigint, jsonb)');
        DB::unprepared('DROP FUNCTION IF EXISTS trg_kurangi_stok()');
        DB::unprepared('DROP FUNCTION IF EXISTS hitung_total_faktur(bigint)');
    }
};