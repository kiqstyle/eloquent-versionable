<?php

namespace Kiqstyle\EloquentVersionable\Test;

use Kiqstyle\EloquentVersionable\Test\Models\Competency;
use Illuminate\Support\Facades\DB;
use Kiqstyle\EloquentVersionable\SyncManyToManyWithVersioning;
use Kiqstyle\EloquentVersionable\Test\Models\Position;
use Kiqstyle\EloquentVersionable\Test\Models\PositionCompetency;

class VersionableOnDeleteCascadeRelationsTest extends TestCase
{
    /** @test */
    public function it_deletes_many_to_many_relations()
    {
        $position = Position::find(1);
        $competencies = Competency::all();

        (new SyncManyToManyWithVersioning)->run(
            $position,
            $competencies->pluck('id')->toArray(),
            new PositionCompetency,
            ['entityKey' => 'position_id', 'relationKey' => 'competency_id']
        );

        $getDeletedAtsFromTable = fn (string $table) => DB::table($table)->pluck('deleted_at')->filter()->toArray();

        $this->assertEmpty($getDeletedAtsFromTable('position_competency'));
        $this->assertEmpty($getDeletedAtsFromTable('position_competency_versioning'));

        $position->delete();

        $this->assertCount(3, $getDeletedAtsFromTable('position_competency'));
        $this->assertCount(3, $getDeletedAtsFromTable('position_competency_versioning'));
    }

    /** @test */
    public function it_does_not_delete_many_to_many_relations()
    {
        $position = Position::find(1);
        $position->onDeleteCascadeRelations = [];
        $competencies = Competency::all();

        (new SyncManyToManyWithVersioning)->run(
            $position,
            $competencies->pluck('id')->toArray(),
            new PositionCompetency,
            ['entityKey' => 'position_id', 'relationKey' => 'competency_id']
        );

        $getDeletedAtsFromTable = fn (string $table) => DB::table($table)->pluck('deleted_at')->filter()->toArray();

        $this->assertEmpty($getDeletedAtsFromTable('position_competency'));
        $this->assertEmpty($getDeletedAtsFromTable('position_competency_versioning'));

        $position->delete();

        $this->assertEmpty($getDeletedAtsFromTable('position_competency'));
        $this->assertEmpty($getDeletedAtsFromTable('position_competency_versioning'));
    }

    /** @test */
    public function it_deletes_has_many_relations()
    {
        $getDeletedAtsFromTable = fn (string $table) => DB::table($table)->pluck('deleted_at')->filter()->toArray();

        $this->assertEmpty($getDeletedAtsFromTable('competency_levels'));
        $this->assertEmpty($getDeletedAtsFromTable('competency_levels_versioning'));

        Competency::find(1)->delete();

        $this->assertCount(3, $getDeletedAtsFromTable('competency_levels'));
        $this->assertCount(3, $getDeletedAtsFromTable('competency_levels_versioning'));
    }

    /** @test */
    public function it_does_not_delete_has_many_relations()
    {
        $getDeletedAtsFromTable = fn (string $table) => DB::table($table)->pluck('deleted_at')->filter()->toArray();

        $this->assertEmpty($getDeletedAtsFromTable('competency_levels'));
        $this->assertEmpty($getDeletedAtsFromTable('competency_levels_versioning'));

        $competency = Competency::find(1);
        $competency->onDeleteCascadeRelations = [];
        $competency->delete();

        $this->assertEmpty($getDeletedAtsFromTable('competency_levels'));
        $this->assertEmpty($getDeletedAtsFromTable('competency_levels_versioning'));
    }
}
