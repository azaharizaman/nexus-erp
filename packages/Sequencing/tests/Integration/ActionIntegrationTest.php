<?php

declare(strict_types=1);

namespace Tests\Integration;

use Nexus\Sequencing\Actions\GenerateSerialNumberAction;
use Nexus\Sequencing\Core\Services\GenerationService;
use Nexus\Sequencing\Core\Contracts\CounterRepositoryInterface;
use Nexus\Sequencing\Core\Contracts\PatternEvaluatorInterface;
use Nexus\Sequencing\Core\Contracts\ResetStrategyInterface;
use Nexus\Sequencing\Core\Engine\RegexPatternEvaluator;
use Nexus\Sequencing\Core\Services\DefaultResetStrategy;
use Nexus\Sequencing\Adapters\Laravel\EloquentCounterRepository;
use Nexus\Sequencing\Models\Sequence;
use Nexus\Sequencing\Enums\ResetPeriod;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Integration Test for Core/Adapter separation
 * 
 * Tests that our refactored action correctly uses Core services
 * while maintaining backward compatibility.
 */
class ActionIntegrationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that GenerateSerialNumberAction uses Core services
     */
    public function test_action_uses_core_services(): void
    {
        // Create a test sequence in the database
        $sequence = Sequence::create([
            'tenant_id' => 'test-tenant',
            'sequence_name' => 'PO',
            'pattern' => 'PO-{YEAR}-{COUNTER:4}',
            'reset_period' => ResetPeriod::YEARLY,
            'padding' => 4,
            'current_value' => 0,
        ]);

        // Manually create the dependencies (for testing without full Laravel DI)
        $counterRepository = new EloquentCounterRepository();
        $patternEvaluator = new RegexPatternEvaluator();
        $resetStrategy = new DefaultResetStrategy();
        
        $generationService = new GenerationService(
            $counterRepository,
            $patternEvaluator,
            $resetStrategy
        );

        // Create the action
        $action = new GenerateSerialNumberAction($generationService);

        // Execute the action
        $result = $action->handle('test-tenant', 'PO', [
            'tenant_code' => 'TST'
        ]);

        // Verify result matches expected pattern
        $this->assertMatchesRegularExpression('/^PO-\d{4}-\d{4}$/', $result);
        
        // Verify sequence counter was incremented
        $sequence->refresh();
        $this->assertEquals(1, $sequence->current_value);
    }

    /**
     * Test that multiple generations produce sequential numbers
     */
    public function test_sequential_generation(): void
    {
        // Create a test sequence
        Sequence::create([
            'tenant_id' => 'test-tenant',
            'sequence_name' => 'INV',
            'pattern' => 'INV-{COUNTER:3}',
            'reset_period' => ResetPeriod::NEVER,
            'padding' => 3,
            'current_value' => 0,
        ]);

        // Create services
        $counterRepository = new EloquentCounterRepository();
        $patternEvaluator = new RegexPatternEvaluator();
        $resetStrategy = new DefaultResetStrategy();
        
        $generationService = new GenerationService(
            $counterRepository,
            $patternEvaluator,
            $resetStrategy
        );

        $action = new GenerateSerialNumberAction($generationService);

        // Generate multiple numbers
        $numbers = [];
        for ($i = 0; $i < 5; $i++) {
            $numbers[] = $action->handle('test-tenant', 'INV');
        }

        // Verify they are sequential
        $this->assertEquals([
            'INV-001',
            'INV-002', 
            'INV-003',
            'INV-004',
            'INV-005',
        ], $numbers);
    }
}