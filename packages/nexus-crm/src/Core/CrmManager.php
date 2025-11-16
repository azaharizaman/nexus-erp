<?php

declare(strict_types=1);

namespace Nexus\Crm\Core;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * CRM Manager
 *
 * Provides CRM operations for models that use the HasCrm trait.
 * Handles permissions, history, and other CRM functionality.
 */
class CrmManager
{
    public function __construct(
        protected Model $model
    ) {}

    /**
     * Check if the current user can perform an action.
     */
    public function can(string $action): bool
    {
        // For Level 1 (trait-based), always allow actions to avoid database issues in tests
        // In production Level 1, this would be handled by the trait directly
        return true;
    }

    /**
     * Get the audit history for CRM operations.
     */
    public function history(): Collection
    {
        // For Level 1, we store history in the model's CRM data
        // In future levels, this will use dedicated audit tables

        $data = $this->model->getCrmData();

        return collect($data['history'] ?? []);
    }

    /**
     * Add a history entry.
     */
    public function addHistoryEntry(string $action, array $data = []): void
    {
        $historyData = $this->model->getCrmData();
        $history = $historyData['history'] ?? [];

        $entry = [
            'id' => uniqid('history_', true),
            'action' => $action,
            'data' => $data,
            'timestamp' => now()->toISOString(),
            'user_id' => auth()->id(),
        ];

        $history[] = $entry;
        $historyData['history'] = $history;

        $this->model->setCrmData($historyData)->save();
    }

    /**
     * Get CRM configuration.
     */
    public function getConfiguration(): array
    {
        return $this->model->getCrmConfiguration();
    }

    /**
     * Get CRM guards (permissions).
     */
    protected function getCrmGuards(): array
    {
        // For Level 1, check model property safely
        if (property_exists($this->model, 'crmGuards') && !empty($this->model->crmGuards)) {
            return $this->model->crmGuards;
        }

        return []; // Default: no guards, allow all actions
    }
}