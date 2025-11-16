<?php

declare(strict_types=1);

namespace Nexus\Atomy\Http\Resources\Api\Backoffice;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Department API Resource
 * 
 * Standardizes department data format for JSON API responses.
 */
class DepartmentResource extends JsonResource
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
            'is_active' => $this->is_active,
            'office_id' => $this->office_id,
            'parent_department_id' => $this->parent_department_id,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            
            // Conditional relationships
            'office' => $this->whenLoaded('office', function () {
                return [
                    'id' => $this->office->id,
                    'name' => $this->office->name,
                    'code' => $this->office->code,
                    'company' => $this->when($this->office->relationLoaded('company'), function () {
                        return [
                            'id' => $this->office->company->id,
                            'name' => $this->office->company->name,
                            'code' => $this->office->company->code,
                        ];
                    }),
                ];
            }),
            
            'parent_department' => $this->whenLoaded('parentDepartment', function () {
                return [
                    'id' => $this->parentDepartment->id,
                    'name' => $this->parentDepartment->name,
                    'code' => $this->parentDepartment->code,
                ];
            }),
            
            'sub_departments' => $this->whenLoaded('subDepartments', function () {
                return self::collection($this->subDepartments);
            }),
            
            'staff' => $this->whenLoaded('staff', function () {
                return StaffResource::collection($this->staff);
            }),
            
            // Conditional counts
            'staff_count' => $this->when(isset($this->staff_count), $this->staff_count),
            'active_staff_count' => $this->when(isset($this->active_staff_count), $this->active_staff_count),
            'sub_departments_count' => $this->when(isset($this->sub_departments_count), $this->sub_departments_count),
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
                'self' => route('departments.show', $this->id),
                'staff' => route('departments.staff', $this->id),
                'hierarchy' => route('departments.hierarchy', $this->id),
                'office' => route('offices.show', $this->office_id),
            ],
        ];
    }
}