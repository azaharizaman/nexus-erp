<?php

declare(strict_types=1);

namespace Nexus\Sequencing\Tests\Unit\Traits;

use Nexus\Sequencing\Traits\HasSequence;
use Nexus\Sequencing\Actions\GenerateSerialNumberAction;
use Nexus\Sequencing\Models\Sequence;
use Illuminate\Database\Eloquent\Model;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * HasSequence Trait Tests
 * 
 * Tests for the Phase 2.2 HasSequence trait functionality including
 * automatic sequence generation and configurable resolution strategies.
 */
class HasSequenceTest extends TestCase
{
    private GenerateSerialNumberAction|MockObject $generateAction;

    protected function setUp(): void
    {
        // Mock the generate action since we're testing the trait logic
        $this->generateAction = $this->createMock(GenerateSerialNumberAction::class);
        
        // Bind the mock in the container for testing
        app()->instance(GenerateSerialNumberAction::class, $this->generateAction);
    }

    public function test_default_sequence_generation_on_creation(): void
    {
        $this->generateAction
            ->expects($this->once())
            ->method('handle')
            ->with('tenant-123', 'invoices', [])
            ->willReturn('INV-2024-001');

        $invoice = new TestInvoiceModel();
        $invoice->tenant_id = 'tenant-123';
        
        // Simulate the creating event
        $invoice->fireModelEvent('creating');

        $this->assertEquals('INV-2024-001', $invoice->invoice_number);
        $this->assertFalse($invoice->wasChanged('invoice_number')); // Should not be marked as dirty
    }

    public function test_custom_sequence_field_configuration(): void
    {
        $this->generateAction
            ->expects($this->once())
            ->method('handle')
            ->with('tenant-456', 'purchase-orders', [])
            ->willReturn('PO-2024-0012');

        $po = new TestPurchaseOrderModel();
        $po->tenant_id = 'tenant-456';
        
        $po->fireModelEvent('creating');

        $this->assertEquals('PO-2024-0012', $po->po_number);
    }

    public function test_custom_sequence_name_resolution(): void
    {
        $this->generateAction
            ->expects($this->once())
            ->method('handle')
            ->with('tenant-789', 'department-invoices', [])
            ->willReturn('DEPT-INV-001');

        $invoice = new TestDepartmentInvoiceModel();
        $invoice->tenant_id = 'tenant-789';
        $invoice->department = 'sales';
        
        $invoice->fireModelEvent('creating');

        $this->assertEquals('DEPT-INV-001', $invoice->invoice_number);
    }

    public function test_custom_tenant_id_resolution(): void
    {
        $this->generateAction
            ->expects($this->once())
            ->method('handle')
            ->with('org-abc', 'quotes', [])
            ->willReturn('QT-ABC-001');

        $quote = new TestQuoteModel();
        $quote->organization_id = 'org-abc';
        
        $quote->fireModelEvent('creating');

        $this->assertEquals('QT-ABC-001', $quote->quote_number);
    }

    public function test_context_injection(): void
    {
        $expectedContext = [
            'department_code' => 'SALES',
            'region' => 'US-WEST',
        ];

        $this->generateAction
            ->expects($this->once())
            ->method('handle')
            ->with('tenant-999', 'sales-orders', $expectedContext)
            ->willReturn('SO-SALES-US-001');

        $order = new TestSalesOrderModel();
        $order->tenant_id = 'tenant-999';
        $order->department_code = 'SALES';
        $order->region = 'US-WEST';
        
        $order->fireModelEvent('creating');

        $this->assertEquals('SO-SALES-US-001', $order->order_number);
    }

    public function test_silent_error_handling(): void
    {
        // Simulate an exception during generation
        $this->generateAction
            ->expects($this->once())
            ->method('handle')
            ->willThrowException(new \Exception('Sequence generation failed'));

        $invoice = new TestSilentInvoiceModel();
        $invoice->tenant_id = 'tenant-error';
        
        // Should not throw exception in silent mode
        $invoice->fireModelEvent('creating');

        $this->assertNull($invoice->invoice_number);
    }

    public function test_strict_error_handling(): void
    {
        $this->generateAction
            ->expects($this->once())
            ->method('handle')
            ->willThrowException(new \Exception('Sequence generation failed'));

        $invoice = new TestStrictInvoiceModel();
        $invoice->tenant_id = 'tenant-error';
        
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Sequence generation failed');
        
        $invoice->fireModelEvent('creating');
    }

    public function test_skip_when_field_already_has_value(): void
    {
        $this->generateAction
            ->expects($this->never())
            ->method('handle');

        $invoice = new TestInvoiceModel();
        $invoice->tenant_id = 'tenant-123';
        $invoice->invoice_number = 'MANUAL-001'; // Pre-filled
        
        $invoice->fireModelEvent('creating');

        $this->assertEquals('MANUAL-001', $invoice->invoice_number);
    }

    public function test_skip_when_no_tenant_id(): void
    {
        $this->generateAction
            ->expects($this->never())
            ->method('handle');

        $invoice = new TestInvoiceModel();
        // No tenant_id set
        
        $invoice->fireModelEvent('creating');

        $this->assertNull($invoice->invoice_number);
    }
}

/**
 * Test Models for HasSequence trait testing
 */

class TestInvoiceModel extends Model
{
    use HasSequence;

    protected $table = 'invoices';
    public $timestamps = false;

    // Default configuration: sequence field = invoice_number, sequence name = invoices
}

class TestPurchaseOrderModel extends Model
{
    use HasSequence;

    protected $table = 'purchase_orders';
    public $timestamps = false;

    protected function getSequenceField(): string
    {
        return 'po_number';
    }

    protected function getSequenceName(): string
    {
        return 'purchase-orders';
    }
}

class TestDepartmentInvoiceModel extends Model
{
    use HasSequence;

    protected $table = 'invoices';
    public $timestamps = false;

    protected function getSequenceName(): string
    {
        return 'department-invoices';
    }
}

class TestQuoteModel extends Model
{
    use HasSequence;

    protected $table = 'quotes';
    public $timestamps = false;

    protected function getSequenceField(): string
    {
        return 'quote_number';
    }

    protected function getSequenceName(): string
    {
        return 'quotes';
    }

    protected function getTenantId(): ?string
    {
        return $this->organization_id;
    }
}

class TestSalesOrderModel extends Model
{
    use HasSequence;

    protected $table = 'sales_orders';
    public $timestamps = false;

    protected function getSequenceField(): string
    {
        return 'order_number';
    }

    protected function getSequenceName(): string
    {
        return 'sales-orders';
    }

    protected function getSequenceContext(): array
    {
        return [
            'department_code' => $this->department_code,
            'region' => $this->region,
        ];
    }
}

class TestSilentInvoiceModel extends Model
{
    use HasSequence;

    protected $table = 'invoices';
    public $timestamps = false;

    protected function getSequenceFailureMode(): string
    {
        return 'silent';
    }
}

class TestStrictInvoiceModel extends Model
{
    use HasSequence;

    protected $table = 'invoices';
    public $timestamps = false;

    protected function getSequenceFailureMode(): string
    {
        return 'strict';
    }
}