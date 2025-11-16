<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Workflow Storage Driver (Phase 1)
    |--------------------------------------------------------------------------
    |
    | Determines how workflow definitions and state are stored.
    | Level 1: Uses in-model definitions (no database tables needed)
    | Level 2+: Uses database for workflow definitions
    |
    | Supported: 'memory' (Level 1), 'database' (Level 2+)
    |
    */
    'storage' => env('WORKFLOW_STORAGE', 'memory'),

    /*
    |--------------------------------------------------------------------------
    | Default Workflow State Column (Phase 1)
    |--------------------------------------------------------------------------
    |
    | The default database column name used to store the workflow state
    | on models using the HasWorkflow trait. Can be overridden per model.
    |
    */
    'state_column' => 'workflow_state',

    /*
    |--------------------------------------------------------------------------
    | Default Workflow Engine (Phase 2)
    |--------------------------------------------------------------------------
    |
    | This option controls the default workflow engine that will be used by
    | the Workflow package. The 'database' engine loads workflow definitions
    | from the database and provides caching for performance.
    |
    */
    'engine' => env('WORKFLOW_ENGINE', 'database'),

    /*
    |--------------------------------------------------------------------------
    | Workflow Definition Cache TTL (Phase 2)
    |--------------------------------------------------------------------------
    |
    | When using the database workflow engine, definitions are cached to
    | improve performance. This value controls how long (in seconds) the
    | cached definitions remain valid before being refreshed.
    |
    | Default: 3600 seconds (1 hour)
    |
    */
    'cache_ttl' => env('WORKFLOW_CACHE_TTL', 3600),

    /*
    |--------------------------------------------------------------------------
    | Database Table Names (Phase 2)
    |--------------------------------------------------------------------------
    |
    | These options allow you to customize the table names used by the
    | Workflow package. This is useful if you need to avoid conflicts
    | with existing tables in your application.
    |
    */
    'tables' => [
        'workflow_definitions' => 'workflow_definitions',
        'workflow_instances' => 'workflow_instances',
        'workflow_transitions' => 'workflow_transitions',
        'approver_groups' => 'approver_groups',
        'approver_group_members' => 'approver_group_members',
        'user_tasks' => 'user_tasks',
    ],

    /*
    |--------------------------------------------------------------------------
    | User Model (Phase 2)
    |--------------------------------------------------------------------------
    |
    | The user model class that will be used for task assignments and
    | approver group members. This should typically be your application's
    | User model.
    |
    */
    'user_model' => env('WORKFLOW_USER_MODEL', 'App\\Models\\User'),

    /*
    |--------------------------------------------------------------------------
    | Approval Strategy Drivers (Phase 2)
    |--------------------------------------------------------------------------
    |
    | This array defines the available approval strategy drivers. You can
    | register custom strategies here by adding them to the array. Each
    | strategy must implement ApprovalStrategyContract.
    |
    */
    'approval_strategies' => [
        'sequential' => \Nexus\Workflow\Strategies\SequentialApprovalStrategy::class,
        'parallel' => \Nexus\Workflow\Strategies\ParallelApprovalStrategy::class,
        'quorum' => \Nexus\Workflow\Strategies\QuorumApprovalStrategy::class,
        'any' => \Nexus\Workflow\Strategies\AnyApprovalStrategy::class,
        'weighted' => \Nexus\Workflow\Strategies\WeightedApprovalStrategy::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Task Priority Configuration (Phase 2)
    |--------------------------------------------------------------------------
    |
    | Default priority values for user tasks. Tasks can be created with
    | custom priorities, but these defaults will be used when no priority
    | is specified.
    |
    */
    'task_priorities' => [
        'low' => 1,
        'normal' => 5,
        'high' => 10,
        'urgent' => 20,
    ],

    /*
    |--------------------------------------------------------------------------
    | Workflow Event Logging (Phase 2)
    |--------------------------------------------------------------------------
    |
    | Enable or disable event logging for workflow operations. When enabled,
    | workflow transitions, task assignments, and approvals will be logged
    | using the configured audit log package.
    |
    */
    'event_logging' => env('WORKFLOW_EVENT_LOGGING', true),

    /*
    |--------------------------------------------------------------------------
    | Auto-Assign Tasks (Phase 2)
    |--------------------------------------------------------------------------
    |
    | When enabled, tasks will be automatically assigned when transitions
    | are applied. When disabled, tasks must be manually assigned using
    | the UserTaskService.
    |
    */
    'auto_assign_tasks' => env('WORKFLOW_AUTO_ASSIGN_TASKS', true),

    /*
    |--------------------------------------------------------------------------
    | Task Due Date Calculation (Phase 2)
    |--------------------------------------------------------------------------
    |
    | Default duration (in days) for calculating task due dates when no
    | specific due date is provided. Set to null to disable automatic
    | due date calculation.
    |
    */
    'default_task_duration_days' => env('WORKFLOW_TASK_DURATION', 7),

    /*
    |--------------------------------------------------------------------------
    | Workflow Validation (Phase 2)
    |--------------------------------------------------------------------------
    |
    | Enable strict validation when importing or creating workflow definitions.
    | When enabled, the system will validate state references, transition
    | names, and guard conditions before saving.
    |
    */
    'strict_validation' => env('WORKFLOW_STRICT_VALIDATION', true),

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
