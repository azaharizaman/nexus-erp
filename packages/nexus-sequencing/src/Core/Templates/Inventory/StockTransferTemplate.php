<?php

declare(strict_types=1);

namespace Nexus\Sequencing\Core\Templates\Inventory;

use Nexus\Sequencing\Core\Templates\AbstractPatternTemplate;

/**
 * Stock Transfer Pattern Template
 * 
 * Stock transfer document numbering with location and priority support.
 * 
 * Pattern: STK-{?LOCATION_FROM?{LOCATION_FROM}TO{LOCATION_TO}:}{MONTH}{DAY}-{COUNTER:3}
 * Example: STK-WHTORET0115-001
 * 
 * @package Nexus\Sequencing\Core\Templates\Inventory
 */
class StockTransferTemplate extends AbstractPatternTemplate
{
    public function getId(): string
    {
        return 'inventory.stock_transfer.location';
    }

    public function getName(): string
    {
        return 'Location Stock Transfers';
    }

    public function getDescription(): string
    {
        return 'Stock transfer numbering showing source and destination locations with daily sequential numbering.';
    }

    public function getBasePattern(): string
    {
        return 'STK-{?LOCATION_FROM?{LOCATION_FROM}TO{LOCATION_TO}:}{MONTH}{DAY}-{COUNTER:3}';
    }

    public function getRequiredContext(): array
    {
        return [];
    }

    public function getOptionalContext(): array
    {
        return [
            'location_from' => 'Source location code (e.g., WH, STORE, RET)',
            'location_to' => 'Destination location code',
            'warehouse_from' => 'Alternative source location key',
            'warehouse_to' => 'Alternative destination location key',
        ];
    }

    public function getExampleContext(): array
    {
        return [
            'location_from' => 'WH',
            'location_to' => 'RET',
        ];
    }

    public function getCategory(): string
    {
        return 'Inventory';
    }

    public function getTags(): array
    {
        return ['stock_transfer', 'inventory', 'warehouse', 'location', 'movement'];
    }
}