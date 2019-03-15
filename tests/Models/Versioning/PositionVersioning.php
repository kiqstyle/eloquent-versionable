<?php

namespace Cohrosonline\EloquentVersionable\Test\Models\Versioning;

use Cohrosonline\EloquentVersionable\Test\Models\Position;

class PositionVersioning extends Position
{
    protected $versioningEnabled = false;

    protected $primaryKey = "_id";

    protected $table = 'positions_versioning';
}