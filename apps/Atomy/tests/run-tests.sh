#!/usr/bin/env bash

#
# Monorepo Test Runner
#
# This script provides convenient shortcuts for running tests across
# the entire monorepo or specific apps/packages.
#

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Script directory
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
ROOT_DIR="$(dirname "$SCRIPT_DIR")"

# Default values
COVERAGE=false
PARALLEL=false
VERBOSE=false
STOP_ON_FAILURE=false
FILTER=""
GROUP=""
DISABLE_XDEBUG=true

# Usage
usage() {
    cat << EOF
Usage: $0 [OPTIONS] [TARGET]

Run tests across the Laravel ERP monorepo.

TARGETS:
    all                 Run all tests (default)
    app                 Run main application tests
    packages            Run all package tests
    package:NAME        Run specific package tests (e.g., package:audit-logging)
    unit                Run all unit tests
    feature             Run all feature tests
    integration         Run all integration tests

OPTIONS:
    -c, --coverage      Generate code coverage report
    -p, --parallel      Run tests in parallel
    -v, --verbose       Verbose output
    -s, --stop          Stop on first failure
    -f, --filter=TEXT   Run only tests matching filter
    -g, --group=NAME    Run only tests in group
    --with-xdebug       Keep Xdebug enabled
    -h, --help          Show this help message

EXAMPLES:
    # Run all tests
    $0

    # Run main app tests with coverage
    $0 --coverage app

    # Run specific package tests
    $0 package:audit-logging

    # Run only unit tests with filter
    $0 --filter=Uom unit

    # Run tests in parallel
    $0 --parallel

    # Stop on first failure
    $0 --stop-on-failure

EOF
}

# Parse arguments
TARGET="all"
while [[ $# -gt 0 ]]; do
    case $1 in
        -c|--coverage)
            COVERAGE=true
            shift
            ;;
        -p|--parallel)
            PARALLEL=true
            shift
            ;;
        -v|--verbose)
            VERBOSE=true
            shift
            ;;
        -s|--stop|--stop-on-failure)
            STOP_ON_FAILURE=true
            shift
            ;;
        -f|--filter)
            FILTER="$2"
            shift 2
            ;;
        --filter=*)
            FILTER="${1#*=}"
            shift
            ;;
        -g|--group)
            GROUP="$2"
            shift 2
            ;;
        --group=*)
            GROUP="${1#*=}"
            shift
            ;;
        --with-xdebug)
            DISABLE_XDEBUG=false
            shift
            ;;
        -h|--help)
            usage
            exit 0
            ;;
        *)
            TARGET="$1"
            shift
            ;;
    esac
done

# Build command
CMD="vendor/bin/pest"
OPTIONS=""

# Add options
if [ "$COVERAGE" = true ]; then
    OPTIONS="$OPTIONS --coverage"
fi

if [ "$PARALLEL" = true ]; then
    OPTIONS="$OPTIONS --parallel"
fi

if [ "$VERBOSE" = true ]; then
    OPTIONS="$OPTIONS --verbose"
fi

if [ "$STOP_ON_FAILURE" = true ]; then
    OPTIONS="$OPTIONS --stop-on-failure"
fi

if [ -n "$FILTER" ]; then
    OPTIONS="$OPTIONS --filter=$FILTER"
fi

if [ -n "$GROUP" ]; then
    OPTIONS="$OPTIONS --group=$GROUP"
fi

# Determine test path
TEST_PATH=""
case $TARGET in
    all)
        echo -e "${GREEN}Running all tests...${NC}"
        TEST_PATH=""
        ;;
    app)
        echo -e "${GREEN}Running main application tests...${NC}"
        TEST_PATH="apps/headless-erp-app/tests"
        ;;
    packages)
        echo -e "${GREEN}Running all package tests...${NC}"
        TEST_PATH="packages/*/tests"
        ;;
    package:*)
        PACKAGE_NAME="${TARGET#package:}"
        echo -e "${GREEN}Running tests for package: $PACKAGE_NAME...${NC}"
        TEST_PATH="packages/$PACKAGE_NAME/tests"
        if [ ! -d "$ROOT_DIR/$TEST_PATH" ]; then
            echo -e "${RED}Error: Package '$PACKAGE_NAME' not found${NC}"
            exit 1
        fi
        ;;
    unit)
        echo -e "${GREEN}Running all unit tests...${NC}"
        OPTIONS="$OPTIONS --testsuite=Unit"
        ;;
    feature)
        echo -e "${GREEN}Running all feature tests...${NC}"
        OPTIONS="$OPTIONS --testsuite=Feature"
        ;;
    integration)
        echo -e "${GREEN}Running all integration tests...${NC}"
        OPTIONS="$OPTIONS --testsuite=Integration"
        ;;
    *)
        echo -e "${RED}Error: Unknown target '$TARGET'${NC}"
        usage
        exit 1
        ;;
esac

# Change to root directory
cd "$ROOT_DIR"

# Disable Xdebug if requested
if [ "$DISABLE_XDEBUG" = true ]; then
    export XDEBUG_MODE=off
fi

# Run tests
echo -e "${YELLOW}Command: $CMD $OPTIONS $TEST_PATH${NC}"
echo ""

set +e
$CMD $OPTIONS $TEST_PATH
EXIT_CODE=$?
set -e

# Summary
echo ""
if [ $EXIT_CODE -eq 0 ]; then
    echo -e "${GREEN}✓ All tests passed!${NC}"
else
    echo -e "${RED}✗ Some tests failed${NC}"
fi

exit $EXIT_CODE
