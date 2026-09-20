import devices from './devices.js';
import { runNormal } from './scenarios/normal.js';
import { runOfflineBatch } from './scenarios/offline-batch.js';
import { runDuplicate } from './scenarios/duplicate.js';

function parseArgs() {
  const args = process.argv.slice(2);
  const parsed = {};

  for (const arg of args) {
    const [key, value] = arg.replace(/^--/, '').split('=');
    parsed[key] = value;
  }

  return parsed;
}

function printResults(results) {
  console.log(`\n══════════════════════════════════════`);
  console.log(`Results:`);
  console.log(`  Total sent:     ${results.total}`);
  console.log(`  Accepted:       ${results.accepted}`);
  console.log(`  Duplicates:     ${results.duplicates}`);
  console.log(`  Rejected:       ${results.rejected}`);
  console.log(`  Rate limited:   ${results.rateLimited}`);
  console.log(`  Failed:         ${results.failed}`);
  console.log(`══════════════════════════════════════\n`);
}

async function main() {
  const args = parseArgs();
  const scenario = args.scenario || 'normal';
  const baseUrl = args.baseUrl || args['base-url'] || 'http://localhost:8000';

  console.log('╔═══════════════════════════════════════╗');
  console.log('║  Weather Station Device Simulator     ║');
  console.log('╚═══════════════════════════════════════╝');
  console.log(`API: ${baseUrl}`);
  console.log(`Scenario: ${scenario}`);
  console.log(`Devices: ${devices.map(d => `${d.id} (${d.location})`).join(', ')}`);
  console.log('');

  const validScenarios = ['normal', 'offline-batch', 'duplicate'];
  if (!validScenarios.includes(scenario)) {
    console.error(`Unknown scenario: ${scenario}`);
    console.error(`Available: ${validScenarios.join(', ')}`);
    process.exit(1);
  }

  const activeDevices = devices.filter(d => !d.simulateError);
  const allDevices = devices;

  let results;

  switch (scenario) {
    case 'normal':
      results = await runNormal(allDevices);
      break;
    case 'offline-batch':
      results = await runOfflineBatch(allDevices);
      break;
    case 'duplicate':
      results = await runDuplicate(allDevices);
      break;
  }

  printResults(results);
}

main().catch(err => {
  console.error('Fatal error:', err);
  process.exit(1);
});
