#!/bin/sh
# Dijalankan otomatis oleh image postgres HANYA saat volume data masih kosong.
# Membuat akun owner (bukan superuser), database, dan 4 akun aplikasi per peran.
# Kata sandi diambil dari env (.env.production), bukan dari roles.sql.
set -e

psql -v ON_ERROR_STOP=1 --username "$POSTGRES_USER" --dbname postgres \
    -v db="$DB_DATABASE" \
    -v owner="$DB_USERNAME"            -v owner_pw="$DB_PASSWORD" \
    -v login="$DB_LOGIN_USERNAME"      -v login_pw="$DB_LOGIN_PASSWORD" \
    -v admin="$DB_ADMIN_USERNAME"      -v admin_pw="$DB_ADMIN_PASSWORD" \
    -v apoteker="$DB_APOTEKER_USERNAME" -v apoteker_pw="$DB_APOTEKER_PASSWORD" \
    -v kasir="$DB_KASIR_USERNAME"      -v kasir_pw="$DB_KASIR_PASSWORD" <<'SQL'
CREATE ROLE :"owner"    LOGIN PASSWORD :'owner_pw';
CREATE ROLE :"login"    LOGIN PASSWORD :'login_pw';
CREATE ROLE :"admin"    LOGIN PASSWORD :'admin_pw';
CREATE ROLE :"apoteker" LOGIN PASSWORD :'apoteker_pw';
CREATE ROLE :"kasir"    LOGIN PASSWORD :'kasir_pw';

CREATE DATABASE :"db" OWNER :"owner";

-- Hanya akun aplikasi yang boleh terhubung ke database ini
REVOKE CONNECT ON DATABASE :"db" FROM PUBLIC;
GRANT CONNECT ON DATABASE :"db" TO :"owner", :"login", :"admin", :"apoteker", :"kasir";
SQL
