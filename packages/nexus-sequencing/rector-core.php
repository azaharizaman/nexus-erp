<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\LevelSetList;
use Rector\TypeDeclaration\Rector\ClassMethod\AddVoidReturnTypeWhereNoReturnRector;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/src/Core',
    ])
    
    // Use PHP 8.3 features
    ->withPhpSets(php83: true)
    
    // Apply coding standards
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        codingStyle: true,
        typeDeclarations: true,
        privatization: true,
        earlyReturn: true,
        strictBooleans: true
    )
    
    // Specific rules for Core purity refactoring
    ->withRules([
        // Add void return types where missing
        AddVoidReturnTypeWhereNoReturnRector::class,
    ])
    
    // Skip vendor and tests
    ->withSkip([
        __DIR__ . '/vendor',
        __DIR__ . '/tests',
    ])
    
    // Import names with strict mode
    ->withImportNames(importShortClasses: false, removeUnusedImports: true);
