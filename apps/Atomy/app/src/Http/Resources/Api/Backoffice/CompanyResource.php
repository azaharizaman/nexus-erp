<?php

declare(strict_types=1);

namespace Nexus\Atomy\Http\Resources\Api\Backoffice;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Company API Resource
 * 
 * Standardizes company data format for JSON API responses.
 */
class CompanyResource extends JsonResource
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
            'parent_company_id' => $this->parent_company_id,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            
            // Conditional relationships
            'parent_company' => $this->whenLoaded('parentCompany', function () {
                return new self($this->parentCompany);
            }),
            
            'subsidiaries' => $this->whenLoaded('subsidiaries', function () {
                return self::collection($this->subsidiaries);
            }),
            
            'offices' => $this->whenLoaded('offices', function () {
                return OfficeResource::collection($this->offices);
            }),
            
            'staff' => $this->whenLoaded('staff', function () {
                return StaffResource::collection($this->staff);
            }),
            
            // Conditional counts
            'offices_count' => $this->when(isset($this->offices_count), $this->offices_count),
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
                'self' => route('companies.show', $this->id),
                'offices' => route('companies.offices', $this->id),
                'staff' => route('companies.staff', $this->id),
                'organizational_chart' => route('companies.organizational-chart', $this->id),
            ],
        ];
    }
}