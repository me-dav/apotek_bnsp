-- Jalankan sebagai superuser, per database:
--   psql -h 127.0.0.1 -U postgres -d apotek_dev -f database/sql/roles.sql
-- Jalankan ulang setiap kali tabel dibuat ulang (migrate:fresh).

-- 1) Buat akun (sekali saja, aman dijalankan ulang)
DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_roles WHERE rolname = 'apotek_login') THEN
        CREATE ROLE apotek_login LOGIN PASSWORD 'login123';
    END IF;
    IF NOT EXISTS (SELECT 1 FROM pg_roles WHERE rolname = 'apotek_admin') THEN
        CREATE ROLE apotek_admin LOGIN PASSWORD 'admin123';
    END IF;
    IF NOT EXISTS (SELECT 1 FROM pg_roles WHERE rolname = 'apotek_apoteker') THEN
        CREATE ROLE apotek_apoteker LOGIN PASSWORD 'apoteker123';
    END IF;
    IF NOT EXISTS (SELECT 1 FROM pg_roles WHERE rolname = 'apotek_kasir') THEN
        CREATE ROLE apotek_kasir LOGIN PASSWORD 'kasir123';
    END IF;
END $$;

-- 2) Cabut hak eksekusi bawaan, lalu beri hanya yang perlu
REVOKE EXECUTE ON ALL ROUTINES IN SCHEMA public FROM PUBLIC;

GRANT USAGE ON SCHEMA public
    TO apotek_login, apotek_admin, apotek_apoteker, apotek_kasir;

-- 3) Akun login: hanya membaca pengguna (+ ubah hash/token bila diperlukan Laravel)
GRANT SELECT ON pengguna TO apotek_login;
GRANT UPDATE (password, remember_token, updated_at) ON pengguna TO apotek_login;

-- 4) Semua peran aplikasi boleh membaca data master & transaksi
GRANT SELECT ON cabang, kategori_obat, obat, stok,
                penjualan, detail_penjualan, log_stok, v_stok_kritis
    TO apotek_admin, apotek_apoteker, apotek_kasir;

-- 5) Admin Pusat: kelola data master & pengguna
GRANT INSERT, UPDATE, DELETE ON cabang, kategori_obat, obat, stok, pengguna
    TO apotek_admin;
GRANT SELECT ON pengguna TO apotek_admin;

-- 6) Apoteker: tambah/ubah stok (mis. barang masuk)
GRANT INSERT, UPDATE ON stok TO apotek_apoteker;

-- 7) Apoteker & Kasir: baca pengguna TANPA kolom password
GRANT SELECT (id, username, nama_lengkap, role, cabang_id)
    ON pengguna TO apotek_apoteker, apotek_kasir;
GRANT UPDATE (remember_token) ON pengguna TO apotek_apoteker, apotek_kasir;

-- 8) Sequence untuk INSERT
GRANT USAGE, SELECT ON ALL SEQUENCES IN SCHEMA public
    TO apotek_admin, apotek_apoteker;

-- 9) Tabel infrastruktur Laravel (cache untuk rate limiter login, sessions bila
--    SESSION_DRIVER=database). Dipakai sebelum dan sesudah login, jadi semua akun perlu.
GRANT SELECT, INSERT, UPDATE, DELETE ON cache, cache_locks, sessions
    TO apotek_login, apotek_admin, apotek_apoteker, apotek_kasir;

-- 10) Menyimpan penjualan hanya lewat procedure
GRANT EXECUTE ON PROCEDURE simpan_penjualan(varchar, bigint, bigint, jsonb)
    TO apotek_admin, apotek_apoteker, apotek_kasir;
GRANT EXECUTE ON FUNCTION hitung_total_faktur(bigint)
    TO apotek_admin, apotek_apoteker, apotek_kasir;

GRANT SELECT ON batch_stok, alokasi_batch
    TO apotek_admin, apotek_apoteker, apotek_kasir;
GRANT EXECUTE ON PROCEDURE terima_batch(bigint, bigint, bigint, varchar, date, integer)
    TO apotek_admin, apotek_apoteker;