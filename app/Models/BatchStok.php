<?php

namespace App\Models;

use App\Models\Concerns\HanyaCabangSendiri;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BatchStok extends Model
{
    use HanyaCabangSendiri;

    protected $table = 'batch_stok';

    protected function casts(): array
    {
        return ['tanggal_kedaluwarsa' => 'date'];
    }

    public function cabang(): BelongsTo
    {
        return $this->belongsTo(Cabang::class, 'cabang_id');
    }

    public function obat(): BelongsTo
    {
        return $this->belongsTo(Obat::class, 'obat_id');
    }

    // aman | segera (90 hari ke depan) | kedaluwarsa
    public function status(): string
    {
        if ($this->tanggal_kedaluwarsa->lt(today())) {
            return 'kedaluwarsa';
        }

        return $this->tanggal_kedaluwarsa->lte(today()->addDays(90)) ? 'segera' : 'aman';
    }
}