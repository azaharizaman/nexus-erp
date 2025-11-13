<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Table Prefix
    |--------------------------------------------------------------------------
    |
    | The table prefix for all backoffice tables. Change this if you want
    | to use a different prefix for the package tables.
    |
    */
    'table_prefix' => 'backoffice_',

    /*
    |--------------------------------------------------------------------------
    | Model Configuration
    |--------------------------------------------------------------------------
    |
    | Configure which models to use for each entity. This allows you to
    | extend the package models with your own implementations.
    |
    */
    'models' => [
        'company' => \Nexus\BackofficeManagement\Models\Company::class,
        'office' => \Nexus\BackofficeManagement\Models\Office::class,
        'office_type' => \Nexus\BackofficeManagement\Models\OfficeType::class,
        'department' => \Nexus\BackofficeManagement\Models\Department::class,
        'staff' => \Nexus\BackofficeManagement\Models\Staff::class,
        'unit' => \Nexus\BackofficeManagement\Models\Unit::class,
        'unit_group' => \Nexus\BackofficeManagement\Models\UnitGroup::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Route Configuration
    |--------------------------------------------------------------------------
    |
    | Configure routing options if you plan to use the package's routes.
    |
    */
    'routes' => [
        'enabled' => false,
        'prefix' => 'backoffice',
        'middleware' => ['web'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Validation Rules
    |--------------------------------------------------------------------------
    |
    | Default validation rules for model fields.
    |
    */
    'validation' => [
        'company' => [
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50|unique:backoffice_companies,code',
            'description' => 'nullable|string|max:1000',
            'parent_company_id' => 'nullable|exists:backoffice_companies,id',
            'is_active' => 'boolean',
        ],
        'office' => [
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50|unique:backoffice_offices,code',
            'description' => 'nullable|string|max:1000',
            'company_id' => 'required|exists:backoffice_companies,id',
            'parent_office_id' => 'nullable|exists:backoffice_offices,id',
            'address' => 'nullable|string|max:500',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'is_active' => 'boolean',
        ],
        'department' => [
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50|unique:backoffice_departments,code',
            'description' => 'nullable|string|max:1000',
            'company_id' => 'required|exists:backoffice_companies,id',
            'parent_department_id' => 'nullable|exists:backoffice_departments,id',
            'is_active' => 'boolean',
        ],
        'staff' => [
            'employee_id' => 'required|string|max:50|unique:backoffice_staff,employee_id',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255|unique:backoffice_staff,email',
            'phone' => 'nullable|string|max:50',
            'office_id' => 'nullable|exists:backoffice_offices,id',
            'department_id' => 'nullable|exists:backoffice_departments,id',
            'position' => 'nullable|string|max:255',
            'hire_date' => 'nullable|date',
            'is_active' => 'boolean',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Soft Deletes
    |--------------------------------------------------------------------------
    |
    | Configure soft delete behavior for each model.
    |
    */
    'soft_deletes' => [
        'company' => true,
        'office' => true,
        'office_type' => true,
        'department' => true,
        'staff' => true,
        'unit' => true,
        'unit_group' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Office Types
    |--------------------------------------------------------------------------
    |
    | Default office types to be seeded when the package is installed.
    |
    */
    'default_office_types' => [
        ['name' => 'Head Office', 'code' => 'HO', 'description' => 'Main headquarters'],
        ['name' => 'Branch Office', 'code' => 'BO', 'description' => 'Regional branch office'],
        ['name' => 'Sales Office', 'code' => 'SO', 'description' => 'Sales and marketing office'],
        ['name' => 'Service Center', 'code' => 'SC', 'description' => 'Customer service center'],
        ['name' => 'Warehouse', 'code' => 'WH', 'description' => 'Storage and distribution facility'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Hierarchy Settings
    |--------------------------------------------------------------------------
    |
    | Configure hierarchy behavior.
    |
    */
    'hierarchy' => [
        'max_depth' => 10, // Maximum depth for hierarchical structures
        'prevent_circular_references' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Configure caching for hierarchy queries and other expensive operations.
    |
    */
    'cache' => [
        'enabled' => true,
        'ttl' => 3600, // Cache TTL in seconds
        'key_prefix' => 'backoffice_',
    ],

    /*
    |--------------------------------------------------------------------------
    | Event Configuration
    |--------------------------------------------------------------------------
    |
    | Configure which events should be fired by the package.
    |
    */
    'events' => [
        'company_created' => true,
        'company_updated' => true,
        'company_deleted' => true,
        'office_created' => true,
        'office_updated' => true,
        'office_deleted' => true,
        'department_created' => true,
        'department_updated' => true,
        'department_deleted' => true,
        'staff_created' => true,
        'staff_updated' => true,
        'staff_deleted' => true,
    ],
];