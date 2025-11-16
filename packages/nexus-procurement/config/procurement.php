<?php

return [
    // Approval thresholds (can be overridden per tenant via nexus-settings)
    'approval_thresholds' => [
        'requisition' => [
            'level_1' => 5000,   // Manager approval up to $5K
            'level_2' => 50000,  // Director approval up to $50K
            'level_3' => PHP_INT_MAX, // CFO approval above $50K
        ],
        'purchase_order' => [
            'level_1' => 10000,
            'level_2' => 100000,
            'level_3' => PHP_INT_MAX,
        ],
    ],

    // 3-way match tolerance rules
    'three_way_match' => [
        'price_variance_tolerance' => 5.0,    // Allow 5% price difference
        'quantity_variance_tolerance' => 2.0, // Allow 2% quantity difference
        'total_variance_amount' => 100.00,    // Allow $100 total difference
        'auto_approve_within_tolerance' => true,
        'escalate_on_exceed' => true,
    ],

    // Separation of duties enforcement
    'separation_of_duties' => [
        'enabled' => true,
        'requester_cannot_approve' => true,
        'creator_cannot_receive' => true,
        'receiver_cannot_authorize_payment' => true,
    ],

    // Vendor management
    'vendor' => [
        'require_tax_id' => true,
        'require_bank_account' => true,
        'performance_tracking_enabled' => true,
    ],

    // Blanket PO settings
    'blanket_po' => [
        'enabled' => true,
        'default_validity_days' => 365,
        'utilization_alert_threshold' => 0.8, // Alert at 80% utilization
    ],
];