<?php

namespace Kiqstyle\EloquentVersionable\Test\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Kiqstyle\EloquentVersionable\VersionedModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Employee
 * @package Kiqstyle\EloquentVersionable\Test\Models
 * @mixin Model
 * @mixin Builder
 */
class Employee extends VersionedModel
{
    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }
}
