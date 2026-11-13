<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Pengguna extends Authenticatable
{
    public const ADMIN_PUSAT = 'admin_pusat';
    public const APOTEKER = 'apoteker';
    public const KASIR = 'kasir';

    protected $table = 'pengguna';

    protected $fillable = ['username', 'password', 'nama_lengkap', 'role', 'cabang_id'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        // Kata sandi otomatis di-hash (bcrypt) saat disimpan
        return ['password' => 'hashed'];
    }

    public function cabang(): BelongsTo
    {
        return $this->belongsTo(Cabang::class, 'cabang_id');
    }

    public function isAdminPusat(): bool
    {
        return $this->role === self::ADMIN_PUSAT;
    }

    public function isApoteker(): bool
    {
        return $this->role === self::APOTEKER;
    }

    public function isKasir(): bool
    {
        return $this->role === self::KASIR;
    }

    public function labelRole(): string
    {
        return [
            self::ADMIN_PUSAT => 'Admin Pusat',
            self::APOTEKER => 'Apoteker',
            self::KASIR => 'Kasir',
        ][$this->role] ?? $this->role;
    }
}