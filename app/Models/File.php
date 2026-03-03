<?php

namespace App\Models;

use App\Traits\HasCreatorAndUpdater;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Kalnoy\Nestedset\NodeTrait;

class File extends Model
{
    use HasCreatorAndUpdater, NodeTrait, SoftDeletes;

    protected $casts = [
        'is_folder' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(File::class, 'parent_id');
    }

    protected function owner(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value, array $attributes): string => (int) $attributes['created_by'] === (int) Auth::id()
                ? 'me'
                : ($this->user?->name ?? 'unknown'),
        );
    }

    public function isRoot()
    {
        return $this->parent_id == null;
    }

    public function getFileSize()
    {
        $NUM = 1024; // divider to B > KB > MB > GB
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        if ($this->size < 1) {
            return '-';
        }

        $power = $this->size > 0 ? floor(log($this->size, $NUM)) : 0;

        return number_format($this->size / pow($NUM, $power), 2).$units[$power];
    }

    public function isOwnedBy($userId): bool
    {
        return $this->created_by == $userId;
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (! $model->parent) {
                return;
            }

            $model->path = (! $model->parent->isRoot() ? $model->parent->path.'/' : '').Str::slug($model->name);
        });
    }
}
