<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | CRM Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the Nexus CRM package. This file contains settings
    | for CRM functionality, including progressive disclosure levels.
    |
    */

    'enabled' => env('CRM_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Progressive Disclosure Level
    |--------------------------------------------------------------------------
    |
    | Controls which CRM features are available:
    | - 1: Basic CRM (traits only, no database)
    | - 2: Sales Automation (database-driven)
    | - 3: Enterprise CRM (SLA, escalation, delegation)
    |
    */
    'level' => env('CRM_LEVEL', 1),

    /*
    |--------------------------------------------------------------------------
    | Default CRM Configuration
    |--------------------------------------------------------------------------
    |
    | Default configuration for CRM entities when using traits.
    | This can be overridden in individual models.
    |
    */
    'defaults' => [
        'contacts' => [
            'fields' => [
                'first_name' => ['type' => 'string', 'required' => true],
                'last_name' => ['type' => 'string', 'required' => true],
                'email' => ['type' => 'string', 'required' => false],
                'phone' => ['type' => 'string', 'required' => false],
                'company' => ['type' => 'string', 'required' => false],
                'notes' => ['type' => 'text', 'required' => false],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Validation Rules
    |--------------------------------------------------------------------------
    |
    | Default validation rules for CRM operations.
    |
    */
    'validation' => [
        'contact' => [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'company' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ],
    ],
];