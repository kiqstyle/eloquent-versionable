<?php

namespace Kiqstyle\EloquentVersionable\Test\Models\Versioning;

use Kiqstyle\EloquentVersionable\Test\Models\CompetencyLevel;

class CompetencyLevelVersioning extends CompetencyLevel
{
    protected $versioningEnabled = false;

    protected $primaryKey = "_id";

    protected $table = 'competency_levels_versioning';
}
