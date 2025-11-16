<?php

declare(strict_types=1);

namespace Nexus\Crm\Contracts;

use Nexus\Crm\Models\CrmEntity;

/**
 * Integration Contract
 *
 * Defines the interface for external service integrations.
 */
interface IntegrationContract
{
    /**
     * Execute the integration.
     *
     * @param CrmEntity $entity The CRM entity
     * @param array $config Integration configuration
     * @param array $context Execution context
     */
    public function execute(CrmEntity $entity, array $config, array $context = []): void;

    /**
     * Compensate for a failed integration (for rollback).
     *
     * @param CrmEntity $entity The CRM entity
     * @param array $config Integration configuration
     * @param array $context Execution context
     */
    public function compensate(CrmEntity $entity, array $config, array $context = []): void;
}