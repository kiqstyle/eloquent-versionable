<?php

namespace Kiqstyle\EloquentVersionable\Test;

use Kiqstyle\EloquentVersionable\SyncManyToManyWithVersioning;
use Kiqstyle\EloquentVersionable\Test\Models\Competency;
use Kiqstyle\EloquentVersionable\Test\Models\Employee;
use Kiqstyle\EloquentVersionable\Test\Models\Position;
use Kiqstyle\EloquentVersionable\Test\Models\PositionCompetency;
use Illuminate\Support\Facades\DB;

class VersionablePersistenceTest extends TestCase
{

    /** @test */
    public function it_creates_versioning_register_on_model_create()
    {
        $employee = Employee::first();
        $versioned = $this->getVersioned($employee);

        $this->assertOriginalEqualsVersioning($employee, $versioned->get(0));
    }

    /** @test */
    public function it_creates_versioning_register_on_model_update()
    {
        $employee = Employee::first();
        $this->update($employee, ['name' => 'updated']);
        $this->update($employee, ['name' => 'updated 2']);

        $versioned = $this->getVersioned($employee);

        $this->assertEquals('employee 1', $versioned->get(0)->name);
        $this->assertEquals($versioned->get(0)->next, $versioned->get(1)->updated_at);

        $this->assertEquals('updated', $versioned->get(1)->name);
        $this->assertEquals($versioned->get(1)->next, $versioned->get(2)->updated_at);

        $this->assertOriginalEqualsVersioning($employee, $versioned->get(2));
        $this->assertNull($versioned->get(2)->deleted_at);
    }

    /** @test */
    public function it_works_with_soft_delete()
    {
        $employee = Employee::first();
        $this->update($employee, ['name' => 'updated']);
        $employee->delete();

        $versioned = $this->getVersioned($employee);

        $this->assertEquals('employee 1', $versioned->get(0)->name);
        $this->assertEquals($versioned->get(0)->next, $versioned->get(1)->updated_at);

        $this->assertEquals('updated', $versioned->get(1)->name);
        $this->assertEquals($versioned->get(1)->next, $versioned->get(2)->updated_at);

        $this->assertOriginalEqualsVersioning($employee, $versioned->get(2));
        $this->assertNotNull($versioned->get(2)->deleted_at);
    }

