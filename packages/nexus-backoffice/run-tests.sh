#!/bin/bash

# Test runner script for BackOffice Package
echo "Running BackOffice Package Tests..."

# Check if vendor directory exists
if [ ! -d "vendor" ]; then
    echo "Vendor directory not found. Please run 'composer install' first."
    exit 1
fi

# Check if PHPUnit is available
if [ ! -f "vendor/bin/phpunit" ]; then
    echo "PHPUnit not found. Please run 'composer install' first."
    exit 1
fi

# Run the tests
echo "Starting test execution..."
vendor/bin/phpunit --configuration phpunit.xml --verbose

echo "Test execution completed."