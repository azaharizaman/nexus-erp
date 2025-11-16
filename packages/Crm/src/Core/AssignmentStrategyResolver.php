<?php

declare(strict_types=1);

namespace Nexus\Crm\Core;

use Nexus\Crm\Models\CrmEntity;
use Nexus\Crm\Contracts\AssignmentStrategyContract;

/**
 * Assignment Strategy Resolver
 *
 * Resolves user assignments based on different strategies.
 */
class AssignmentStrategyResolver
{
    /**
     * Available assignment strategies.
     */
    private array $strategies = [
        'manual' => ManualAssignmentStrategy::class,
        'round_robin' => RoundRobinAssignmentStrategy::class,
        'load_balance' => LoadBalanceAssignmentStrategy::class,
        'skill_based' => SkillBasedAssignmentStrategy::class,
    ];

    /**
     * Resolve users for assignment based on strategy.
     */
    public function resolve(string $strategy, CrmEntity $entity, array $config = []): array
    {
        $strategyClass = $this->strategies[$strategy] ?? $this->strategies['manual'];

        $strategyInstance = app($strategyClass);

        if (!$strategyInstance instanceof AssignmentStrategyContract) {
            throw new \InvalidArgumentException("Strategy {$strategy} must implement AssignmentStrategyContract");
        }

        return $strategyInstance->resolve($entity, $config);
    }

    /**
     * Register a custom assignment strategy.
     */
    public function registerStrategy(string $name, string $class): void
    {
        $this->strategies[$name] = $class;
    }
}