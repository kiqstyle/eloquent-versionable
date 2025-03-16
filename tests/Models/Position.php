<?php

namespace Kiqstyle\EloquentVersionable\Test\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Kiqstyle\EloquentVersionable\VersionedModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class Position
 * @package Kiqstyle\EloquentVersionable\Test\Models
 * @mixin Model
 * @mixin Builder
 */
class Position extends VersionedModel
{
    public array $onDeleteCascadeRelations = ['positionCompetencies'];

    public function competencies(): BelongsToMany
    {
        $pivot = new PositionCompetency;

        return $this->belongsToMany(Competency::class, $pivot->getTable());
    }

    public function positionCompetencies(): HasMany
    {
        return $this->hasMany(PositionCompetency::class);
    }
}
