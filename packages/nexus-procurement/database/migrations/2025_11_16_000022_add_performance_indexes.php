<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Performance indexes for purchase requisitions
        Schema::table('purchase_requisitions', function (Blueprint $table) {
            $table->index(['tenant_id', 'status'], 'pr_tenant_status_idx');
            $table->index(['tenant_id', 'requester_id'], 'pr_tenant_requester_idx');
            $table->index(['tenant_id', 'department_id'], 'pr_tenant_department_idx');
            $table->index(['tenant_id', 'created_at'], 'pr_tenant_created_at_idx');
            $table->index(['status', 'created_at'], 'pr_status_created_at_idx');
        });

        // Performance indexes for purchase orders
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->index(['tenant_id', 'status'], 'po_tenant_status_idx');
            $table->index(['tenant_id', 'vendor_id'], 'po_tenant_vendor_idx');
            $table->index(['tenant_id', 'requisition_id'], 'po_tenant_requisition_idx');
            $table->index(['tenant_id', 'created_at'], 'po_tenant_created_at_idx');
            $table->index(['status', 'order_date'], 'po_status_order_date_idx');
            $table->index(['vendor_id', 'status'], 'po_vendor_status_idx');
        });

        // Performance indexes for goods receipt notes
        Schema::table('goods_receipt_notes', function (Blueprint $table) {
            $table->index(['tenant_id', 'purchase_order_id'], 'grn_tenant_po_idx');
            $table->index(['tenant_id', 'received_at'], 'grn_tenant_received_at_idx');
            $table->index(['purchase_order_id', 'created_at'], 'grn_po_created_at_idx');
        });

        // Performance indexes for vendor invoices
        Schema::table('vendor_invoices', function (Blueprint $table) {
            $table->index(['tenant_id', 'vendor_id'], 'inv_tenant_vendor_idx');
            $table->index(['tenant_id', 'purchase_order_id'], 'inv_tenant_po_idx');
            $table->index(['tenant_id', 'status'], 'inv_tenant_status_idx');
            $table->index(['tenant_id', 'payment_status'], 'inv_tenant_payment_status_idx');
            $table->index(['tenant_id', 'due_date'], 'inv_tenant_due_date_idx');
            $table->index(['vendor_id', 'payment_status'], 'inv_vendor_payment_status_idx');
            $table->index(['purchase_order_id', 'invoice_number'], 'inv_po_number_idx');
        });

        // Performance indexes for vendors
        Schema::table('vendors', function (Blueprint $table) {
            $table->index(['tenant_id', 'status'], 'vendor_tenant_status_idx');
            $table->index(['tenant_id', 'vendor_category'], 'vendor_tenant_category_idx');
            $table->index(['tenant_id', 'name'], 'vendor_tenant_name_idx');
        });

        // Performance indexes for RFQ
        Schema::table('request_for_quotations', function (Blueprint $table) {
            $table->index(['tenant_id', 'status'], 'rfq_tenant_status_idx');
            $table->index(['tenant_id', 'requisition_id'], 'rfq_tenant_requisition_idx');
            $table->index(['tenant_id', 'created_at'], 'rfq_tenant_created_at_idx');
        });

        // Performance indexes for vendor quotes
        Schema::table('vendor_quotes', function (Blueprint $table) {
            $table->index(['rfq_id', 'vendor_id'], 'quote_rfq_vendor_idx');
            $table->index(['rfq_id', 'status'], 'quote_rfq_status_idx');
            $table->index(['vendor_id', 'created_at'], 'quote_vendor_created_at_idx');
        });

        // Performance indexes for blanket POs
        Schema::table('blanket_purchase_orders', function (Blueprint $table) {
            $table->index(['tenant_id', 'vendor_id'], 'bpo_tenant_vendor_idx');
            $table->index(['tenant_id', 'status'], 'bpo_tenant_status_idx');
            $table->index(['tenant_id', 'expiry_date'], 'bpo_tenant_expiry_idx');
        });

        // Performance indexes for contracts
        Schema::table('procurement_contracts', function (Blueprint $table) {
            $table->index(['tenant_id', 'vendor_id'], 'contract_tenant_vendor_idx');
            $table->index(['tenant_id', 'status'], 'contract_tenant_status_idx');
            $table->index(['tenant_id', 'end_date'], 'contract_tenant_end_date_idx');
            $table->index(['vendor_id', 'status'], 'contract_vendor_status_idx');
        });

        // Performance indexes for vendor users
        Schema::table('vendor_users', function (Blueprint $table) {
            $table->index(['tenant_id', 'vendor_id'], 'vuser_tenant_vendor_idx');
            $table->index(['tenant_id', 'is_active'], 'vuser_tenant_active_idx');
            $table->index(['vendor_id', 'is_primary_contact'], 'vuser_vendor_primary_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop all performance indexes
        Schema::table('purchase_requisitions', function (Blueprint $table) {
            $table->dropIndex('pr_tenant_status_idx');
            $table->dropIndex('pr_tenant_requester_idx');
            $table->dropIndex('pr_tenant_department_idx');
            $table->dropIndex('pr_tenant_created_at_idx');
            $table->dropIndex('pr_status_created_at_idx');
        });

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropIndex('po_tenant_status_idx');
            $table->dropIndex('po_tenant_vendor_idx');
            $table->dropIndex('po_tenant_requisition_idx');
            $table->dropIndex('po_tenant_created_at_idx');
            $table->dropIndex('po_status_order_date_idx');
            $table->dropIndex('po_vendor_status_idx');
        });

        Schema::table('goods_receipt_notes', function (Blueprint $table) {
            $table->dropIndex('grn_tenant_po_idx');
            $table->dropIndex('grn_tenant_received_at_idx');
            $table->dropIndex('grn_po_created_at_idx');
        });

        Schema::table('vendor_invoices', function (Blueprint $table) {
            $table->dropIndex('inv_tenant_vendor_idx');
            $table->dropIndex('inv_tenant_po_idx');
            $table->dropIndex('inv_tenant_status_idx');
            $table->dropIndex('inv_tenant_payment_status_idx');
            $table->dropIndex('inv_tenant_due_date_idx');
            $table->dropIndex('inv_vendor_payment_status_idx');
            $table->dropIndex('inv_po_number_idx');
        });

        Schema::table('vendors', function (Blueprint $table) {
            $table->dropIndex('vendor_tenant_status_idx');
            $table->dropIndex('vendor_tenant_category_idx');
            $table->dropIndex('vendor_tenant_name_idx');
        });

        Schema::table('request_for_quotations', function (Blueprint $table) {
            $table->dropIndex('rfq_tenant_status_idx');
            $table->dropIndex('rfq_tenant_requisition_idx');
            $table->dropIndex('rfq_tenant_created_at_idx');
        });

        Schema::table('vendor_quotes', function (Blueprint $table) {
            $table->dropIndex('quote_rfq_vendor_idx');
            $table->dropIndex('quote_rfq_status_idx');
            $table->dropIndex('quote_vendor_created_at_idx');
        });

        Schema::table('blanket_purchase_orders', function (Blueprint $table) {
            $table->dropIndex('bpo_tenant_vendor_idx');
            $table->dropIndex('bpo_tenant_status_idx');
            $table->dropIndex('bpo_tenant_expiry_idx');
        });

        Schema::table('procurement_contracts', function (Blueprint $table) {
            $table->dropIndex('contract_tenant_vendor_idx');
            $table->dropIndex('contract_tenant_status_idx');
            $table->dropIndex('contract_tenant_end_date_idx');
            $table->dropIndex('contract_vendor_status_idx');
        });

        Schema::table('vendor_users', function (Blueprint $table) {
            $table->dropIndex('vuser_tenant_vendor_idx');
            $table->dropIndex('vuser_tenant_active_idx');
            $table->dropIndex('vuser_vendor_primary_idx');
        });
    }
};