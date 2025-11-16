<?php

declare(strict_types=1);

namespace Nexus\Atomy\Http\Resources\Api\Backoffice;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Staff Transfer API Resource
 * 
 * Standardizes staff transfer data format for JSON API responses.
 */
class StaffTransferResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'staff_id' => $this->staff_id,
            'from_office_id' => $this->from_office_id,
            'to_office_id' => $this->to_office_id,
            'from_department_id' => $this->from_department_id,
            'to_department_id' => $this->to_department_id,
            'from_position_id' => $this->from_position_id,
            'to_position_id' => $this->to_position_id,
            'from_supervisor_id' => $this->from_supervisor_id,
            'to_supervisor_id' => $this->to_supervisor_id,
            'effective_date' => $this->effective_date?->toDateString(),
            'reason' => $this->reason,
            'notes' => $this->notes,
            'status' => $this->status,
            'processed_at' => $this->processed_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            
            // Conditional relationships
            'staff' => $this->whenLoaded('staff', function () {
                return [
                    'id' => $this->staff->id,
                    'name' => $this->staff->name,
                    'employee_id' => $this->staff->employee_id,
                ];
            }),
            
            'from_office' => $this->whenLoaded('fromOffice', function () {
                return [
                    'id' => $this->fromOffice->id,
                    'name' => $this->fromOffice->name,
                    'code' => $this->fromOffice->code,
                ];
            }),
            
            'to_office' => $this->whenLoaded('toOffice', function () {
                return [
                    'id' => $this->toOffice->id,
                    'name' => $this->toOffice->name,
                    'code' => $this->toOffice->code,
                ];
            }),
            
            'from_department' => $this->whenLoaded('fromDepartment', function () {
                return [
                    'id' => $this->fromDepartment->id,
                    'name' => $this->fromDepartment->name,
                    'code' => $this->fromDepartment->code,
                ];
            }),
            
            'to_department' => $this->whenLoaded('toDepartment', function () {
                return [
                    'id' => $this->toDepartment->id,
                    'name' => $this->toDepartment->name,
                    'code' => $this->toDepartment->code,
                ];
            }),
            
            'from_position' => $this->whenLoaded('fromPosition', function () {
                return [
                    'id' => $this->fromPosition->id,
                    'name' => $this->fromPosition->name,
                    'level' => $this->fromPosition->level ?? null,
                ];
            }),
            
            'to_position' => $this->whenLoaded('toPosition', function () {
                return [
                    'id' => $this->toPosition->id,
                    'name' => $this->toPosition->name,
                    'level' => $this->toPosition->level ?? null,
                ];
            }),
            
            'from_supervisor' => $this->whenLoaded('fromSupervisor', function () {
                return [
                    'id' => $this->fromSupervisor->id,
                    'name' => $this->fromSupervisor->name,
                    'employee_id' => $this->fromSupervisor->employee_id,
                ];
            }),
            
            'to_supervisor' => $this->whenLoaded('toSupervisor', function () {
                return [
                    'id' => $this->toSupervisor->id,
                    'name' => $this->toSupervisor->name,
                    'employee_id' => $this->toSupervisor->employee_id,
                ];
            }),
        ];
    }
    
    /**
     * Get additional data for the resource array.
     *
     * @return array<string, mixed>
     */
    public function with(Request $request): array
    {
        return [
            'links' => [
                'staff' => route('staff.show', $this->staff_id),
            ],
        ];
    }
}