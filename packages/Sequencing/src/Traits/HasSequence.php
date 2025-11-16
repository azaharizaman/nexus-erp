<?php

declare(strict_types=1);

namespace Nexus\Sequencing\Traits;

use Nexus\Sequencing\Actions\GenerateSerialNumberAction;

/**
 * HasSequence Trait
 *
 * This trait provides automatic sequence number generation for Eloquent models.
 * When a model uses this trait, it will automatically generate a sequence number
 * on creation if the sequence field is empty.
 *
 * Usage:
 * ```php
 * class PurchaseOrder extends Model
 * {
 *     use HasSequence;
 *
 *     protected $sequenceField = 'po_number';     // Override default
 *     protected $sequenceName = 'PURCHASE_ORDER'; // Override default
 * }
 * ```
 *
 * Features:
 * - Automatic generation on model creation
 * - Configurable sequence name resolution
 * - Configurable target field name
 * - Tenant isolation support
 * - Skip generation if field already has value
 * - Error handling for missing sequences
 */
trait HasSequence
{
    /**
     * Boot the trait.
     */
    public static function bootHasSequence(): void
    {
        static::creating(function ($model) {
            $sequenceField = $model->getSequenceField();
            
            // Skip if sequence field already has a value
            if (!empty($model->{$sequenceField})) {
                return;
            }

            try {
                $tenantId = $model->getTenantId();
                $sequenceName = $model->getSequenceName();
                
                // Generate sequence number
                $sequenceNumber = app(GenerateSerialNumberAction::class)->handle(
                    $tenantId,
                    $sequenceName,
                    $model->getSequenceContext()
                );
                
                // Assign to model
                $model->{$sequenceField} = $sequenceNumber;
                
            } catch (\Exception $e) {
                // Log error but don't prevent model creation
                logger()->error('Failed to generate sequence number', [
                    'model' => get_class($model),
                    'tenant_id' => $model->getTenantId() ?? 'unknown',
                    'sequence_name' => $model->getSequenceName() ?? 'unknown',
                    'error' => $e->getMessage()
                ]);
                
                // Re-throw if configured to fail hard
                if ($model->getSequenceFailureMode() === 'strict') {
                    throw $e;
                }
            }
        });
    }

    /**
     * Get the field name for storing the sequence number.
     *
     * Override this method or set $sequenceField property to customize.
     *
     * @return string
     */
    public function getSequenceField(): string
    {
        return $this->sequenceField ?? 'sequence_number';
    }

    /**
     * Get the sequence name for this model.
     *
     * Override this method or set $sequenceName property to customize.
     * Default format: ModelName_NUMBER (e.g., PURCHASE_ORDER_NUMBER)
     *
     * @return string
     */
    public function getSequenceName(): string
    {
        if (isset($this->sequenceName)) {
            return $this->sequenceName;
        }

        $className = class_basename($this);
        return strtoupper($className) . '_NUMBER';
    }

    /**
     * Get the tenant ID for sequence isolation.
     *
     * Override this method to customize tenant ID resolution.
     * Tries common patterns: tenant_id, tenantId, organization_id
     *
     * @return string|null
     */
    public function getTenantId(): ?string
    {
        // Try common tenant ID field patterns
        $fields = ['tenant_id', 'tenantId', 'organization_id', 'org_id'];
        
        foreach ($fields as $field) {
            if (isset($this->attributes[$field])) {
                return (string) $this->attributes[$field];
            }
        }

        // Check if there's a tenant relationship
        if (method_exists($this, 'tenant') && $this->tenant) {
            return (string) $this->tenant->id;
        }

        // Fallback to a default tenant or throw error
        if (config('app.env') === 'testing') {
            return 'default-tenant';
        }

        throw new \RuntimeException(
            'Cannot determine tenant ID for sequence generation. ' .
            'Please implement getTenantId() method or ensure tenant_id field exists.'
        );
    }

    /**
     * Get additional context for sequence generation.
     *
     * Override this method to provide custom context variables
     * that can be used in sequence patterns.
     *
     * @return array<string, mixed>
     */
    public function getSequenceContext(): array
    {
        return [
            'model_type' => class_basename($this),
            'model_id' => $this->id ?? null,
        ];
    }

    /**
     * Get failure mode for sequence generation.
     *
     * Options:
     * - 'silent': Log error but continue (default)
     * - 'strict': Throw exception and prevent model creation
     *
     * Override this method or set $sequenceFailureMode property to customize.
     *
     * @return string
     */
    public function getSequenceFailureMode(): string
    {
        return $this->sequenceFailureMode ?? 'silent';
    }

    /**
     * Regenerate sequence number for this model.
     *
     * This method allows manual regeneration of sequence numbers
     * for existing models. Use with caution as it may create duplicates.
     *
     * @param bool $force Force regeneration even if field has value
     * @return string The new sequence number
     * @throws \Exception If generation fails
     */
    public function regenerateSequenceNumber(bool $force = false): string
    {
        $sequenceField = $this->getSequenceField();
        
        if (!$force && !empty($this->{$sequenceField})) {
            throw new \RuntimeException(
                "Sequence field '{$sequenceField}' already has value. Use \$force = true to override."
            );
        }

        $sequenceNumber = app(GenerateSerialNumberAction::class)->handle(
            $this->getTenantId(),
            $this->getSequenceName(),
            $this->getSequenceContext()
        );

        $this->{$sequenceField} = $sequenceNumber;
        
        if ($this->exists) {
            $this->save();
        }

        return $sequenceNumber;
    }

    /**
     * Check if this model has an auto-generated sequence number.
     *
     * @return bool
     */
    public function hasAutoSequence(): bool
    {
        $sequenceField = $this->getSequenceField();
        return !empty($this->{$sequenceField});
    }

    /**
     * Get the sequence number for this model.
     *
     * @return string|null
     */
    public function getSequenceNumber(): ?string
    {
        $sequenceField = $this->getSequenceField();
        return $this->{$sequenceField};
    }
}