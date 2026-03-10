<?php

namespace App\Models;

use App\Support\SizeFormatter;
use App\Traits\HasCreatorAndUpdater;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
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

    public function getFileSize(): string
    {
        return SizeFormatter::formatBytes($this->size);
    }

    public function isOwnedBy($userId): bool
    {
        return $this->created_by == $userId;
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($model) {
            if (! $model->parent) {
                return;
            }

            $model->path = (! $model->parent->isRoot() ? $model->parent->path.'/' : '').Str::slug($model->name);
        });

        static::deleted(function (File $model) {
            if (! $model->is_folder) {
                Storage::delete($model->path);
            }
            // In this instance, Files get removed IF selected, selected folder ALSO get removed
            // BUT files inside a removed folder will NOT be deleted, TODO: Fix this issue
            // CHECK: (5:32:30 (timestamp YT))
        });
    }
}
