<?php

namespace Kiqstyle\EloquentVersionable\Test;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Kiqstyle\EloquentVersionable\Test\Models\Employee;
use Kiqstyle\EloquentVersionable\Test\Models\Position;
use Kiqstyle\EloquentVersionable\VersioningServiceProvider;
use Illuminate\Database\Schema\Blueprint;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    public function setUp(): void
    {
        parent::setUp();

        $this->setFakeNow();
        $this->setUpDatabase();
    }

    protected function getPackageProviders($app)
    {
        return [
            VersioningServiceProvider::class
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }

    protected function setUpDatabase(): void
    {
        $this->app['db']->connection()->getSchemaBuilder()->create('employees', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('position_id')->nullable();
            $table->string('name');

            $table->foreign('position_id')->on('id')->references('positions');
            $table->timestamps();
            $table->softDeletes();
        });

        $this->app['db']->connection()->getSchemaBuilder()->create('employees_versioning', function (Blueprint $table) {
            $table->increments('_id');
            $table->unsignedInteger('id');
            $table->unsignedInteger('position_id')->nullable();
            $table->string('name');

            $table->timestamps();
            $table->dateTime('next')->nullable();
            $table->softDeletes();
        });

        $this->app['db']->connection()->getSchemaBuilder()->create('positions', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');

            $table->timestamps();
            $table->softDeletes();
        });

        $this->app['db']->connection()->getSchemaBuilder()->create('positions_versioning', function (Blueprint $table) {
            $table->increments('_id');
            $table->unsignedInteger('id');
            $table->string('name');

            $table->timestamps();
            $table->dateTime('next')->nullable();
            $table->softDeletes();
        });

        $this->app['db']->connection()->getSchemaBuilder()->create('competencies', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');

            $table->timestamps();
            $table->softDeletes();
        });

        $this->app['db']->connection()->getSchemaBuilder()->create('competencies_versioning', function (Blueprint $table) {
            $table->increments('_id');
            $table->unsignedInteger('id');
            $table->string('name');

            $table->timestamps();
            $table->dateTime('next')->nullable();
            $table->softDeletes();
        });

        $this->app['db']->connection()->getSchemaBuilder()->create('position_competency', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('position_id');
            $table->unsignedInteger('competency_id');

            $table->foreign('position_id')->on('id')->references('positions');
            $table->foreign('competency_id')->on('id')->references('competencies');
            $table->timestamps();
            $table->softDeletes();
        });

        $this->app['db']->connection()->getSchemaBuilder()->create('position_competency_versioning', function (Blueprint $table) {
            $table->increments('_id');
            $table->unsignedInteger('id');
            $table->unsignedInteger('position_id');
            $table->unsignedInteger('competency_id');

            $table->timestamps();
            $table->dateTime('next')->nullable();
            $table->softDeletes();
        });

        collect(range(1, 3))->each(function (int $i) {
            Position::create(['name' => $i]);
        });

        $position = Position::first();
        collect(range(1, 3))->each(function (int $i) use ($position) {
            Employee::create(['name' => $i, 'position_id' => $position->id]);
        });
    }

    protected function update(Model $entity, array $attributes): void
    {
        Carbon::setTestNow(Carbon::now()->addSecond());
        $entity->update($attributes);
    }

    protected function setFakeNow(string $time = '2019-01-01 12:00:00'): Carbon
    {
        $time = Carbon::createFromFormat('Y-m-d H:i:s', $time);
        Carbon::setTestNow($time);

        return $time;
    }

    protected function assertOriginalEqualsVersioning(Model $original, Model $versioned): void
    {
        $this->assertEquals($original->id, $versioned->id);
        $this->assertEquals($original->name, $versioned->name);
        $this->assertEquals($original->created_at, $versioned->created_at);
        $this->assertEquals($original->updated_at, $versioned->updated_at);
        $this->assertEquals($original->deleted_at, $versioned->deleted_at);

        $this->assertNotNull($versioned->_id);
        $this->assertNull($versioned->next);
    }

    protected function getVersioned(Model $entity): Collection
    {
        return $entity->withoutGlobalScopes()->where('id', $entity->id)->get();
    }
}
