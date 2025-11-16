<?php

declare(strict_types=1);

namespace Nexus\Backoffice\Database\Factories;

use Nexus\Backoffice\Models\Staff;
use Nexus\Backoffice\Models\Office;
use Nexus\Backoffice\Models\Company;
use Nexus\Backoffice\Models\Position;
use Nexus\Backoffice\Models\Department;
use Nexus\Backoffice\Models\StaffTransfer;
use Illuminate\Database\Eloquent\Factories\Factory;
use Nexus\Backoffice\Enums\StaffTransferStatus;

/**
 * @extends Factory<StaffTransfer>
 */
class StaffTransferFactory extends Factory
{
    protected $model = StaffTransfer::class;

    public function definition(): array
    {
        $company = Company::factory()->create();
        $sourceOffice = Office::factory()->for($company)->create();
        $targetOffice = Office::factory()->for($company)->create();
        $staff = Staff::factory()->for($sourceOffice)->create();

        return [
            'staff_id' => $staff->id,
            'from_office_id' => $sourceOffice->id,
            'to_office_id' => $targetOffice->id,
            'from_department_id' => null,
            'to_department_id' => null,
            'from_supervisor_id' => null,
            'to_supervisor_id' => null,
            'from_position_id' => null,
            'to_position_id' => null,
            'effective_date' => $this->faker->dateTimeBetween('+1 day', '+1 month'),
            'reason' => $this->faker->sentence(),
            'status' => StaffTransferStatus::PENDING,
            'requested_by_id' => $staff->id,
            'requested_at' => $this->faker->unique()->dateTimeBetween('-1 month', 'now'),
            'approved_by_id' => null,
            'approved_at' => null,
            'rejected_by_id' => null,
            'rejected_at' => null,
            'rejection_reason' => null,
            'processed_by_id' => null,
            'completed_at' => null,
            'cancelled_at' => null,
            'notes' => null,
            'is_immediate' => false,
            'metadata' => null,
        ];
    }

    /**
     * Configure the factory for immediate transfers.
     */
    public function immediate(): static
    {
        return $this->state(fn (array $attributes) => [
            'effective_date' => now(),
            'is_immediate' => true,
        ]);
    }

    /**
     * Configure the factory for scheduled transfers.
     */
    public function scheduled(?string $date = null): static
    {
        return $this->state(fn (array $attributes) => [
            'effective_date' => $date ? now()->parse($date) : now()->addWeek(),
        ]);
    }

    /**
     * Configure the factory for pending status.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => StaffTransferStatus::PENDING,
        ]);
    }

    /**
     * Configure the factory for approved status.
     */
    public function approved(?string $notes = null): static
    {
        $staff = Staff::first() ?? Staff::factory()->create();

        return $this->state(fn (array $attributes) => [
            'status' => StaffTransferStatus::APPROVED,
            'approved_by_id' => $staff->id,
            'approved_at' => now(),
            'notes' => $notes ?? 'Transfer approved',
        ]);
    }

    /**
     * Configure the factory for rejected status.
     */
    public function rejected(?string $reason = null): static
    {
        $staff = Staff::first() ?? Staff::factory()->create();

        return $this->state(fn (array $attributes) => [
            'status' => StaffTransferStatus::REJECTED,
            'rejected_by_id' => $staff->id,
            'rejected_at' => now(),
            'rejection_reason' => $reason ?? 'Transfer rejected',
        ]);
    }

    /**
     * Configure the factory for cancelled status.
     */
    public function cancelled(?string $reason = null): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => StaffTransferStatus::CANCELLED,
            'cancelled_at' => now(),
            'notes' => $reason ?? 'Transfer cancelled',
        ]);
    }

    /**
     * Configure the factory for completed status.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => StaffTransferStatus::COMPLETED,
            'completed_at' => now(),
        ]);
    }

    /**
     * Configure the factory with department change.
     */
    public function withDepartmentChange(): static
    {
        return $this->state(function (array $attributes) {
            $office = Office::find($attributes['from_office_id']);
            $company = $office ? $office->company : Company::factory()->create();

            $sourceDepartment = Department::factory()->for($company)->create();
            $targetDepartment = Department::factory()->for($company)->create();

            return [
                'from_department_id' => $sourceDepartment->id,
                'to_department_id' => $targetDepartment->id,
            ];
        });
    }

    /**
     * Configure the factory with supervisor change.
     */
    public function withSupervisorChange(): static
    {
        return $this->state(function (array $attributes) {
            $office = Office::find($attributes['from_office_id']) ?? Office::factory()->create();

            $currentSupervisor = Staff::factory()->for($office)->create();
            $newSupervisor = Staff::factory()->for($office)->create();

            return [
                'from_supervisor_id' => $currentSupervisor->id,
                'to_supervisor_id' => $newSupervisor->id,
            ];
        });
    }

    /**
     * Configure the factory with position change.
     */
    public function withPositionChange(?Position $fromPosition = null, ?Position $toPosition = null): static
    {
        return $this->state(function (array $attributes) use ($fromPosition, $toPosition) {
            $company = Company::find($attributes['staff_id'] ?? null)?->getCompany() 
                ?? Company::factory()->create();
                
            return [
                'from_position_id' => $fromPosition?->id ?? Position::factory()->for($company)->create()->id,
                'to_position_id' => $toPosition?->id ?? Position::factory()->for($company)->create()->id,
            ];
        });
    }

    /**
     * Configure the factory for a complete transfer with all changes.
     */
    public function complete(): static
    {
        return $this->withDepartmentChange()
                    ->withSupervisorChange()
                    ->withPositionChange();
    }
}