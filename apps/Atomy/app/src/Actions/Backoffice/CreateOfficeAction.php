<?php

declare(strict_types=1);

namespace Nexus\Atomy\Actions\Backoffice;

use Nexus\Atomy\Actions\Action;
use Nexus\Backoffice\Models\Office;
use Nexus\Backoffice\Models\Company;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Validator;

/**
 * Create Office Action
 * 
 * Orchestrates office creation with proper validation and company relationship management.
 */
class CreateOfficeAction extends Action
{
    /**
     * Create a new office with validation.
     * 
     * @param array $officeData
     * @return Office
     * @throws ValidationException
     */
    public function handle(...$parameters): Office
    {
        $officeData = $parameters[0] ?? [];
        
        if (!is_array($officeData)) {
            throw new \InvalidArgumentException('Office data must be an array');
        }
        
        // Validate input data
        $this->validateOfficeData($officeData);
        
        // Verify company exists and is active
        $company = Company::find($officeData['company_id']);
        if (!$company || !$company->is_active) {
            $validator = Validator::make([], []);
            $validator->errors()->add('company_id', 'The selected company is not active or does not exist.');
            throw new ValidationException($validator);
        }
        
        // Create the office
        $office = Office::create([
            'name' => $officeData['name'],
            'code' => $officeData['code'],
            'description' => $officeData['description'] ?? null,
            'address' => $officeData['address'] ?? null,
            'phone' => $officeData['phone'] ?? null,
            'email' => $officeData['email'] ?? null,
            'company_id' => $officeData['company_id'],
            'is_active' => $officeData['is_active'] ?? true,
        ]);
        
        return $office;
    }
    
    /**
     * Validate office data.
     * 
     * @param array $data
     * @return void
     * @throws ValidationException
     */
    protected function validateOfficeData(array $data): void
    {
        $validator = Validator::make($data, [
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:offices,code',
            'description' => 'nullable|string',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'company_id' => 'required|exists:companies,id',
            'is_active' => 'boolean',
        ]);
        
        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }
    
    /**
     * Execute the action with structured return format.
     * 
     * @param mixed ...$parameters
     * @return array
     */
    public function execute(...$parameters): array
    {
        try {
            $officeData = $parameters[0] ?? [];
            if (!is_array($officeData)) {
                throw new \InvalidArgumentException('Office data must be an array');
            }
            
            $office = $this->handle($officeData);
            
            // Load relationships for response
            $office->load(['company']);
            
            return [
                'success' => true,
                'message' => 'Office created successfully',
                'data' => $office,
            ];
            
        } catch (ValidationException $e) {
            return [
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => [],
            ];
        }
    }
}