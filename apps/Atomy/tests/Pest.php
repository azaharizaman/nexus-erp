<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Monorepo Test Configuration
|--------------------------------------------------------------------------
|
| This is the root-level Pest configuration that orchestrates test execution
| across all apps and packages in the monorepo. Each app/package has its own
| test suite that can be run independently or as part of the full suite.
|
| Note: This file is not actively used since we run tests directly from
| app/package directories. It's here for future root-level integration tests.
|
*/

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| Global expectations that are available in all test files.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| Global helper functions for testing.
|
*/

/**
 * Get the path to a specific app's test directory
 */
function appTestPath(string $app): string
{
    return __DIR__."/../apps/{$app}/tests";
}

/**
 * Get the path to a specific package's test directory
 */
function packageTestPath(string $package): string
{
    return __DIR__."/../packages/{$package}/tests";
}
