<?php

declare(strict_types=1);

namespace Nexus\Sequencing\Core\Templates\Procurement;

use Nexus\Sequencing\Core\Templates\AbstractPatternTemplate;

/**
 * Purchase Order Pattern Template
 * 
 * Purchase order numbering with project support and priority classification.
 * 
 * Pattern: PO-{?PROJECT_CODE?{PROJECT_CODE:SHORT}-:}{?PRIORITY>5?URGENT-:}{YEAR}-{COUNTER:4}
 * Example: PO-ALPHA-URGENT-2025-0001
 * 
 * @package Nexus\Sequencing\Core\Templates\Procurement
 */
class PurchaseOrderTemplate extends AbstractPatternTemplate
{
    public function getId(): string
    {
        return 'procurement.purchase_order.project';
    }

    public function getName(): string
    {
        return 'Project Purchase Orders';
    }

    public function getDescription(): string
    {
        return 'Purchase order numbering with optional project codes and urgent priority flagging for high-value orders.';
    }

    public function getBasePattern(): string
    {
        return 'PO-{?PROJECT_CODE?{PROJECT_CODE:SHORT}-:}{?PRIORITY>5?URGENT-:}{YEAR}-{COUNTER:4}';
    }

    public function getRequiredContext(): array
    {
        return [];
    }

    public function getOptionalContext(): array
    {
        return [
            'project_code' => 'Project identifier for project-specific POs',
            'project_id' => 'Alternative project key',
            'priority' => 'Numeric priority (1-10, >5 considered urgent)',
            'urgency' => 'Alternative priority indicator',
        ];
    }

    public function getExampleContext(): array
    {
        return [
            'project_code' => 'ALPHA_PROJECT',
            'priority' => '8',
        ];
    }

    public function getCategory(): string
    {
        return 'Procurement';
    }

    public function getTags(): array
    {
        return ['purchase_order', 'procurement', 'project', 'priority', 'urgent'];
    }
}