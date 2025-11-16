<?php

declare(strict_types=1);

namespace Nexus\Atomy\Actions\Backoffice;

use Nexus\Atomy\Actions\Action;
use Nexus\Backoffice\Models\Staff;
use Nexus\Backoffice\Models\StaffTransfer;
use Nexus\Backoffice\Helpers\StaffTransferHelper;
use Nexus\Backoffice\Enums\StaffTransferStatus;
use Nexus\Backoffice\Exceptions\InvalidTransferException;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Validator;

/**
 * Transfer Staff Action
 * 
 * Orchestrates staff transfer creation with proper validation.
 */
class TransferStaffAction extends Action
{
    /**
     * Create a staff transfer with validation.
     * 
     * @param Staff $staff
     * @param array $transferData
     * @return StaffTransfer
     * @throws ValidationException|InvalidTransferException
     */
    public function handle(...$parameters): StaffTransfer
    {
        $staff = $parameters[0] ?? null;
        $transferData = $parameters[1] ?? [];
        
        if (!$staff instanceof Staff) {
            throw new \InvalidArgumentException('First parameter must be a Staff instance');
        }
        
        if (!is_array($transferData)) {
            throw new \InvalidArgumentException('Transfer data must be an array');
        }
        
        // Validate transfer data
        $this->validateTransferData($transferData);
        
        // Use StaffTransferHelper for business logic validation
        $validationResult = StaffTransferHelper::validateTransferRequest(
            $staff,
            $transferData['to_office_id'],
            $transferData['to_department_id'] ?? null,
            $transferData['to_position_id'] ?? null,
            $transferData['to_supervisor_id'] ?? null
        );
        
        if (!$validationResult['valid']) {
            throw new InvalidTransferException(
                'Transfer validation failed: ' . implode(', ', $validationResult['errors'])
            );
        }
        
        // Create the transfer
        $transfer = StaffTransfer::create([
            'staff_id' => $staff->id,
            'from_office_id' => $staff->office_id,
            'from_department_id' => $staff->department_id,
            'from_position_id' => $staff->position_id,
            'from_supervisor_id' => $staff->supervisor_id,
            'to_office_id' => $transferData['to_office_id'],
            'to_department_id' => $transferData['to_department_id'] ?? null,
            'to_position_id' => $transferData['to_position_id'] ?? null,
            'to_supervisor_id' => $transferData['to_supervisor_id'] ?? null,
            'effective_date' => $transferData['effective_date'],
            'reason' => $transferData['reason'],
            'requested_by' => $transferData['requested_by'],
            'status' => $transferData['type'] === 'immediate' ? StaffTransferStatus::APPROVED : StaffTransferStatus::PENDING,
        ]);
        
        return $transfer;
    }

    /**
     * Validate transfer data.
     * 
     * @param array $data
     * @throws ValidationException
     */
    protected function validateTransferData(array $data): void
    {
        $validator = Validator::make($data, [
            'to_office_id' => 'required|exists:offices,id',
            'to_department_id' => 'nullable|exists:departments,id',
            'to_position_id' => 'nullable|exists:positions,id',
            'to_supervisor_id' => 'nullable|exists:staff,id',
            'effective_date' => 'required|date|after_or_equal:today',
            'reason' => 'required|string|max:500',
            'requested_by' => 'required|exists:staff,id',
            'type' => 'in:immediate,scheduled',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }
}