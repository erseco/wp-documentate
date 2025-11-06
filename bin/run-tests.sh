#!/usr/bin/env bash
# Script to run PHPUnit tests with proper environment setup
#
# Usage:
#   ./bin/run-tests.sh                    # Run all tests
#   ./bin/run-tests.sh --filter=MyTest    # Run specific tests
#   ./bin/run-tests.sh tests/unit/...     # Run specific file

# Colors for output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Check if .env.testing exists and load it
if [ -f .env.testing ]; then
    echo -e "${GREEN}Loading environment from .env.testing${NC}"
    export $(cat .env.testing | grep -v '^#' | xargs)
else
    echo -e "${YELLOW}Warning: .env.testing not found${NC}"
    echo "Using default environment variables"
fi

# Check if WP_TESTS_DIR is set
if [ -z "$WP_TESTS_DIR" ]; then
    WP_TESTS_DIR="/tmp/wordpress-tests-lib"
    export WP_TESTS_DIR
    echo -e "${YELLOW}WP_TESTS_DIR not set, using default: $WP_TESTS_DIR${NC}"
fi

# Check if WP_DEVELOP_DIR is set
if [ -z "$WP_DEVELOP_DIR" ]; then
    WP_DEVELOP_DIR="/tmp/wordpress"
    export WP_DEVELOP_DIR
    echo -e "${YELLOW}WP_DEVELOP_DIR not set, using default: $WP_DEVELOP_DIR${NC}"
fi

# Check if WordPress test suite is installed
if [ ! -d "$WP_TESTS_DIR" ]; then
    echo -e "${RED}Error: WordPress test suite not found at $WP_TESTS_DIR${NC}"
    echo ""
    echo "Please run the installation script first:"
    echo "  bash bin/install-wp-tests.sh <db-name> <db-user> <db-pass> [db-host] [wp-version]"
    echo ""
    echo "Example:"
    echo "  bash bin/install-wp-tests.sh wordpress_test root '' localhost latest"
    exit 1
fi

# Check if vendor directory exists
if [ ! -d "vendor" ]; then
    echo -e "${RED}Error: vendor directory not found${NC}"
    echo "Please run: composer install"
    exit 1
fi

# Check if PHPUnit is installed
if [ ! -f "vendor/bin/phpunit" ]; then
    echo -e "${RED}Error: PHPUnit not found${NC}"
    echo "Please run: composer install"
    exit 1
fi

# Display configuration
echo ""
echo "==================================="
echo "PHPUnit Test Runner"
echo "==================================="
echo "WP_TESTS_DIR: $WP_TESTS_DIR"
echo "WP_DEVELOP_DIR: $WP_DEVELOP_DIR"
echo "==================================="
echo ""

# Run PHPUnit with all arguments passed to this script
./vendor/bin/phpunit "$@"

# Capture exit code
EXIT_CODE=$?

# Display result
echo ""
if [ $EXIT_CODE -eq 0 ]; then
    echo -e "${GREEN}✓ Tests completed successfully${NC}"
else
    echo -e "${RED}✗ Tests failed with exit code $EXIT_CODE${NC}"
fi

exit $EXIT_CODE
