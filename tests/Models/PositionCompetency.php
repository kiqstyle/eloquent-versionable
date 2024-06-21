<?php

namespace Kiqstyle\EloquentVersionable\Test\Models;

use Kiqstyle\EloquentVersionable\VersionedModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Class PositionCompetency
 * @package Kiqstyle\EloquentVersionable\Test\Models
 * @mixin Model
 * @mixin Builder
 */
class PositionCompetency extends VersionedModel
{
    protected $table = 'position_competency';
}
