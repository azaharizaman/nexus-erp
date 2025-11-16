<?php

declare(strict_types=1);

namespace Nexus\Sequencing\Actions;

use Nexus\Sequencing\Core\Engine\TemplateRegistry;
use Nexus\Sequencing\Core\Contracts\PatternTemplateInterface;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * Pattern Template Management Action
 * 
 * Laravel action for managing pattern templates including discovery,
 * preview, and template application to sequences.
 * 
 * @package Nexus\Sequencing\Actions
 */
class ManagePatternTemplatesAction
{
    use AsAction;

    public function __construct(
        private readonly TemplateRegistry $registry
    ) {}

    /**
     * List all available pattern templates.
     * 
     * @param array $filters Optional filters (category, tag, search)
     * @return array{templates: array, stats: array}
     */
    public function listTemplates(array $filters = []): array
    {
        $templates = $this->registry->all();

        // Apply filters
        if (!empty($filters['category'])) {
            $templates = array_filter(
                $templates,
                fn(PatternTemplateInterface $t) => $t->getCategory() === $filters['category']
            );
        }

        if (!empty($filters['tag'])) {
            $templates = array_filter(
                $templates,
                fn(PatternTemplateInterface $t) => in_array($filters['tag'], $t->getTags())
            );
        }

        if (!empty($filters['search'])) {
            $templates = $this->registry->search($filters['search']);
        }

        // Convert to array format
        $templatesData = array_map(function (PatternTemplateInterface $template) {
            return [
                'id' => $template->getId(),
                'name' => $template->getName(),
                'description' => $template->getDescription(),
                'category' => $template->getCategory(),
                'tags' => $template->getTags(),
                'pattern' => $template->getBasePattern(),
                'required_context' => $template->getRequiredContext(),
                'optional_context' => $template->getOptionalContext(),
                'example_context' => $template->getExampleContext(),
                'preview' => $template->preview(),
            ];
        }, $templates);

        return [
            'templates' => array_values($templatesData),
            'stats' => $this->registry->getStats(),
            'categories' => $this->registry->getCategories(),
            'tags' => $this->registry->getTags(),
        ];
    }

    /**
     * Get a specific template by ID.
     * 
     * @param string $templateId Template identifier
     * @return array{success: bool, template?: array, message?: string}
     */
    public function getTemplate(string $templateId): array
    {
        $template = $this->registry->get($templateId);

        if (!$template) {
            return [
                'success' => false,
                'message' => "Template '{$templateId}' not found",
            ];
        }

        return [
            'success' => true,
            'template' => [
                'id' => $template->getId(),
                'name' => $template->getName(),
                'description' => $template->getDescription(),
                'category' => $template->getCategory(),
                'tags' => $template->getTags(),
                'pattern' => $template->getBasePattern(),
                'required_context' => $template->getRequiredContext(),
                'optional_context' => $template->getOptionalContext(),
                'example_context' => $template->getExampleContext(),
                'parent_template_id' => $template->getParentTemplateId(),
            ],
        ];
    }

    /**
     * Preview a template with custom context.
     * 
     * @param string $templateId Template identifier
     * @param array $context Custom context data
     * @param int $counterValue Counter value for preview
     * @return array{success: bool, preview?: string, pattern?: string, message?: string}
     */
    public function previewTemplate(
        string $templateId,
        array $context = [],
        int $counterValue = 1
    ): array {
        $template = $this->registry->get($templateId);

        if (!$template) {
            return [
                'success' => false,
                'message' => "Template '{$templateId}' not found",
            ];
        }

        try {
            $preview = $template->preview($context, $counterValue);
            
            return [
                'success' => true,
                'preview' => $preview,
                'pattern' => $template->getBasePattern(),
                'context_used' => array_merge($template->getExampleContext(), $context),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => "Preview failed: {$e->getMessage()}",
            ];
        }
    }

    /**
     * Customize a template with options.
     * 
     * @param string $templateId Template identifier
     * @param array $customizations Customization options
     * @return array{success: bool, customized_pattern?: string, original_pattern?: string, message?: string}
     */
    public function customizeTemplate(string $templateId, array $customizations = []): array
    {
        $template = $this->registry->get($templateId);

        if (!$template) {
            return [
                'success' => false,
                'message' => "Template '{$templateId}' not found",
            ];
        }

        try {
            $customizedPattern = $template->customize($customizations);
            
            return [
                'success' => true,
                'customized_pattern' => $customizedPattern,
                'original_pattern' => $template->getBasePattern(),
                'customizations_applied' => $customizations,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => "Customization failed: {$e->getMessage()}",
            ];
        }
    }

    /**
     * Validate a template.
     * 
     * @param string $templateId Template identifier
     * @return array{success: bool, validation: array, message?: string}
     */
    public function validateTemplate(string $templateId): array
    {
        $template = $this->registry->get($templateId);

        if (!$template) {
            return [
                'success' => false,
                'message' => "Template '{$templateId}' not found",
            ];
        }

        $validation = $template->validate();

        return [
            'success' => true,
            'validation' => [
                'is_valid' => $validation->isValid,
                'errors' => $validation->getErrors(),
                'warnings' => $validation->getWarnings(),
            ],
        ];
    }

    /**
     * Search templates by query.
     * 
     * @param string $query Search query
     * @return array{results: array, query: string, count: int}
     */
    public function searchTemplates(string $query): array
    {
        $results = $this->registry->search($query);

        $resultsData = array_map(function (PatternTemplateInterface $template) {
            return [
                'id' => $template->getId(),
                'name' => $template->getName(),
                'description' => $template->getDescription(),
                'category' => $template->getCategory(),
                'tags' => $template->getTags(),
                'pattern' => $template->getBasePattern(),
            ];
        }, $results);

        return [
            'results' => array_values($resultsData),
            'query' => $query,
            'count' => count($resultsData),
        ];
    }
}