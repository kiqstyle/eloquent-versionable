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
use function Pest\version;

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

        $this->assertEquals(1, $employee->id);
        $this->assertEquals('updated 2019', $employee->name);
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

        $this->assertEquals(1, $employee->position->id);
        $this->assertEquals('updated 2019', $employee->position->name);
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
    public function it_should_bring_a_record_from_the_future_to_the_past_without_modifying_updated_at()
    {
        $employee = Employee::find(1);
        $this->update($employee, ['name' => 'updated employee 1.1']);
        $this->update($employee, ['name' => 'updated employee 1.2']);
        $this->update($employee, ['name' => 'updated employee 1.3']);
        $this->update($employee, ['name' => 'updated employee 1.4']);

        versioningDate()->setDate('2019-01-01 12:00:02');

        $employee = Employee::find(1);

        $this->assertEquals('updated employee 1.2', $employee->name);

        // Bring a record from the future to the past without changing updated_at
        DB::table('employees_versioning')
            ->where('name', 'updated employee 1.2')
            ->update(['next' => '2019-01-01 12:00:02']);

        DB::table('employees_versioning')
            ->where('name', 'updated employee 1.3')
            ->update(['versioned_at' => '2019-01-01 12:00:02']);

        $employee = Employee::find(1);

        $this->assertEquals('updated employee 1.3', $employee->name);
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
            // when updating, the last method to be called is createVersionedRecord
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
        // if there was a transaction opened, it would roll back after script end
        DB::rollBack();

        // losing all data that should not be on a transaction
        $this->assertDatabaseHas('employees', ['name' => 'New employee']);
    }

    /** @test */
    public function it_should_get_recent_registers_with_join()
    {
        $employee = Employee::find(1);
        $this->update($employee, ['name' => 'updated employee 1']);

        $position = Position::find(1);
        $this->update($position, ['name' => 'updated position 1']);

        $employees = Employee::query()
            ->select([
                'employees_versioning.name as employee_name',
                'positions_versioning.name as position_name',
            ])
            ->join('positions_versioning', 'employees_versioning.position_id', '=', 'positions_versioning.id')
            ->orderBy('employees_versioning.id')
            ->get();

        $this->assertEquals('updated employee 1', $employees->first()->employee_name);
        $this->assertEquals('updated position 1', $employees->first()->position_name);

        $this->assertEquals('employee 2', $employees->get(1)->employee_name);
        $this->assertEquals('updated position 1', $employees->get(1)->position_name);

        $this->assertEquals('employee 3', $employees->get(2)->employee_name);
        $this->assertEquals('updated position 1', $employees->get(2)->position_name);
    }

    /** @test */
    public function it_should_get_old_registers_with_join()
    {
        versioningDate()->setDate(now());

        $employee = Employee::find(1);
        $this->update($employee, ['name' => 'updated employee 1']);

        $position = Position::find(1);
        $this->update($position, ['name' => 'updated position 1']);

        $employee = Employee::query()
            ->select([
                'employees_versioning.name as employee_name',
                'positions_versioning.name as position_name',
                'employees_versioning.updated_at as employee_updated_at',
            ])
            ->join('positions_versioning', 'employees_versioning.position_id', '=', 'positions_versioning.id')
            ->find(1);

        $this->assertEquals('employee 1', $employee->employee_name);
        $this->assertEquals('position 1', $employee->position_name);
    }
}
