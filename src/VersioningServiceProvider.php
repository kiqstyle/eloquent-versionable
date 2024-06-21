<?php

namespace Kiqstyle\EloquentVersionable;

use Carbon\Carbon;
use Illuminate\Support\ServiceProvider;

class VersioningServiceProvider extends ServiceProvider
{
    /**
     * Register bindings in the container.
     */
    public function register(): void
    {
        $this->app->singleton('versioningDate', function () {
            return (new VersioningDate())->setDate(Carbon::now()->addDay());
        });
    }
}
