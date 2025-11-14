<?php

declare(strict_types=1);

namespace Nexus\Sequencing\Core\Contracts;

/**
 * Pattern Template Interface
 * 
 * Contract for pre-built pattern templates that provide common business
 * sequence patterns with customization options and inheritance support.
 * 
 * @package Nexus\Sequencing\Core\Contracts
 */
interface PatternTemplateInterface
{
    /**
     * Get the template identifier.
     * 
     * @return string Unique template identifier
     */
    public function getId(): string;

    /**
     * Get the template name.
     * 
     * @return string Human-readable template name
     */
    public function getName(): string;

    /**
     * Get the template description.
     * 
     * @return string Description of what this template is for
     */
    public function getDescription(): string;

    /**
     * Get the base pattern for this template.
     * 
     * @return string The pattern string with variables and conditionals
     */
    public function getBasePattern(): string;

    /**
     * Get required context variables for this template.
     * 
     * @return array<string> List of required context keys
     */
    public function getRequiredContext(): array;

    /**
     * Get optional context variables for this template.
     * 
     * @return array<string> List of optional context keys with their defaults
     */
    public function getOptionalContext(): array;

    /**
     * Get example context data for this template.
     * 
     * @return array<string, mixed> Example context that would work with this template
     */
    public function getExampleContext(): array;

    /**
     * Generate a pattern customized for specific context.
     * 
     * @param array<string, mixed> $customizations Customization options
     * @return string Customized pattern
     */
    public function customize(array $customizations = []): string;

    /**
     * Preview the pattern with example data.
     * 
     * @param array<string, mixed> $context Optional context override
     * @param int $counterValue Optional counter value for preview
     * @return string Example generated sequence number
     */
    public function preview(array $context = [], int $counterValue = 1): string;

    /**
     * Get the template category.
     * 
     * @return string Template category (e.g., 'Financial', 'HR', 'Procurement')
     */
    public function getCategory(): string;

    /**
     * Get template tags for discovery.
     * 
     * @return array<string> List of tags
     */
    public function getTags(): array;

    /**
     * Check if this template extends another template.
     * 
     * @return string|null Parent template ID or null if base template
     */
    public function getParentTemplateId(): ?string;

    /**
     * Validate template configuration.
     * 
     * @return ValidationResult Validation result
     */
    public function validate(): ValidationResult;
}