<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KategoriObat extends Model
{
    protected $table = 'kategori_obat';

    protected $fillable = ['kode', 'nama'];

    public function obat(): HasMany
    {
        return $this->hasMany(Obat::class, 'kategori_id');
    }
}