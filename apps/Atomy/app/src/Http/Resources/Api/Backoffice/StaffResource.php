<?php

declare(strict_types=1);

namespace Nexus\Atomy\Http\Resources\Api\Backoffice;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Staff API Resource
 * 
 * Standardizes staff data format for JSON API responses.
 */
class StaffResource extends JsonResource
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
            'employee_id' => $this->employee_id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'hire_date' => $this->hire_date?->toDateString(),
            'resignation_date' => $this->resignation_date?->toDateString(),
            'is_active' => $this->is_active,
            'status' => $this->status,
            'company_id' => $this->company_id,
            'office_id' => $this->office_id,
            'department_id' => $this->department_id,
            'position_id' => $this->position_id,
            'supervisor_id' => $this->supervisor_id,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            
            // Conditional relationships
            'company' => $this->whenLoaded('company', function () {
                return [
                    'id' => $this->company->id,
                    'name' => $this->company->name,
                    'code' => $this->company->code,
                ];
            }),
            
            'office' => $this->whenLoaded('office', function () {
                return [
                    'id' => $this->office->id,
                    'name' => $this->office->name,
                    'code' => $this->office->code,
                ];
            }),
            
            'department' => $this->whenLoaded('department', function () {
                return [
                    'id' => $this->department->id,
                    'name' => $this->department->name,
                    'code' => $this->department->code,
                ];
            }),
            
            'position' => $this->whenLoaded('position', function () {
                return [
                    'id' => $this->position->id,
                    'name' => $this->position->name,
                    'level' => $this->position->level ?? null,
                ];
            }),
            
            'supervisor' => $this->whenLoaded('supervisor', function () {
                return [
                    'id' => $this->supervisor->id,
                    'name' => $this->supervisor->name,
                    'employee_id' => $this->supervisor->employee_id,
                ];
            }),
            
            'subordinates' => $this->whenLoaded('subordinates', function () {
                return self::collection($this->subordinates);
            }),
            
            'transfers' => $this->whenLoaded('transfers', function () {
                return StaffTransferResource::collection($this->transfers);
            }),
            
            // Conditional counts
            'subordinates_count' => $this->when(isset($this->subordinates_count), $this->subordinates_count),
            'transfers_count' => $this->when(isset($this->transfers_count), $this->transfers_count),
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
                'self' => route('staff.show', $this->id),
                'transfers' => route('staff.transfer-history', $this->id),
                'company' => $this->when($this->company_id, fn() => route('companies.show', $this->company_id)),
                'office' => $this->when($this->office_id, fn() => route('offices.show', $this->office_id)),
                'department' => $this->when($this->department_id, fn() => route('departments.show', $this->department_id)),
            ],
        ];
    }
}