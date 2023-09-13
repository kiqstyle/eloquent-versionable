<?php

namespace Kiqstyle\EloquentVersionable\Test;

use Exception;
use Illuminate\Support\Facades\DB;
use Kiqstyle\EloquentVersionable\SyncManyToManyWithVersioning;
use Kiqstyle\EloquentVersionable\Test\Models\Competency;
use Kiqstyle\EloquentVersionable\Test\Models\Employee;
use Kiqstyle\EloquentVersionable\Test\Models\Position;
use Kiqstyle\EloquentVersionable\Test\Models\PositionCompetency;
use Kiqstyle\EloquentVersionable\VersioningPersistence;
use Mockery\MockInterface;

class VersionableQueryTest extends TestCase
{
    /** @test */
    public function it_finds_last_versioned_register()
    {
        $employee = Employee::first();
        $this->update($employee, ['name' => 'updated']);
        $this->update($employee, ['name' => 'updated 2']);

        $employeeModel = new Employee;
        $employeeModel->unsetVersioning();
        $employee = $employeeModel->first();
        $versionedDummy = Employee::find($employee->id);

        $this->assertOriginalEqualsVersioning($employee, $versionedDummy);
    }

    /** @test */
    public function it_works_with_soft_delete()
    {
        $employee = Employee::first();
        $this->update($employee, ['name' => 'updated']);
        $employee->delete();

        $employeeModel = new Employee;
        $employeeModel->unsetVersioning();
        $employee = $employeeModel->withTrashed()->first();
        $versioned = $this->getVersioned($employee);

        $this->assertOriginalEqualsVersioning($employee, $versioned->get(2));
    }

    /** @test */
    public function it_finds_old_registers_based_on_versioning_date()
    {
        $employee = Employee::first();

        $this->update($employee, ['name' => 'updated 2019']);
        $this->update($employee, ['name' => 'updated 2020']);

        versioningDate()->setDate('2019-01-01 12:00:01');

        $employee = Employee::find($employee->id);

        $this->assertEquals($employee->id, 1);
        $this->assertEquals($employee->name, 'updated 2019');
        $this->assertNotNull($employee->next);
    }

    /** @test */
    public function it_works_with_update_or_create()
    {
        $this->setFakeNow('2019-01-01 12:00:01');
        $employee = Employee::updateOrCreate(['id' => '999'], ['name' => 'new employee']);
        $versioned = $this->getVersioned($employee);

        $this->assertOriginalEqualsVersioning($employee, $versioned->get(0));

        $employee = Employee::updateOrCreate(['id' => '999'], ['name' => 'updated employee']);
        $versioned = $this->getVersioned($employee);

        $this->assertOriginalEqualsVersioning($employee, $versioned->get(1));
    }

    /** @test */
    public function it_finds_versioned_results_with_has_one()
    {
        $employee = Employee::with('position')->first();
        $this->update($employee->position, ['name' => 'updated']);
        $this->update($employee->position, ['name' => 'updated 2']);

        $versioned = $this->getVersioned($employee->position);

        $this->assertOriginalEqualsVersioning($employee->position, $versioned->get(2));
    }

    /** @test */
    public function it_works_with_soft_delete_in_has_one()
    {
        $employee = Employee::with('position')->first();
        $this->update($employee->position, ['name' => 'updated']);
        $employee->position->delete();

        $positionModel = new Position;
        $positionModel->unsetVersioning();
        $position = $positionModel->withTrashed()->first();
        $versioned = $this->getVersioned($position);

        $this->assertOriginalEqualsVersioning($position, $versioned->get(2));
    }

    /** @test */
    public function it_finds_versioned_results_with_has_one_based_on_versioning_date()
    {
        $employee = Employee::with('position')->first();
        $this->update($employee->position, ['name' => 'updated 2019']);
        $this->update($employee->position, ['name' => 'updated 2020']);

        $now = $this->setFakeNow('2019-01-01 12:00:01');
        versioningDate()->setDate($now);

        $employee = Employee::with('position')->first();

        $this->assertEquals($employee->position->id, 1);
        $this->assertEquals($employee->position->name, 'updated 2019');
        $this->assertNotNull($employee->position->next);
    }

