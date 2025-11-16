<?php

declare(strict_types=1);

namespace Nexus\Sequencing\Tests\Unit\Actions;

use Nexus\Sequencing\Actions\PreviewSerialNumberAction;
use Nexus\Sequencing\Contracts\PatternParserContract;
use Nexus\Sequencing\Contracts\SequenceRepositoryContract;
use Nexus\Sequencing\Core\Contracts\ResetStrategyInterface;
use Nexus\Sequencing\Models\Sequence;
use Nexus\Sequencing\Enums\ResetPeriod;
use Nexus\Sequencing\Exceptions\SequenceNotFoundException;
use PHPUnit\Framework\TestCase;
use DateTimeImmutable;

/**
 * Enhanced Preview Serial Number Action Tests
 * 
 * Tests for the Phase 2.2 enhanced preview functionality including
 * remaining count calculations and reset information.
 */
class PreviewSerialNumberActionTest extends TestCase
{
    private PreviewSerialNumberAction $action;
    private SequenceRepositoryContract $repository;
    private PatternParserContract $parser;
    private ResetStrategyInterface $resetStrategy;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(SequenceRepositoryContract::class);
        $this->parser = $this->createMock(PatternParserContract::class);
        $this->resetStrategy = $this->createMock(ResetStrategyInterface::class);

