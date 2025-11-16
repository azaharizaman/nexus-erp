<?php

namespace Nexus\Procurement\Contracts;

use Nexus\Procurement\Models\PurchaseOrder;

interface PurchaseOrderServiceContract
{
    /**
     * Create a purchase order from approved requisition
     *
     * @param string $requisitionId
     * @param array $additionalData
     * @return PurchaseOrder
     */
    public function createFromRequisition(string $requisitionId, array $additionalData = []): PurchaseOrder;

    /**
     * Create a purchase order directly
     *
     * @param array $data
     * @return PurchaseOrder
     */
    public function create(array $data): PurchaseOrder;

    /**
     * Update purchase order
     *
     * @param string $id
     * @param array $data
     * @return PurchaseOrder
     */
    public function update(string $id, array $data): PurchaseOrder;

    /**
     * Approve purchase order
     *
     * @param string $id
     * @param string $approverId
     * @param string $comments
     * @return PurchaseOrder
     */
    public function approve(string $id, string $approverId, string $comments = null): PurchaseOrder;

    /**
     * Reject purchase order
     *
     * @param string $id
     * @param string $approverId
     * @param string $comments
     * @return PurchaseOrder
     */
    public function reject(string $id, string $approverId, string $comments = null): PurchaseOrder;

    /**
     * Send purchase order to vendor
     *
     * @param string $id
     * @return PurchaseOrder
     */
    public function sendToVendor(string $id): PurchaseOrder;

    /**
     * Amend purchase order
     *
     * @param string $id
     * @param array $amendmentData
     * @param string $reason
     * @return PurchaseOrder
     */
    public function amend(string $id, array $amendmentData, string $reason): PurchaseOrder;

    /**
     * Close purchase order
     *
     * @param string $id
     * @param string $reason
     * @return PurchaseOrder
     */
    public function close(string $id, string $reason = null): PurchaseOrder;

    /**
     * Get purchase orders by vendor
     *
     * @param string $vendorId
     * @param array $filters
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getByVendor(string $vendorId, array $filters = []): \Illuminate\Database\Eloquent\Collection;

    /**
     * Get purchase orders by status
     *
     * @param string $tenantId
     * @param string $status
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getByStatus(string $tenantId, string $status): \Illuminate\Database\Eloquent\Collection;

    /**
     * Get overdue purchase orders
     *
     * @param string $tenantId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getOverdue(string $tenantId): \Illuminate\Database\Eloquent\Collection;

    /**
     * Calculate purchase order totals
     *
     * @param string $id
     * @return array
     */
    public function calculateTotals(string $id): array;

    /**
     * Validate purchase order data
     *
     * @param array $data
     * @return array Validation errors
     */
    public function validateData(array $data): array;
}