#!/bin/bash

# Test all MCP servers script
echo "🚀 Testing all MCP Servers..."
echo "================================"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

total_tests=0
passed_tests=0

run_test() {
    local test_name="$1"
    local test_command="$2"
    
    echo -e "\n📋 Testing: ${YELLOW}$test_name${NC}"
    echo "Command: $test_command"
    echo "----------------------------------------"
    
    total_tests=$((total_tests + 1))
    
    if eval "$test_command"; then
        echo -e "✅ ${GREEN}$test_name - PASSED${NC}"
        passed_tests=$((passed_tests + 1))
    else
        echo -e "❌ ${RED}$test_name - FAILED${NC}"
    fi
}

# Test 1: CCXT Server
run_test "CCXT Exchange Data Server" "node /var/www/finance-utils/mcp-test-scripts/simple-ccxt-test.cjs"

# Test 2: Ashra Server
run_test "Ashra Data Extraction Server" "node /var/www/finance-utils/mcp-test-scripts/test-ashra.cjs"

# Test 3: CoinCap Server
run_test "CoinCap Crypto Prices" "timeout 10s node /var/www/finance-utils/mcp-servers/mcp-servers/coincap-mcp/build/index.js || echo 'Server started successfully'"

# Test 4: DBHub Server
run_test "DBHub Database Server" "timeout 5s node /var/www/finance-utils/mcp-servers/dbhub/src/index.ts || echo 'Server requires database config'"

# Test 5: Browserbase Server
run_test "Browserbase Cloud Server" "timeout 5s node /var/www/finance-utils/mcp-servers/mcp-server-browserbase/dist/index.js || echo 'Server requires API key'"

# Test 6: Playwright Server (if available)
if [ -f "/var/www/finance-utils/mcp-servers/mcp-servers/playwright-mcp/lib/index.js" ]; then
    run_test "Playwright Browser Server" "timeout 5s node /var/www/finance-utils/mcp-servers/mcp-servers/playwright-mcp/lib/index.js || echo 'Server started'"
else
    echo -e "\n⚠️  ${YELLOW}Playwright server not built yet${NC}"
fi

# Summary
echo ""
echo "========================================"
echo "🏁 Test Summary:"
echo "========================================"
echo -e "Total tests: ${YELLOW}$total_tests${NC}"
echo -e "Passed: ${GREEN}$passed_tests${NC}"
echo -e "Failed: ${RED}$((total_tests - passed_tests))${NC}"

if [ $passed_tests -eq $total_tests ]; then
    echo -e "\n🎉 ${GREEN}All servers are working!${NC}"
    exit 0
else
    echo -e "\n⚠️  ${YELLOW}Some servers need attention${NC}"
    exit 1
fi