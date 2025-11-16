<?php

declare(strict_types=1);

namespace Nexus\Procurement\Services;

use Nexus\Procurement\Models\ProcurementContract;
use Nexus\Procurement\Models\ContractAmendment;
use Nexus\Procurement\Models\PurchaseOrder;
use Nexus\Procurement\Enums\ContractStatus;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Contract Management Service
 *
 * Handles procurement contract lifecycle, amendments, and compliance.
 */
class ContractManagementService
{
    /**
     * Create a new procurement contract.
     */
    public function createContract(array $data): ProcurementContract
    {
        return DB::transaction(function () use ($data) {
            $contract = ProcurementContract::create($data);

            // Create initial contract items if provided
            if (isset($data['items'])) {
                $contract->items()->createMany($data['items']);
            }

            return $contract;
        });
    }

    /**
     * Amend an existing contract.
     */
    public function amendContract(int $contractId, array $amendmentData): ContractAmendment
    {
        return DB::transaction(function () use ($contractId, $amendmentData) {
            $contract = ProcurementContract::findOrFail($contractId);

            // Generate amendment number
            $nextAmendmentNumber = $contract->amendments()->count() + 1;
            $amendmentData['amendment_number'] = 'AMD-' . $nextAmendmentNumber;

            $amendment = $contract->amendments()->create($amendmentData);

            // Apply changes to contract if amendment is approved
            if (isset($amendmentData['approved_at'])) {
                $this->applyAmendmentChanges($contract, $amendment);
            }

            return $amendment;
        });
    }

    /**
     * Apply amendment changes to the contract.
     */
    private function applyAmendmentChanges(ProcurementContract $contract, ContractAmendment $amendment): void
    {
        $changes = $amendment->changes;

        foreach ($changes as $field => $value) {
            if ($contract->isFillable($field)) {
                $contract->update([$field => $value]);
            }
        }
    }

    /**
     * Check if a contract can accommodate a purchase order value.
     */
    public function canAccommodatePOValue(ProcurementContract $contract, float $poValue): bool
    {
        if (!$contract->isActive()) {
            return false;
        }

        $remainingValue = $contract->getRemainingValue();
        return $remainingValue >= $poValue;
    }

    /**
     * Link a purchase order to a contract.
     */
    public function linkPOToContract(PurchaseOrder $po, ProcurementContract $contract): void
    {
        if (!$this->canAccommodatePOValue($contract, $po->total_amount)) {
            throw new \InvalidArgumentException('Contract cannot accommodate this PO value');
        }

        $po->update(['contract_id' => $contract->id]);
    }

    /**
     * Get contracts that are due for renewal.
     */
    public function getContractsDueForRenewal(int $daysAhead = 30): Collection
    {
        $renewalDate = Carbon::now()->addDays($daysAhead);

        return ProcurementContract::where('auto_renewal', true)
            ->where('end_date', '<=', $renewalDate)
            ->where('status', ContractStatus::ACTIVE)
            ->get();
    }

    /**
     * Renew a contract.
     */
    public function renewContract(int $contractId, array $renewalData = []): ProcurementContract
    {
        return DB::transaction(function () use ($contractId, $renewalData) {
            $contract = ProcurementContract::findOrFail($contractId);

            $newEndDate = isset($renewalData['new_end_date'])
                ? Carbon::parse($renewalData['new_end_date'])
                : $contract->end_date->addMonths($contract->renewal_period_months ?? 12);

            $contract->update([
                'end_date' => $newEndDate,
                'last_renewed_at' => now(),
                'renewal_count' => $contract->renewal_count + 1,
            ]);

            return $contract;
        });
    }

    /**
     * Get contract utilization summary.
     */
    public function getContractUtilization(int $contractId): array
    {
        $contract = ProcurementContract::with('purchaseOrders')->findOrFail($contractId);

        $totalPOValue = $contract->purchaseOrders->sum('total_amount');
        $remainingValue = $contract->contract_value - $totalPOValue;
        $utilizationPercentage = $contract->contract_value > 0
            ? ($totalPOValue / $contract->contract_value) * 100
            : 0;

        return [
            'contract_id' => $contract->id,
            'contract_value' => $contract->contract_value,
            'total_po_value' => $totalPOValue,
            'remaining_value' => $remainingValue,
            'utilization_percentage' => round($utilizationPercentage, 2),
            'po_count' => $contract->purchaseOrders->count(),
        ];
    }

    /**
     * Expire contracts that have reached their end date.
     */
    public function expireContracts(): int
    {
        return ProcurementContract::where('end_date', '<', now())
            ->where('status', ContractStatus::ACTIVE)
            ->update(['status' => ContractStatus::EXPIRED]);
    }
}