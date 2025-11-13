<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Tenant Model
    |--------------------------------------------------------------------------
    |
    | The model used for tenant operations. You can extend this model
    | to add custom functionality or override the default behavior.
    |
    */
    'tenant_model' => \Nexus\TenancyManagement\Models\Tenant::class,

    /*
    |--------------------------------------------------------------------------
    | Tenant Column Name
    |--------------------------------------------------------------------------
    |
    | The column name used to identify the tenant in your database tables.
    | This should match the foreign key column in your tenant-aware models.
    |
    */
    'tenant_column' => 'tenant_id',

    /*
    |--------------------------------------------------------------------------
    | Impersonation Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for tenant impersonation functionality, including
    | cache TTL and allowed roles.
    |
    */
    'impersonation' => [
        'enabled' => true,
        'cache_ttl' => 3600, // 1 hour in seconds
        'cache_prefix' => 'tenant:impersonation:',
    ],

    /*
    |--------------------------------------------------------------------------
    | Tenant Caching
    |--------------------------------------------------------------------------
    |
    | Enable caching for frequently accessed tenant data to improve
    | performance in high-traffic environments.
    |
    */
    'caching' => [
        'enabled' => true,
        'ttl' => 3600, // 1 hour in seconds
        'prefix' => 'tenant:',
    ],
];
