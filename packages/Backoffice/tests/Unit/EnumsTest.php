<?php

namespace Nexus\Backoffice\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Nexus\Backoffice\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use Nexus\Backoffice\Enums\StaffStatus;
use Nexus\Backoffice\Models\StaffTransfer;
use Nexus\Backoffice\BackOfficeServiceProvider;
use Nexus\Backoffice\Enums\StaffTransferStatus;

#[CoversClass(StaffTransferStatus::class)]
#[CoversClass(StaffTransfer::class)]
#[CoversClass(StaffStatus::class)]
#[CoversClass(BackOfficeServiceProvider::class)]
class EnumsTest extends TestCase
{
    #[Test]
    public function staff_status_enum_has_correct_values()
    {
        $this->assertEquals('active', StaffStatus::ACTIVE->value);
        $this->assertEquals('inactive', StaffStatus::INACTIVE->value);
        $this->assertEquals('terminated', StaffStatus::TERMINATED->value);
        $this->assertEquals('resigned', StaffStatus::RESIGNED->value);
        $this->assertEquals('on_leave', StaffStatus::ON_LEAVE->value);
        $this->assertEquals('retired', StaffStatus::RETIRED->value);
    }

    #[Test]
    public function staff_status_enum_has_correct_labels()
    {
        $this->assertEquals('Active', StaffStatus::ACTIVE->label());
        $this->assertEquals('Inactive', StaffStatus::INACTIVE->label());
        $this->assertEquals('Terminated', StaffStatus::TERMINATED->label());
        $this->assertEquals('Resigned', StaffStatus::RESIGNED->label());
        $this->assertEquals('On Leave', StaffStatus::ON_LEAVE->label());
        $this->assertEquals('Retired', StaffStatus::RETIRED->label());
    }

    #[Test]
    public function staff_status_can_get_all_values()
    {
        $values = StaffStatus::values();
        
        $this->assertCount(6, $values);
        $this->assertContains('active', $values);
        $this->assertContains('inactive', $values);
        $this->assertContains('terminated', $values);
        $this->assertContains('resigned', $values);
        $this->assertContains('on_leave', $values);
        $this->assertContains('retired', $values);
    }

    #[Test]
    public function staff_transfer_status_enum_has_correct_values()
    {
        $this->assertEquals('pending', StaffTransferStatus::PENDING->value);
        $this->assertEquals('approved', StaffTransferStatus::APPROVED->value);
        $this->assertEquals('rejected', StaffTransferStatus::REJECTED->value);
        $this->assertEquals('cancelled', StaffTransferStatus::CANCELLED->value);
        $this->assertEquals('completed', StaffTransferStatus::COMPLETED->value);
    }

    #[Test]
    public function staff_transfer_status_enum_has_correct_labels()
    {
        $this->assertEquals('Pending Approval', StaffTransferStatus::PENDING->label());
        $this->assertEquals('Approved', StaffTransferStatus::APPROVED->label());
        $this->assertEquals('Rejected', StaffTransferStatus::REJECTED->label());
        $this->assertEquals('Cancelled', StaffTransferStatus::CANCELLED->label());
        $this->assertEquals('Completed', StaffTransferStatus::COMPLETED->label());
    }

    #[Test]
    public function staff_transfer_status_can_check_if_can_be_modified()
    {
        $this->assertTrue(StaffTransferStatus::PENDING->canBeModified());
        $this->assertTrue(StaffTransferStatus::APPROVED->canBeModified());
        $this->assertFalse(StaffTransferStatus::REJECTED->canBeModified());
        $this->assertFalse(StaffTransferStatus::CANCELLED->canBeModified());
        $this->assertFalse(StaffTransferStatus::COMPLETED->canBeModified());
    }
}