    /** @test */
    public function it_finds_versioned_results_with_many_to_many_relationship_based_on_versioning_date()
    {
        $position = Position::first();
        $competencies = collect(range(1, 3))->map(function (int $i) {
            return Competency::create(['name' => $i]);
        });

        $this->setFakeNow('2019-01-01 12:00:01');
        (new SyncManyToManyWithVersioning)->run($position, $competencies->pluck('id')->toArray(), new PositionCompetency, ['entityKey' => 'position_id', 'relationKey' => 'competency_id']);

        $competencies = collect(range(4, 7))->map(function (int $i) {
            return Competency::create(['name' => $i]);
        });

        $this->setFakeNow('2020-01-01 12:00:01');
        (new SyncManyToManyWithVersioning)->run($position, $competencies->pluck('id')->toArray(), new PositionCompetency, ['entityKey' => 'position_id', 'relationKey' => 'competency_id']);

        versioningDate()->setDate('2019-01-01 12:00:01');

        $position = Position::first();
        $this->assertCount(3, $position->competencies);
        $this->assertEquals(1, $position->competencies->get(0)->id);
        $this->assertEquals(2, $position->competencies->get(1)->id);
        $this->assertEquals(3, $position->competencies->get(2)->id);
    }

    /** @test */
    public function it_should_not_create_original_register_when_versioning_fail()
    {
        $this->expectException(Exception::class);

        $this->mock(VersioningPersistence::class, function (MockInterface $mock) {
            $mock->shouldReceive('createVersionedRecord')
                ->andThrow(new Exception('Failed to create.'));
        });

        Employee::create([
            'name' => 'New employee'
        ]);

        $this->assertDatabaseMissing('employees', ['name' => 'New employee']);
        $this->assertDatabaseMissing('employees_versioning', ['name' => 'New employee']);
    }

    /** @test */
    public function it_should_not_update_original_register_when_versioning_fail()
    {
        $this->expectException(Exception::class);

        $employee = Employee::create([
            'name' => 'New employee'
        ]);

        $this->partialMock(VersioningPersistence::class, function (MockInterface $mock) {
            // when updating, the last method to by called is createVersionedRecord
            $mock->shouldReceive('createVersionedRecord')
                ->andThrow(new Exception('Failed to update.'));
        });

        $this->setFakeNow('2020-01-01 12:00:00');
        $employee->update(['name' => 'Updated employee']);

        $this->assertDatabaseMissing('employees', ['name' => 'Updated employee']);
        $this->assertDatabaseMissing('employees_versioning', ['name' => 'New employee', 'next' => '2020-01-01 12:00:00']);
        $this->assertDatabaseMissing('employees_versioning', ['name' => 'Updated employee', 'updated_at' => '2020-01-01 12:00:00', 'next' => null]);
    }

    /** @test */
    public function it_should_not_delete_original_register_when_versioning_fail()
    {
        $this->expectException(Exception::class);

        $employee = Employee::create([
            'name' => 'New employee'
        ]);

        $this->partialMock(VersioningPersistence::class, function (MockInterface $mock) {
            $mock->shouldReceive('createDeletedVersionedRecord')
                ->andThrow(new Exception('Failed to delete.'));
        });

        $this->setFakeNow('2020-01-01 12:00:00');
        $employee->delete();

        $this->assertDatabaseMissing('employees', ['name' => 'New employee', 'deleted_at' => '2020-01-01 12:00:00']);
        $this->assertDatabaseMissing('employees_versioning', ['name' => 'New employee', 'next' => '2020-01-01 12:00:00', 'deleted_at' => null]);
        $this->assertDatabaseMissing('employees_versioning', ['name' => 'New employee', 'deleted_at' => '2020-01-01 12:00:00', 'next' => null]);
    }

    /** @test */
    public function it_should_not_let_a_transaction_opened()
    {
        $employee = Employee::create(['name' => 'zika']);
        $employee->update(['name' => 'updated']);
        // When versioning was trying to update again, it would let a transaction opened
        $employee->update(['name' => 'updated']);

        Employee::create(['name' => 'New employee']);
        // if there was a transaction opened, it would rollBack after script end
        DB::rollBack();

        // losing all data that should not be on a transaction
        $this->assertDatabaseHas('employees', ['name' => 'New employee']);
    }
}
