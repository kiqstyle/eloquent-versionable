<?php

namespace Kiqstyle\EloquentVersionable\Test\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Kiqstyle\EloquentVersionable\VersionedModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class Competency
 * @package Kiqstyle\EloquentVersionable\Test\Models
 * @mixin Model
 * @mixin Builder
 */
class Competency extends VersionedModel
{
    public $onDeleteCascadeRelations = ['levels'];

    public function positions(): BelongsToMany
    {
        return $this->belongsToMany(Position::class)
            ->using(PositionCompetency::class);
    }

    public function levels(): HasMany
    {
        return $this->hasMany(CompetencyLevel::class);
    }
}
