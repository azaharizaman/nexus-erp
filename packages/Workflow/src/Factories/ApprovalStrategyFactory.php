<?php

declare(strict_types=1);

namespace Nexus\Workflow\Factories;

use InvalidArgumentException;
use Nexus\Workflow\Contracts\ApprovalStrategyContract;
use Nexus\Workflow\Models\ApproverGroup;
use Nexus\Workflow\Strategies\AnyApprovalStrategy;
use Nexus\Workflow\Strategies\ParallelApprovalStrategy;
use Nexus\Workflow\Strategies\QuorumApprovalStrategy;
use Nexus\Workflow\Strategies\SequentialApprovalStrategy;
use Nexus\Workflow\Strategies\WeightedApprovalStrategy;

/**
 * Approval Strategy Factory
 * 
 * Creates the appropriate approval strategy instance based on
 * the approver group's configuration.
 * 
 * @package Nexus\Workflow\Factories
 */
class ApprovalStrategyFactory
{
    /**
     * Strategy class mapping.
     */
    protected static array $strategies = [
        ApproverGroup::STRATEGY_SEQUENTIAL => SequentialApprovalStrategy::class,
        ApproverGroup::STRATEGY_PARALLEL => ParallelApprovalStrategy::class,
        ApproverGroup::STRATEGY_QUORUM => QuorumApprovalStrategy::class,
        ApproverGroup::STRATEGY_ANY => AnyApprovalStrategy::class,
        ApproverGroup::STRATEGY_WEIGHTED => WeightedApprovalStrategy::class,
    ];

    /**
     * Create strategy instance from approver group.
     * 
     * @param ApproverGroup $group Approver group
     * @return ApprovalStrategyContract
     * @throws InvalidArgumentException
     */
    public static function make(ApproverGroup $group): ApprovalStrategyContract
    {
        return static::makeFromStrategy($group->strategy);
    }

    /**
     * Create strategy instance from strategy name.
     * 
     * @param string $strategy Strategy name
     * @return ApprovalStrategyContract
     * @throws InvalidArgumentException
     */
    public static function makeFromStrategy(string $strategy): ApprovalStrategyContract
    {
        if (!isset(static::$strategies[$strategy])) {
            throw new InvalidArgumentException("Unknown approval strategy: {$strategy}");
        }

        $class = static::$strategies[$strategy];

        return new $class();
    }

    /**
     * Get all available strategy names.
     * 
     * @return array
     */
    public static function getAvailableStrategies(): array
    {
        return array_keys(static::$strategies);
    }

    /**
     * Register a custom strategy.
     * 
     * @param string $name Strategy name
     * @param string $class Strategy class (must implement ApprovalStrategyContract)
     * @return void
     * @throws InvalidArgumentException
     */
    public static function register(string $name, string $class): void
    {
        if (!class_exists($class)) {
            throw new InvalidArgumentException("Strategy class does not exist: {$class}");
        }

        if (!in_array(ApprovalStrategyContract::class, class_implements($class))) {
            throw new InvalidArgumentException(
                "Strategy class must implement ApprovalStrategyContract: {$class}"
            );
        }

        static::$strategies[$name] = $class;
    }
}
