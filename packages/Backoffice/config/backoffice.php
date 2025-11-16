<?php

return [
    "table_prefix" => "backoffice_",
    "models" => [
        "company" => "\\Nexus\\Backoffice\\Models\\Company",
        "office" => "\\Nexus\\Backoffice\\Models\\Office",
        "office_type" => "\\Nexus\\Backoffice\\Models\\OfficeType",
        "department" => "\\Nexus\\Backoffice\\Models\\Department",
        "staff" => "\\Nexus\\Backoffice\\Models\\Staff",
        "position" => "\\Nexus\\Backoffice\\Models\\Position",
        "staff_transfer" => "\\Nexus\\Backoffice\\Models\\StaffTransfer"
    ],
    "business_rules" => [
        "max_hierarchy_depth" => 10,
        "allow_lateral_transfers" => true,
        "require_transfer_approval" => true,
        "auto_process_transfers" => false,
        "max_reporting_levels" => 8
    ],
    "cache" => [
        "enabled" => true,
        "ttl" => 3600,
        "key_prefix" => "backoffice_"
    ]
];
