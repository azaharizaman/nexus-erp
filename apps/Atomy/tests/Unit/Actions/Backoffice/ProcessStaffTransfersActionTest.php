<?php

declare(strict_types=1);

namespace Tests\Unit\Actions\Backoffice;

use PHPUnit\Framework\TestCase;
use Mockery;
use Nexus\Erp\Actions\Backoffice\ProcessStaffTransfersAction;
use Nexus\Backoffice\Models\Company;
use Nexus\Backoffice\Models\Staff;
use Nexus\Backoffice\Models\StaffTransfer;
use Nexus\Backoffice\Enums\StaffTransferStatus;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

class ProcessStaffTransfersActionTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_processes_due_transfers_successfully()
    {
        $today = Carbon::now();
        
        $dueTransfers = new Collection([
            (object) [
                'id' => 1,
                'staff_id' => 1,
                'to_office_id' => 2,
                'to_department_id' => 2,
                'to_position_id' => 2,
                'effective_date' => $today->copy()->subDay(),
                'status' => StaffTransferStatus::APPROVED,
                'staff' => (object) [
                    'id' => 1,
                    'name' => 'John Doe',
                    'office_id' => 1,
                    'department_id' => 1,
                    'position_id' => 1,
                ],
            ],
            (object) [
                'id' => 2,
                'staff_id' => 2,
                'to_office_id' => 3,
                'to_department_id' => 3,
                'to_position_id' => 3,
                'effective_date' => $today->copy(),
                'status' => StaffTransferStatus::APPROVED,
                'staff' => (object) [
                    'id' => 2,
                    'name' => 'Jane Smith',
                    'office_id' => 2,
                    'department_id' => 2,
                    'position_id' => 2,
                ],
            ]
        ]);

        // Mock StaffTransfer model
        $transferMock = Mockery::mock('alias:' . StaffTransfer::class);
        $transferMock->shouldReceive('where')
            ->with('status', StaffTransferStatus::APPROVED)
            ->andReturnSelf();
        $transferMock->shouldReceive('where')
            ->with('effective_date', '<=', Mockery::type(Carbon::class))
            ->andReturnSelf();
        $transferMock->shouldReceive('with')
            ->with(['staff'])
            ->andReturnSelf();
        $transferMock->shouldReceive('get')
            ->andReturn($dueTransfers);

        // Mock individual transfer updates
        foreach ($dueTransfers as $transfer) {
            $transferMock->shouldReceive('find')
                ->with($transfer->id)
                ->andReturn($transfer);
            
            $mockTransferObject = Mockery::mock();
            $mockTransferObject->shouldReceive('update')
                ->with(['status' => StaffTransferStatus::COMPLETED])
                ->once();
            
            $transferMock->shouldReceive('find')
                ->with($transfer->id)
                ->andReturn($mockTransferObject);
        }

        // Mock Staff model updates
        $staffMock = Mockery::mock('alias:' . Staff::class);
        foreach ($dueTransfers as $transfer) {
            $mockStaff = Mockery::mock();
            $mockStaff->shouldReceive('update')
                ->once()
                ->with([
                    'office_id' => $transfer->to_office_id,
                    'department_id' => $transfer->to_department_id,
                    'position_id' => $transfer->to_position_id,
                ]);
            
            $staffMock->shouldReceive('find')
                ->with($transfer->staff_id)
                ->andReturn($mockStaff);
        }

        // Mock DB transaction
        $dbMock = Mockery::mock('alias:DB');
        $dbMock->shouldReceive('transaction')
            ->twice() // Once for each transfer
            ->andReturnUsing(function ($callback) {
                return $callback();
            });

        $action = new ProcessStaffTransfersAction();
        $result = $action->execute(null, $today);

        $this->assertEquals(2, $result['processed']);
        $this->assertEquals(0, $result['failed']);
        $this->assertCount(2, $result['transfers']);
        $this->assertEmpty($result['errors']);
        $this->assertFalse($result['dry_run']);
    }

    public function test_filters_transfers_by_company()
    {
        $company = (object) ['id' => 1, 'name' => 'Test Company'];
        $today = Carbon::now();
        
        $dueTransfers = new Collection([
            (object) [
                'id' => 1,
                'staff_id' => 1,
                'to_office_id' => 2,
                'to_department_id' => 2,
                'to_position_id' => 2,
                'effective_date' => $today->copy()->subDay(),
                'status' => StaffTransferStatus::APPROVED,
                'staff' => (object) [
                    'id' => 1,
                    'name' => 'John Doe',
                    'company_id' => 1,
                ],
            ]
        ]);

        $transferMock = Mockery::mock('alias:' . StaffTransfer::class);
        $transferMock->shouldReceive('where')
            ->with('status', StaffTransferStatus::APPROVED)
            ->andReturnSelf();
        $transferMock->shouldReceive('where')
            ->with('effective_date', '<=', Mockery::type(Carbon::class))
            ->andReturnSelf();
        $transferMock->shouldReceive('whereHas')
            ->with('staff', Mockery::type('callable'))
            ->andReturnSelf();
        $transferMock->shouldReceive('with')
            ->with(['staff'])
            ->andReturnSelf();
        $transferMock->shouldReceive('get')
            ->andReturn($dueTransfers);

        $action = new ProcessStaffTransfersAction();
        $result = $action->execute($company, $today, true); // Dry run

        $this->assertEquals(1, $result['processed']);
        $this->assertTrue($result['dry_run']);
        $this->assertCount(1, $result['transfers']);
    }

    public function test_handles_processing_errors_gracefully()
    {
        $today = Carbon::now();
        
        $dueTransfers = new Collection([
            (object) [
                'id' => 1,
                'staff_id' => 1,
                'to_office_id' => 2,
                'to_department_id' => 2,
                'to_position_id' => 2,
                'effective_date' => $today->copy()->subDay(),
                'status' => StaffTransferStatus::APPROVED,
                'staff' => (object) [
                    'id' => 1,
                    'name' => 'John Doe',
                ],
            ]
        ]);

        $transferMock = Mockery::mock('alias:' . StaffTransfer::class);
        $transferMock->shouldReceive('where')
            ->andReturnSelf();
        $transferMock->shouldReceive('with')
            ->andReturnSelf();
        $transferMock->shouldReceive('get')
            ->andReturn($dueTransfers);

        // Mock DB transaction to throw an exception
        $dbMock = Mockery::mock('alias:DB');
        $dbMock->shouldReceive('transaction')
            ->once()
            ->andThrow(new \Exception('Database error'));

        $action = new ProcessStaffTransfersAction();
        $result = $action->execute(null, $today);

        $this->assertEquals(0, $result['processed']);
        $this->assertEquals(1, $result['failed']);
        $this->assertCount(1, $result['errors']);
        $this->assertEquals('Database error', $result['errors'][0]['error']);
        $this->assertEquals(1, $result['errors'][0]['staff_id']);
    }

    public function test_dry_run_mode()
    {
        $today = Carbon::now();
        
        $dueTransfers = new Collection([
            (object) [
                'id' => 1,
                'staff_id' => 1,
                'effective_date' => $today->copy()->subDay(),
                'staff' => (object) [
                    'id' => 1,
                    'name' => 'John Doe',
                ],
            ]
        ]);

        $transferMock = Mockery::mock('alias:' . StaffTransfer::class);
        $transferMock->shouldReceive('where')
            ->andReturnSelf();
        $transferMock->shouldReceive('with')
            ->andReturnSelf();
        $transferMock->shouldReceive('get')
            ->andReturn($dueTransfers);

        // Should not mock any update operations in dry run mode

        $action = new ProcessStaffTransfersAction();
        $result = $action->execute(null, $today, true); // Dry run = true

        $this->assertEquals(1, $result['processed']);
        $this->assertEquals(0, $result['failed']);
        $this->assertTrue($result['dry_run']);
        $this->assertCount(1, $result['transfers']);
        $this->assertEquals('would_be_processed', $result['transfers'][0]['status']);
        $this->assertEmpty($result['errors']);
    }

    public function test_validates_company_parameter()
    {
        $action = new ProcessStaffTransfersAction();
        
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('First parameter must be a Company instance or null');
        
        $action->execute('invalid_company');
    }

    public function test_converts_date_parameter_automatically()
    {
        $transferMock = Mockery::mock('alias:' . StaffTransfer::class);
        $transferMock->shouldReceive('where')
            ->andReturnSelf();
        $transferMock->shouldReceive('with')
            ->andReturnSelf();
        $transferMock->shouldReceive('get')
            ->andReturn(new Collection());

        $action = new ProcessStaffTransfersAction();
        
        // Should not throw an exception when passing a date string
        $result = $action->execute(null, '2023-12-01', true);
        
        $this->assertIsArray($result);
        $this->assertTrue($result['dry_run']);
    }
}