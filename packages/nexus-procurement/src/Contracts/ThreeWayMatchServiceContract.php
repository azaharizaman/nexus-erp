<?php

namespace Nexus\Procurement\Contracts;

use Nexus\Procurement\Models\ThreeWayMatchResult;

interface ThreeWayMatchServiceContract
{
    /**
     * Perform 3-way matching for a purchase order, goods receipt, and vendor invoice
     *
     * @param string $purchaseOrderId
     * @param string $goodsReceiptId
     * @param string $vendorInvoiceId
     * @return ThreeWayMatchResult
     */
    public function performMatch(string $purchaseOrderId, string $goodsReceiptId, string $vendorInvoiceId): ThreeWayMatchResult;

    /**
     * Perform 3-way matching for a vendor invoice (auto-find PO and GRN)
     *
     * @param string $vendorInvoiceId
     * @return ThreeWayMatchResult
     */
    public function matchInvoice(string $vendorInvoiceId): ThreeWayMatchResult;

    /**
     * Check if documents match within tolerance rules
     *
     * @param array $poData
     * @param array $grnData
     * @param array $invoiceData
     * @return array Match results with variances
     */
    public function checkTolerance(array $poData, array $grnData, array $invoiceData): array;

    /**
     * Get tolerance rules for a tenant
     *
     * @param string $tenantId
     * @return array
     */
    public function getToleranceRules(string $tenantId): array;

    /**
     * Update tolerance rules for a tenant
     *
     * @param string $tenantId
     * @param array $rules
     * @return bool
     */
    public function updateToleranceRules(string $tenantId, array $rules): bool;

    /**
     * Get match results for a purchase order
     *
     * @param string $purchaseOrderId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getMatchesForPurchaseOrder(string $purchaseOrderId): \Illuminate\Database\Eloquent\Collection;

    /**
     * Get match results for a vendor invoice
     *
     * @param string $vendorInvoiceId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getMatchesForInvoice(string $vendorInvoiceId): \Illuminate\Database\Eloquent\Collection;

    /**
     * Approve a match result
     *
     * @param string $matchResultId
     * @param string $approverId
     * @param string $comments
     * @return ThreeWayMatchResult
     */
    public function approveMatch(string $matchResultId, string $approverId, string $comments = null): ThreeWayMatchResult;

    /**
     * Reject a match result
     *
     * @param string $matchResultId
     * @param string $approverId
     * @param string $comments
     * @return ThreeWayMatchResult
     */
    public function rejectMatch(string $matchResultId, string $approverId, string $comments = null): ThreeWayMatchResult;

    /**
     * Get match statistics for a tenant
     *
     * @param string $tenantId
     * @param array $dateRange
     * @return array
     */
    public function getMatchStatistics(string $tenantId, array $dateRange = []): array;

    /**
     * Validate match data
     *
     * @param array $data
     * @return array Validation errors
     */
    public function validateMatchData(array $data): array;
}