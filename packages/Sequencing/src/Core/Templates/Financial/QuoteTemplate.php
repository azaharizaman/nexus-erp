<?php

declare(strict_types=1);

namespace Nexus\Sequencing\Core\Templates\Financial;

use Nexus\Sequencing\Core\Templates\AbstractPatternTemplate;

/**
 * Quote Pattern Template
 * 
 * Quote/proposal numbering with customer tier and quarterly organization.
 * 
 * Pattern: QTE-{QUARTER:QTR}-{?CUSTOMER_TIER=VIP?VIP-:}{COUNTER:4}
 * Example: QTE-QTR1-VIP-0001 or QTE-QTR1-0001
 * 
 * @package Nexus\Sequencing\Core\Templates\Financial
 */
class QuoteTemplate extends AbstractPatternTemplate
{
    public function getId(): string
    {
        return 'financial.quote.tiered';
    }

    public function getName(): string
    {
        return 'Customer Quote Numbers';
    }

    public function getDescription(): string
    {
        return 'Quote numbering organized by quarter with VIP customer highlighting and sequential counters.';
    }

    public function getBasePattern(): string
    {
        return 'QTE-{QUARTER:QTR}-{?CUSTOMER_TIER=VIP?VIP-:}{COUNTER:4}';
    }

    public function getRequiredContext(): array
    {
        return [];
    }

    public function getOptionalContext(): array
    {
        return [
            'customer_tier' => 'Customer classification (VIP, PREMIUM, STANDARD)',
            'tier' => 'Alternative tier key',
        ];
    }

    public function getExampleContext(): array
    {
        return [
            'customer_tier' => 'VIP',
        ];
    }

    public function getCategory(): string
    {
        return 'Financial';
    }

    public function getTags(): array
    {
        return ['quote', 'proposal', 'sales', 'customer', 'tier', 'quarter'];
    }
}