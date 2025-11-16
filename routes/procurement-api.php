<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Nexus\Procurement\Http\Controllers\PurchaseRequisitionController;
use Nexus\Procurement\Http\Controllers\PurchaseOrderController;
use Nexus\Procurement\Http\Controllers\GoodsReceiptController;
use Nexus\Procurement\Http\Controllers\VendorInvoiceController;
use Nexus\Procurement\Http\Controllers\RFQController;
use Nexus\Procurement\Http\Controllers\VendorQuoteController;
use Nexus\Procurement\Http\Controllers\BlanketPurchaseOrderController;
use Nexus\Procurement\Http\Controllers\BlanketPOReleaseController;
use Nexus\Procurement\Http\Controllers\ContractController;
use Nexus\Procurement\Http\Controllers\VendorPerformanceController;
use Nexus\Procurement\Http\Controllers\ProcurementAnalyticsController;

/*
|--------------------------------------------------------------------------
| Procurement API Routes
|--------------------------------------------------------------------------
|
| Routes for the procurement management system.
| All routes are prefixed with 'api/procurement' and require authentication.
|
*/

Route::middleware(['auth:sanctum'])->prefix('procurement')->name('procurement.')->group(function () {

    // Purchase Requisitions
    Route::prefix('requisitions')->name('requisitions.')->group(function () {
        Route::get('/', [PurchaseRequisitionController::class, 'index'])->name('index');
        Route::post('/', [PurchaseRequisitionController::class, 'store'])->name('store');
        Route::get('/{requisition}', [PurchaseRequisitionController::class, 'show'])->name('show');
        Route::put('/{requisition}', [PurchaseRequisitionController::class, 'update'])->name('update');
        Route::delete('/{requisition}', [PurchaseRequisitionController::class, 'destroy'])->name('destroy');

        // Approval workflow
        Route::post('/{requisition}/submit', [PurchaseRequisitionController::class, 'submitForApproval'])->name('submit');
        Route::post('/{requisition}/approve', [PurchaseRequisitionController::class, 'approve'])->name('approve');
        Route::post('/{requisition}/reject', [PurchaseRequisitionController::class, 'reject'])->name('reject');
        Route::post('/{requisition}/cancel', [PurchaseRequisitionController::class, 'cancel'])->name('cancel');
        Route::get('/{requisition}/approval-history', [PurchaseRequisitionController::class, 'approvalHistory'])->name('approval-history');
    });

    // Purchase Orders
    Route::prefix('orders')->name('orders.')->group(function () {
        Route::get('/', [PurchaseOrderController::class, 'index'])->name('index');
        Route::post('/create-from-requisition', [PurchaseOrderController::class, 'createFromRequisition'])->name('create-from-requisition');
        Route::get('/{purchaseOrder}', [PurchaseOrderController::class, 'show'])->name('show');
        Route::put('/{purchaseOrder}', [PurchaseOrderController::class, 'update'])->name('update');

        // PO workflow
        Route::post('/{purchaseOrder}/submit', [PurchaseOrderController::class, 'submitForApproval'])->name('submit');
        Route::post('/{purchaseOrder}/approve', [PurchaseOrderController::class, 'approve'])->name('approve');
        Route::post('/{purchaseOrder}/send-to-vendor', [PurchaseOrderController::class, 'sendToVendor'])->name('send-to-vendor');
        Route::post('/{purchaseOrder}/cancel', [PurchaseOrderController::class, 'cancel'])->name('cancel');
        Route::post('/{purchaseOrder}/close', [PurchaseOrderController::class, 'close'])->name('close');
        Route::get('/{purchaseOrder}/receipt-summary', [PurchaseOrderController::class, 'receiptSummary'])->name('receipt-summary');
    });

    // Goods Receipts
    Route::prefix('receipts')->name('receipts.')->group(function () {
        Route::get('/', [GoodsReceiptController::class, 'index'])->name('index');
        Route::post('/create-from-purchase-order', [GoodsReceiptController::class, 'createFromPurchaseOrder'])->name('create-from-purchase-order');
        Route::get('/{goodsReceipt}', [GoodsReceiptController::class, 'show'])->name('show');
        Route::post('/{goodsReceipt}/confirm', [GoodsReceiptController::class, 'confirm'])->name('confirm');
        Route::post('/{goodsReceipt}/reject', [GoodsReceiptController::class, 'reject'])->name('reject');
    });

    // Vendor Invoices
    Route::prefix('invoices')->name('invoices.')->group(function () {
        Route::get('/', [VendorInvoiceController::class, 'index'])->name('index');
        Route::post('/', [VendorInvoiceController::class, 'store'])->name('store');
        Route::get('/{vendorInvoice}', [VendorInvoiceController::class, 'show'])->name('show');

        // Three-way matching
        Route::post('/{vendorInvoice}/match', [VendorInvoiceController::class, 'performThreeWayMatch'])->name('match');
        Route::get('/{vendorInvoice}/match-result', [VendorInvoiceController::class, 'matchResult'])->name('match-result');

        // Payment processing
        Route::post('/{vendorInvoice}/approve-payment', [VendorInvoiceController::class, 'approvePayment'])->name('approve-payment');
        Route::post('/{vendorInvoice}/reject-payment', [VendorInvoiceController::class, 'rejectPayment'])->name('reject-payment');
        Route::post('/{vendorInvoice}/mark-paid', [VendorInvoiceController::class, 'markAsPaid'])->name('mark-paid');
    });

    // Request for Quotations (RFQ)
    Route::prefix('rfqs')->name('rfqs.')->group(function () {
        Route::get('/', [RFQController::class, 'index'])->name('index');
        Route::post('/', [RFQController::class, 'store'])->name('store');
        Route::get('/{rfq}', [RFQController::class, 'show'])->name('show');
        Route::post('/{rfq}/invite-vendors', [RFQController::class, 'inviteVendors'])->name('invite-vendors');
        Route::post('/{rfq}/send-to-vendors', [RFQController::class, 'sendToVendors'])->name('send-to-vendors');
        Route::get('/{rfq}/compare-quotes', [RFQController::class, 'compareQuotes'])->name('compare-quotes');
        Route::post('/{rfq}/select-winner', [RFQController::class, 'selectWinner'])->name('select-winner');
        Route::post('/{rfq}/close', [RFQController::class, 'close'])->name('close');
    });

    // Vendor Quotes
    Route::prefix('quotes')->name('quotes.')->group(function () {
        Route::get('/rfq/{rfq}', [VendorQuoteController::class, 'index'])->name('index');
        Route::post('/rfq/{rfq}', [VendorQuoteController::class, 'store'])->name('store');
        Route::get('/{quote}', [VendorQuoteController::class, 'show'])->name('show');
        Route::put('/{quote}', [VendorQuoteController::class, 'update'])->name('update');
        Route::delete('/{quote}', [VendorQuoteController::class, 'destroy'])->name('destroy');

        // Vendor-specific endpoints
        Route::get('/vendor/{vendor}', [VendorQuoteController::class, 'vendorQuotes'])->name('vendor-quotes');
        Route::get('/vendor/{vendor}/pending-rfqs', [VendorQuoteController::class, 'pendingRFQs'])->name('pending-rfqs');
        Route::get('/vendor/{vendor}/statistics', [VendorQuoteController::class, 'vendorStatistics'])->name('vendor-statistics');
    });

    // RFQ Statistics
    Route::get('/rfq-statistics', [RFQController::class, 'statistics'])->name('rfq-statistics');

    // Blanket Purchase Orders
    Route::prefix('blanket-pos')->name('blanket-pos.')->group(function () {
        Route::get('/', [BlanketPurchaseOrderController::class, 'index'])->name('index');
        Route::post('/', [BlanketPurchaseOrderController::class, 'store'])->name('store');
        Route::get('/{blanketPO}', [BlanketPurchaseOrderController::class, 'show'])->name('show');
        Route::put('/{blanketPO}', [BlanketPurchaseOrderController::class, 'update'])->name('update');
        Route::post('/{blanketPO}/activate', [BlanketPurchaseOrderController::class, 'activate'])->name('activate');
        Route::post('/{blanketPO}/suspend', [BlanketPurchaseOrderController::class, 'suspend'])->name('suspend');
        Route::post('/{blanketPO}/cancel', [BlanketPurchaseOrderController::class, 'cancel'])->name('cancel');
        Route::get('/{blanketPO}/utilization', [BlanketPurchaseOrderController::class, 'utilization'])->name('utilization');
    });

    // Blanket PO Releases
    Route::prefix('blanket-pos/{blanketPO}/releases')->name('blanket-po-releases.')->group(function () {
        Route::get('/', [BlanketPOReleaseController::class, 'index'])->name('index');
        Route::post('/', [BlanketPOReleaseController::class, 'store'])->name('store');
        Route::get('/{release}', [BlanketPOReleaseController::class, 'show'])->name('show');
        Route::post('/{release}/submit', [BlanketPOReleaseController::class, 'submitForApproval'])->name('submit');
        Route::post('/{release}/approve', [BlanketPOReleaseController::class, 'approve'])->name('approve');
        Route::post('/{release}/reject', [BlanketPOReleaseController::class, 'reject'])->name('reject');
        Route::post('/{release}/convert-to-po', [BlanketPOReleaseController::class, 'convertToPO'])->name('convert-to-po');
        Route::post('/{release}/cancel', [BlanketPOReleaseController::class, 'cancel'])->name('cancel');
    });

    // Blanket PO Statistics
    Route::get('/blanket-po-statistics', [BlanketPurchaseOrderController::class, 'statistics'])->name('blanket-po-statistics');
    Route::get('/blanket-po-pending-approvals', [BlanketPOReleaseController::class, 'pendingApprovals'])->name('blanket-po-pending-approvals');

    // Procurement Contracts
    Route::prefix('contracts')->name('contracts.')->group(function () {
        Route::get('/', [ContractController::class, 'index'])->name('index');
        Route::post('/', [ContractController::class, 'store'])->name('store');
        Route::get('/{contract}', [ContractController::class, 'show'])->name('show');
        Route::put('/{contract}', [ContractController::class, 'update'])->name('update');

        // Contract amendments
        Route::post('/{contract}/amend', [ContractController::class, 'amend'])->name('amend');
        Route::get('/{contract}/amendments', [ContractController::class, 'amendments'])->name('amendments');

        // Contract lifecycle
        Route::post('/{contract}/renew', [ContractController::class, 'renew'])->name('renew');
        Route::post('/{contract}/link-po', [ContractController::class, 'linkPO'])->name('link-po');
        Route::get('/{contract}/utilization', [ContractController::class, 'utilization'])->name('utilization');
    });

    // Contract Management
    Route::get('/contracts-due-renewal', [ContractController::class, 'dueForRenewal'])->name('contracts-due-renewal');

    // Vendor Performance
    Route::prefix('vendors/{vendor}/performance')->name('vendor-performance.')->group(function () {
        Route::get('/metrics', [VendorPerformanceController::class, 'metrics'])->name('metrics');
        Route::get('/ranking', [VendorPerformanceController::class, 'ranking'])->name('ranking');
        Route::post('/rate', [VendorPerformanceController::class, 'rateVendor'])->name('rate');
    });

    // Performance Dashboard
    Route::get('/performance-dashboard', [VendorPerformanceController::class, 'dashboard'])->name('performance-dashboard');

    // Procurement Analytics
    Route::prefix('analytics')->name('analytics.')->group(function () {
        Route::get('/dashboard', [ProcurementAnalyticsController::class, 'dashboard'])->name('dashboard');
        Route::get('/spend-analysis', [ProcurementAnalyticsController::class, 'spendAnalysis'])->name('spend-analysis');
        Route::get('/supplier-performance', [ProcurementAnalyticsController::class, 'supplierPerformance'])->name('supplier-performance');
        Route::get('/efficiency-metrics', [ProcurementAnalyticsController::class, 'efficiencyMetrics'])->name('efficiency-metrics');
        Route::get('/compliance-metrics', [ProcurementAnalyticsController::class, 'complianceMetrics'])->name('compliance-metrics');
        Route::get('/trends', [ProcurementAnalyticsController::class, 'trends'])->name('trends');
        Route::get('/category-spend', [ProcurementAnalyticsController::class, 'categorySpend'])->name('category-spend');
        Route::get('/savings-analysis', [ProcurementAnalyticsController::class, 'savingsAnalysis'])->name('savings-analysis');
        Route::get('/export', [ProcurementAnalyticsController::class, 'export'])->name('export');
    });
});