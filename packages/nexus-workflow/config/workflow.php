<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Workflow Storage Driver
    |--------------------------------------------------------------------------
    |
    | Determines how workflow definitions and state are stored.
    | Level 1: Uses in-model definitions (no database tables needed)
    | Level 2+: Will use database for workflow definitions
    |
    | Supported: 'memory' (Level 1), 'database' (Level 2+)
    |
    */
    'storage' => env('WORKFLOW_STORAGE', 'memory'),

    /*
    |--------------------------------------------------------------------------
    | Default Workflow State Column
    |--------------------------------------------------------------------------
    |
    | The default database column name used to store the workflow state
    | on models using the HasWorkflow trait. Can be overridden per model.
    |
    */
    'state_column' => 'workflow_state',

    /*
    |--------------------------------------------------------------------------
    | Nexus Package Integrations
    |--------------------------------------------------------------------------
    |
    | Auto-detect and integrate with other Nexus packages when available.
    | These will be used automatically if the packages are installed.
    |
    */
    'integrations' => [
        'tenancy' => true,       // nexus-tenancy for multi-tenant isolation
        'audit_log' => true,     // nexus-audit-log for enhanced audit trails
        'notification' => true,  // nexus-notification for alerts
    ],
];
