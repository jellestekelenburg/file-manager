<?php

namespace App\Traits;

use Illuminate\Support\Facades\Auth;

trait HasCreatorAndUpdater
{
    protected static function bootHasCreatorAndUpdater(): void
    {
        static::creating(function ($model) {
            if (is_null($model->created_by)) {
                $model->created_by = Auth::id();
            }

            if (is_null($model->updated_by)) {
                $model->updated_by = Auth::id();
            }
        });

        static::updating(function ($model) {
            if (is_null($model->updated_by)) {
                $model->updated_by = Auth::id();
            }
        });
    }
}
