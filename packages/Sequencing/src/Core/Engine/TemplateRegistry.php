<?php

declare(strict_types=1);

namespace Nexus\Sequencing\Core\Engine;

use Nexus\Sequencing\Core\Contracts\PatternTemplateInterface;

/**
 * Pattern Template Registry
 * 
 * Manages the collection of pre-built pattern templates,
 * providing discovery, filtering, and retrieval capabilities.
 * 
 * @package Nexus\Sequencing\Core\Engine
 */
class TemplateRegistry
{
    /**
     * Registered templates
     * 
     * @var array<string, PatternTemplateInterface>
     */
    private array $templates = [];

    /**
     * Register a pattern template.
     * 
     * @param PatternTemplateInterface $template
     * @throws \InvalidArgumentException If template ID already exists
     */
    public function register(PatternTemplateInterface $template): void
    {
        $id = $template->getId();
        
        if (isset($this->templates[$id])) {
            throw new \InvalidArgumentException("Template with ID '{$id}' is already registered");
        }

        $this->templates[$id] = $template;
    }

    /**
     * Get a template by ID.
     * 
     * @param string $id Template ID
     * @return PatternTemplateInterface|null
     */
    public function get(string $id): ?PatternTemplateInterface
    {
        return $this->templates[$id] ?? null;
    }

    /**
     * Check if a template exists.
     * 
     * @param string $id Template ID
     * @return bool
     */
    public function has(string $id): bool
    {
        return isset($this->templates[$id]);
    }

    /**
     * Get all registered templates.
     * 
     * @return PatternTemplateInterface[]
     */
    public function all(): array
    {
        return array_values($this->templates);
    }

    /**
     * Get templates by category.
     * 
     * @param string $category Category name
     * @return PatternTemplateInterface[]
     */
    public function getByCategory(string $category): array
    {
        return array_filter(
            $this->templates,
            fn(PatternTemplateInterface $template) => $template->getCategory() === $category
        );
    }

    /**
     * Get templates by tag.
     * 
     * @param string $tag Tag name
     * @return PatternTemplateInterface[]
     */
    public function getByTag(string $tag): array
    {
        return array_filter(
            $this->templates,
            fn(PatternTemplateInterface $template) => in_array($tag, $template->getTags())
        );
    }

    /**
     * Search templates by name or description.
     * 
     * @param string $query Search query
     * @return PatternTemplateInterface[]
     */
    public function search(string $query): array
    {
        $query = strtolower($query);
        
        return array_filter(
            $this->templates,
            function (PatternTemplateInterface $template) use ($query) {
                $searchableText = strtolower(
                    $template->getName() . ' ' . 
                    $template->getDescription() . ' ' .
                    implode(' ', $template->getTags())
                );
                
                return strpos($searchableText, $query) !== false;
            }
        );
    }

    /**
     * Get all available categories.
     * 
     * @return string[]
     */
    public function getCategories(): array
    {
        $categories = array_unique(
            array_map(
                fn(PatternTemplateInterface $template) => $template->getCategory(),
                $this->templates
            )
        );
        
        sort($categories);
        return $categories;
    }

    /**
     * Get all available tags.
     * 
     * @return string[]
     */
    public function getTags(): array
    {
        $allTags = [];
        
        foreach ($this->templates as $template) {
            $allTags = array_merge($allTags, $template->getTags());
        }
        
        $uniqueTags = array_unique($allTags);
        sort($uniqueTags);
        
        return $uniqueTags;
    }

    /**
     * Get templates that don't require specific context.
     * 
     * @return PatternTemplateInterface[]
     */
    public function getContextFreeTemplates(): array
    {
        return array_filter(
            $this->templates,
            fn(PatternTemplateInterface $template) => empty($template->getRequiredContext())
        );
    }

    /**
     * Get template statistics.
     * 
     * @return array{total: int, by_category: array<string, int>, by_tags: array<string, int>}
     */
    public function getStats(): array
    {
        $byCategory = [];
        $byTags = [];
        
        foreach ($this->templates as $template) {
            // Count by category
            $category = $template->getCategory();
            $byCategory[$category] = ($byCategory[$category] ?? 0) + 1;
            
            // Count by tags
            foreach ($template->getTags() as $tag) {
                $byTags[$tag] = ($byTags[$tag] ?? 0) + 1;
            }
        }
        
        return [
            'total' => count($this->templates),
            'by_category' => $byCategory,
            'by_tags' => $byTags,
        ];
    }

    /**
     * Remove a template.
     * 
     * @param string $id Template ID
     * @return bool True if removed, false if not found
     */
    public function remove(string $id): bool
    {
        if (isset($this->templates[$id])) {
            unset($this->templates[$id]);
            return true;
        }
        
        return false;
    }

    /**
     * Clear all templates.
     */
    public function clear(): void
    {
        $this->templates = [];
    }
}