        $this->action = new PreviewSerialNumberAction(
            $this->repository,
            $this->parser,
            $this->resetStrategy
        );
    }

    public function test_basic_preview_without_reset_info(): void
    {
        $sequence = $this->createSequence([
            'current_value' => 10,
            'step_size' => 1,
            'pattern' => 'INV-{YYYY}-{counter:4}',
        ]);

        $this->repository
            ->expects($this->once())
            ->method('find')
            ->with('tenant-123', 'invoices')
            ->willReturn($sequence);

        $this->parser
            ->expects($this->once())
            ->method('preview')
            ->with('INV-{YYYY}-{counter:4}', $this->callback(function ($context) {
                return $context['counter'] === 11 && $context['padding'] === 4;
            }))
            ->willReturn('INV-2024-0011');

        $result = $this->action->handle('tenant-123', 'invoices', [], false);

        $this->assertEquals([
            'preview' => 'INV-2024-0011',
            'current_value' => 10,
            'next_value' => 11,
            'step_size' => 1,
        ], $result);
    }

    public function test_detailed_preview_with_count_based_reset(): void
    {
        $sequence = $this->createSequence([
            'current_value' => 85,
            'step_size' => 1,
            'reset_limit' => 100,
            'reset_period' => ResetPeriod::NEVER,
            'pattern' => 'PO-{counter:4}',
        ]);

        $this->repository
            ->expects($this->once())
            ->method('find')
            ->willReturn($sequence);

        $this->parser
            ->expects($this->once())
            ->method('preview')
            ->willReturn('PO-0086');

        $this->resetStrategy
            ->expects($this->once())
            ->method('shouldReset')
            ->willReturn(false);

        $result = $this->action->handle('tenant-123', 'purchase-orders');

        $this->assertEquals('PO-0086', $result['preview']);
        $this->assertEquals(85, $result['current_value']);
        $this->assertEquals(86, $result['next_value']);
        $this->assertEquals(1, $result['step_size']);
        $this->assertEquals('count', $result['reset_info']['type']);
        $this->assertEquals('never', $result['reset_info']['period']);
        $this->assertEquals(100, $result['reset_info']['limit']);
        $this->assertEquals(15, $result['reset_info']['remaining_count']); // 100 - 85 = 15
        $this->assertNull($result['reset_info']['next_reset_date']);
        $this->assertFalse($result['reset_info']['will_reset_next']);
    }

    public function test_detailed_preview_with_time_based_reset(): void
    {
        $now = new DateTimeImmutable('2024-11-15 10:00:00');
        $nextReset = new DateTimeImmutable('2024-12-01 00:00:00');

        $sequence = $this->createSequence([
            'current_value' => 42,
            'step_size' => 1,
            'reset_limit' => null,
            'reset_period' => ResetPeriod::MONTHLY,
            'updated_at' => $now,
        ]);

        $this->repository
            ->expects($this->once())
            ->method('find')
            ->willReturn($sequence);

        $this->parser
            ->expects($this->once())
            ->method('preview')
            ->willReturn('QT-0043');

        $this->resetStrategy
            ->expects($this->once())
            ->method('calculateNextResetTime')
            ->willReturn($nextReset);

        $this->resetStrategy
            ->expects($this->once())
            ->method('shouldReset')
            ->willReturn(false);

        $result = $this->action->handle('tenant-123', 'quotes');

        $this->assertEquals('time', $result['reset_info']['type']);
        $this->assertEquals('monthly', $result['reset_info']['period']);
        $this->assertNull($result['reset_info']['limit']);
        $this->assertNull($result['reset_info']['remaining_count']);
        $this->assertEquals('2024-12-01T00:00:00+00:00', $result['reset_info']['next_reset_date']);
        $this->assertFalse($result['reset_info']['will_reset_next']);
    }

    public function test_detailed_preview_with_both_reset_types(): void
    {
        $sequence = $this->createSequence([
            'current_value' => 98,
            'step_size' => 2,
            'reset_limit' => 100,
            'reset_period' => ResetPeriod::MONTHLY,
        ]);

        $this->repository
            ->expects($this->once())
            ->method('find')
            ->willReturn($sequence);

        $this->parser
            ->expects($this->once())
            ->method('preview')
            ->willReturn('SO-0100');

        $this->resetStrategy
            ->expects($this->once())
            ->method('shouldReset')
            ->willReturn(true); // Next generation will trigger reset

        $result = $this->action->handle('tenant-123', 'sales-orders');

        $this->assertEquals(100, $result['next_value']); // 98 + 2 = 100
        $this->assertEquals(2, $result['step_size']);
        $this->assertEquals('both', $result['reset_info']['type']);
        $this->assertEquals(2, $result['reset_info']['remaining_count']); // 100 - 98 = 2
        $this->assertTrue($result['reset_info']['will_reset_next']);
    }

    public function test_step_size_greater_than_one(): void
    {
        $sequence = $this->createSequence([
            'current_value' => 10,
            'step_size' => 5,
            'pattern' => 'BATCH-{counter:3}',
        ]);

        $this->repository
            ->expects($this->once())
            ->method('find')
            ->willReturn($sequence);

        $this->parser
            ->expects($this->once())
            ->method('preview')
            ->with('BATCH-{counter:3}', $this->callback(function ($context) {
                return $context['counter'] === 15; // 10 + 5
            }))
            ->willReturn('BATCH-015');

        $result = $this->action->handle('tenant-123', 'batches', [], false);

        $this->assertEquals(10, $result['current_value']);
        $this->assertEquals(15, $result['next_value']);
        $this->assertEquals(5, $result['step_size']);
    }

    public function test_sequence_not_found_throws_exception(): void
    {
        $this->repository
            ->expects($this->once())
            ->method('find')
            ->with('tenant-123', 'nonexistent')
            ->willReturn(null);

        $this->expectException(SequenceNotFoundException::class);

        $this->action->handle('tenant-123', 'nonexistent');
    }

    public function test_custom_context_passed_to_parser(): void
    {
        $sequence = $this->createSequence();

        $this->repository
            ->expects($this->once())
            ->method('find')
            ->willReturn($sequence);

        $customContext = [
            'department_code' => 'IT',
            'prefix' => 'PROJ',
        ];

        $this->parser
            ->expects($this->once())
            ->method('preview')
            ->with(
                $sequence->pattern,
                $this->callback(function ($context) use ($customContext) {
                    return $context['department_code'] === 'IT'
                        && $context['prefix'] === 'PROJ'
                        && isset($context['counter']);
                })
            )
            ->willReturn('PROJ-IT-001');

        $result = $this->action->handle('tenant-123', 'projects', $customContext, false);

        $this->assertEquals('PROJ-IT-001', $result['preview']);
    }

    /**
     * Helper method to create a sequence mock with default values
     */
    private function createSequence(array $attributes = []): Sequence
    {
        $sequence = $this->createMock(Sequence::class);
        
        $defaults = [
            'tenant_id' => 'tenant-123',
            'name' => 'test-sequence',
            'pattern' => 'TEST-{counter:4}',
            'current_value' => 1,
            'step_size' => 1,
            'padding' => 4,
            'reset_period' => ResetPeriod::NEVER,
            'reset_limit' => null,
            'last_reset_at' => null,
            'updated_at' => new DateTimeImmutable(),
        ];

        $values = array_merge($defaults, $attributes);

        foreach ($values as $property => $value) {
            $sequence->{$property} = $value;
        }

        return $sequence;
    }
}