    /** @test */
    public function it_sync_versioning_register_on_many_to_many_service()
    {
        $position = Position::first();
        $competencies = collect(range(1, 3))->map(function (int $i) {
            return Competency::create(['name' => $i]);
        });

        (new SyncManyToManyWithVersioning)->run($position, $competencies->pluck('id')->toArray(), new PositionCompetency, ['entityKey' => 'position_id', 'relationKey' => 'competency_id']);

        $position = Position::with('competencies')->first();
        $this->assertCount(3, $position->competencies);

        $databaseRegisters = DB::select('select * from position_competency');
        $this->assertCount(3, $databaseRegisters);
        $this->assertEquals(1, $databaseRegisters[0]->id);
        $this->assertEquals(1, $databaseRegisters[0]->position_id);
        $this->assertEquals(1, $databaseRegisters[0]->competency_id);
        $this->assertEquals(2, $databaseRegisters[1]->id);
        $this->assertEquals(1, $databaseRegisters[1]->position_id);
        $this->assertEquals(2, $databaseRegisters[1]->competency_id);
        $this->assertEquals(3, $databaseRegisters[2]->id);
        $this->assertEquals(1, $databaseRegisters[2]->position_id);
        $this->assertEquals(3, $databaseRegisters[2]->competency_id);

        $databaseVersionedRegisters = DB::select('select * from position_competency_versioning');
        $this->assertEquals(1, $databaseVersionedRegisters[0]->_id);
        $this->assertEquals(1, $databaseVersionedRegisters[0]->id);
        $this->assertEquals(1, $databaseVersionedRegisters[0]->position_id);
        $this->assertEquals(1, $databaseVersionedRegisters[0]->competency_id);
        $this->assertEquals(2, $databaseVersionedRegisters[1]->_id);
        $this->assertEquals(2, $databaseVersionedRegisters[1]->id);
        $this->assertEquals(1, $databaseVersionedRegisters[1]->position_id);
        $this->assertEquals(2, $databaseVersionedRegisters[1]->competency_id);
        $this->assertEquals(3, $databaseVersionedRegisters[2]->id);
        $this->assertEquals(3, $databaseVersionedRegisters[2]->_id);
        $this->assertEquals(1, $databaseVersionedRegisters[2]->position_id);
        $this->assertEquals(3, $databaseVersionedRegisters[2]->competency_id);

        $competencies = collect(range(1, 3))->map(function (int $i) {
            return Competency::create(['name' => $i]);
        });

        (new SyncManyToManyWithVersioning)->run($position, $competencies->pluck('id')->toArray(), new PositionCompetency, ['entityKey' => 'position_id', 'relationKey' => 'competency_id']);
        $position = Position::with('competencies')->first();

        $this->assertCount(3, $position->competencies);
        $this->assertEquals(4, $position->competencies->get(0)->id);
        $this->assertEquals(5, $position->competencies->get(1)->id);
        $this->assertEquals(6, $position->competencies->get(2)->id);

        $databaseVersionedRegisters = DB::select('select * from position_competency_versioning');
        $this->assertEquals(1, $databaseVersionedRegisters[0]->_id);
        $this->assertEquals(1, $databaseVersionedRegisters[0]->id);
        $this->assertEquals(1, $databaseVersionedRegisters[0]->position_id);
        $this->assertEquals(1, $databaseVersionedRegisters[0]->competency_id);
        $this->assertNotNull($databaseVersionedRegisters[0]->next);
        $this->assertEquals(2, $databaseVersionedRegisters[1]->_id);
        $this->assertEquals(2, $databaseVersionedRegisters[1]->id);
        $this->assertEquals(1, $databaseVersionedRegisters[1]->position_id);
        $this->assertEquals(2, $databaseVersionedRegisters[1]->competency_id);
        $this->assertNotNull($databaseVersionedRegisters[1]->next);
        $this->assertEquals(3, $databaseVersionedRegisters[2]->_id);
        $this->assertEquals(3, $databaseVersionedRegisters[2]->id);
        $this->assertEquals(1, $databaseVersionedRegisters[2]->position_id);
        $this->assertEquals(3, $databaseVersionedRegisters[2]->competency_id);
        $this->assertNotNull($databaseVersionedRegisters[2]->next);

        $this->assertEquals(4, $databaseVersionedRegisters[3]->_id);
        $this->assertEquals(1, $databaseVersionedRegisters[3]->id);
        $this->assertEquals(1, $databaseVersionedRegisters[3]->position_id);
        $this->assertEquals(1, $databaseVersionedRegisters[3]->competency_id);
        $this->assertNull($databaseVersionedRegisters[3]->next);
        $this->assertNotNull($databaseVersionedRegisters[3]->deleted_at);
        $this->assertEquals(5, $databaseVersionedRegisters[4]->_id);
        $this->assertEquals(2, $databaseVersionedRegisters[4]->id);
        $this->assertEquals(1, $databaseVersionedRegisters[4]->position_id);
        $this->assertEquals(2, $databaseVersionedRegisters[4]->competency_id);
        $this->assertNull($databaseVersionedRegisters[4]->next);
        $this->assertNotNull($databaseVersionedRegisters[4]->deleted_at);
        $this->assertEquals(6, $databaseVersionedRegisters[5]->_id);
        $this->assertEquals(3, $databaseVersionedRegisters[5]->id);
        $this->assertEquals(1, $databaseVersionedRegisters[5]->position_id);
        $this->assertEquals(3, $databaseVersionedRegisters[5]->competency_id);
        $this->assertNull($databaseVersionedRegisters[5]->next);
        $this->assertNotNull($databaseVersionedRegisters[5]->deleted_at);


        $this->assertEquals(7, $databaseVersionedRegisters[6]->_id);
        $this->assertEquals(4, $databaseVersionedRegisters[6]->id);
        $this->assertEquals(1, $databaseVersionedRegisters[6]->position_id);
        $this->assertEquals(4, $databaseVersionedRegisters[6]->competency_id);
        $this->assertNull($databaseVersionedRegisters[6]->next);
        $this->assertEquals(8, $databaseVersionedRegisters[7]->_id);
        $this->assertEquals(5, $databaseVersionedRegisters[7]->id);
        $this->assertEquals(1, $databaseVersionedRegisters[7]->position_id);
        $this->assertEquals(5, $databaseVersionedRegisters[7]->competency_id);
        $this->assertNull($databaseVersionedRegisters[7]->next);
        $this->assertEquals(9, $databaseVersionedRegisters[8]->_id);
        $this->assertEquals(6, $databaseVersionedRegisters[8]->id);
        $this->assertEquals(1, $databaseVersionedRegisters[8]->position_id);
        $this->assertEquals(6, $databaseVersionedRegisters[8]->competency_id);
        $this->assertNull($databaseVersionedRegisters[8]->next);
    }
}
