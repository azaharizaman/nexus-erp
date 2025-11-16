<?php

declare(strict_types=1);

namespace Nexus\Atomy\Http\Resources\Api\Backoffice;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Office API Resource
 * 
 * Standardizes office data format for JSON API responses.
 */
class OfficeResource extends JsonResource
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
            'name' => $this->name,
            'code' => $this->code,
            'description' => $this->description,
            'address' => $this->address,
            'phone' => $this->phone,
            'email' => $this->email,
            'is_active' => $this->is_active,
            'company_id' => $this->company_id,
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
            
            'departments' => $this->whenLoaded('departments', function () {
                return DepartmentResource::collection($this->departments);
            }),
            
            'staff' => $this->whenLoaded('staff', function () {
                return StaffResource::collection($this->staff);
            }),
            
            // Conditional counts
            'departments_count' => $this->when(isset($this->departments_count), $this->departments_count),
            'staff_count' => $this->when(isset($this->staff_count), $this->staff_count),
            'active_staff_count' => $this->when(isset($this->active_staff_count), $this->active_staff_count),
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
                'self' => route('offices.show', $this->id),
                'departments' => route('offices.departments', $this->id),
                'staff' => route('offices.staff', $this->id),
                'company' => route('companies.show', $this->company_id),
            ],
        ];
    }
}