<?php

namespace App\Models\Concerns;

use App\Models\Scopes\CabangScope;

trait HanyaCabangSendiri
{
    protected static function bootHanyaCabangSendiri(): void
    {
        static::addGlobalScope(new CabangScope);
    }
}