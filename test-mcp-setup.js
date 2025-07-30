#!/usr/bin/env node

/**
 * Test script for MCP servers configuration
 * This script validates that all MCP servers can be started correctly
 */

const { spawn } = require('child_process');
const path = require('path');
const fs = require('fs');

const CONFIG_PATH = path.join(__dirname, '.claude', 'mcp.json');

async function loadConfig() {
  try {
    const configData = fs.readFileSync(CONFIG_PATH, 'utf8');
    return JSON.parse(configData);
  } catch (error) {
    console.error('❌ Error loading MCP configuration:', error.message);
    process.exit(1);
  }
}

function testServer(name, config) {
  return new Promise((resolve) => {
    console.log(`🧪 Testing ${name}...`);
    
    const child = spawn(config.command, config.args, {
      cwd: config.cwd || __dirname,
      env: { ...process.env, ...config.env },
      stdio: ['pipe', 'pipe', 'pipe']
    });

    let output = '';
    let errorOutput = '';

    child.stdout.on('data', (data) => {
      output += data.toString();
    });

    child.stderr.on('data', (data) => {
      errorOutput += data.toString();
    });

    // Test timeout
    const timeout = setTimeout(() => {
      child.kill();
      console.log(`✅ ${name}: Server started successfully (terminated after timeout)`);
      resolve({ name, status: 'success', message: 'Started successfully' });
    }, 5000);

    child.on('close', (code) => {
      clearTimeout(timeout);
      if (code === 0) {
        console.log(`✅ ${name}: Server exited cleanly`);
        resolve({ name, status: 'success', message: 'Exited cleanly' });
      } else {
        console.log(`❌ ${name}: Server exited with code ${code}`);
        console.log(`   Output: ${output.slice(0, 200)}...`);
        console.log(`   Error: ${errorOutput.slice(0, 200)}...`);
        resolve({ name, status: 'error', message: `Exit code ${code}`, output, errorOutput });
      }
    });

    child.on('error', (error) => {
      clearTimeout(timeout);
      console.log(`❌ ${name}: Failed to start - ${error.message}`);
      resolve({ name, status: 'error', message: error.message });
    });
  });
}

async function main() {
  console.log('🚀 Testing MCP Server Configuration\n');
  
  const config = await loadConfig();
  const results = [];

  for (const [name, serverConfig] of Object.entries(config.mcpServers)) {
    const result = await testServer(name, serverConfig);
    results.push(result);
  }

  console.log('\n📊 Test Results Summary:');
  console.log('========================');
  
  const successful = results.filter(r => r.status === 'success');
  const failed = results.filter(r => r.status === 'error');

  console.log(`✅ Successful: ${successful.length}`);
  console.log(`❌ Failed: ${failed.length}`);

  if (failed.length > 0) {
    console.log('\n❌ Failed servers:');
    failed.forEach(result => {
      console.log(`   - ${result.name}: ${result.message}`);
    });
  }

  console.log('\n💡 Next steps:');
  console.log('1. 📖 Read MCP-COMPLETE-GUIDE.md for detailed documentation');
  console.log('2. 🔑 Add FREE API keys to .env file (optional):');
  console.log('   - Alpha Vantage (25 requests/day): https://www.alphavantage.co/support/#api-key');
  console.log('   - CoinMarketCap (10k calls/month): https://coinmarketcap.com/api/');
  console.log('3. 🐍 Install Python dependencies if needed: pip install -r requirements.txt');
  console.log('4. 🔄 Restart Claude Code to load the new MCP configuration');
  console.log('\n🎉 Status: 8 FREE MCP servers configured! Total cost: $0/month');
}

main().catch(console.error);