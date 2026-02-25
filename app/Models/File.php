<?php

namespace App\Models;

use App\Traits\HasCreatorAndUpdater;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Kalnoy\Nestedset\NodeTrait;

class File extends Model
{
    use HasCreatorAndUpdater, NodeTrait, SoftDeletes;

    public mixed $name;
    /**
     * @var int|mixed
     */
    public mixed $is_folder;

    public function isOwnedBy($userId): bool
    {
        return $this->created_by == $userId;
    }
}
