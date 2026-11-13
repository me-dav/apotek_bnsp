<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

class CabangScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $user = Auth::user();

        // Admin Pusat dan proses CLI (seeder/migrate) melihat semua cabang
        if ($user && !$user->isAdminPusat()) {
            $builder->where($model->qualifyColumn('cabang_id'), $user->cabang_id);
        }
    }
}