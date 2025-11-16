<?php

declare(strict_types=1);

namespace Nexus\Manufacturing\Contracts;

use Nexus\Manufacturing\Models\QualityInspection;

interface QualityManagementServiceContract
{
    /**
     * Perform quality inspection.
     * 
     * @param string $workOrderId
     * @param string $inspectionPlanId
     * @param array $measurements [['characteristic_id', 'measured_value', 'pass_fail'], ...]
     * @return QualityInspection
     */
    public function performInspection(string $workOrderId, string $inspectionPlanId, array $measurements): QualityInspection;

    /**
     * Set disposition for failed inspection.
     * 
     * @param string $inspectionId
     * @param string $disposition (accept, reject, rework, quarantine, etc.)
     * @param string $notes
     * @return QualityInspection
     */
    public function setDisposition(string $inspectionId, string $disposition, string $notes = ''): QualityInspection;

    /**
     * Quarantine a lot/batch.
     * 
     * @param string $lotNumber
     * @param string $reason
     * @return array Quarantine record
     */
    public function quarantineLot(string $lotNumber, string $reason): array;

    /**
     * Release quarantined lot.
     * 
     * @param string $lotNumber
     * @param string $approvedBy
     * @return bool
     */
    public function releaseQuarantine(string $lotNumber, string $approvedBy): bool;

    /**
     * Get quality metrics for a period.
     * 
     * @param \DateTimeInterface $startDate
     * @param \DateTimeInterface $endDate
     * @return array ['pass_rate', 'defect_rate', 'top_defects', etc.]
     */
    public function getQualityMetrics(\DateTimeInterface $startDate, \DateTimeInterface $endDate): array;
}
