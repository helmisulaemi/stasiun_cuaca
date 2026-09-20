import config from '../config.js';
import { generateSensorReadings, generatePartialReadings, incrementRainCounter, varyBattery, varyRssi } from '../sensors.js';

function sleep(ms) {
  return new Promise(resolve => setTimeout(resolve, ms));
}

function log(deviceId, status, details) {
  const time = new Date().toISOString().slice(11, 19);
  console.log(`[${time}] ${deviceId} → ${status} ${details}`);
}

async function sendTelemetry(device, ts, seq, rainCounter) {
  const payload = {
    device_id: device.id,
    fw: device.fw,
    ts,
    seq,
    battery_v: varyBattery(device.baseBattery),
    rssi: varyRssi(device.baseRssi),
    readings: generateSensorReadings(device, rainCounter),
  };

  const url = `${config.baseUrl}${config.apiPrefix}/ingest/telemetry`;

  try {
    const res = await fetch(url, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-Api-Key': device.apiKey,
      },
      body: JSON.stringify(payload),
    });

    const data = await res.json();
    const status = res.ok ? 'OK' : `ERROR ${res.status}`;
    const details = data.data
      ? `(accepted: ${data.data.accepted || 0}, duplicates: ${data.data.duplicates || 0})`
      : `(code: ${data.error?.code || 'UNKNOWN'})`;

    log(device.id, `${res.status} ${status}`, details);
    return { status: res.status, data };
  } catch (err) {
    log(device.id, 'FAILED', err.message);
    return { status: 0, error: err.message };
  }
}

async function sendBatch(device, batchItems) {
  const payload = {
    device_id: device.id,
    fw: device.fw,
    batch: batchItems,
  };

  const url = `${config.baseUrl}${config.apiPrefix}/ingest/telemetry/batch`;

  try {
    const res = await fetch(url, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-Api-Key': device.apiKey,
      },
      body: JSON.stringify(payload),
    });

    const data = await res.json();
    const status = res.ok ? 'OK' : `ERROR ${res.status}`;
    const details = data.data
      ? `(received: ${data.data.received}, accepted: ${data.data.accepted}, duplicates: ${data.data.duplicates})`
      : `(code: ${data.error?.code || 'UNKNOWN'})`;

    log(device.id, `${res.status} ${status}`, details);
    return { status: res.status, data };
  } catch (err) {
    log(device.id, 'FAILED', err.message);
    return { status: 0, error: err.message };
  }
}

async function runOfflineBatch(devices) {
  const scenario = config.scenarios['offline-batch'];
  const offlineMs = scenario.offlineMinutes * 60 * 1000;
  const intervalMs = scenario.intervalSeconds * 1000;

  console.log(`\n═══ SCENARIO: OFFLINE → BATCH ═══`);
  console.log(`Offline: ${scenario.offlineMinutes} min | Batch size: ${scenario.batchSize}`);
  console.log(`Devices: ${devices.map(d => d.id).join(', ')}\n`);

  const targetDevice = devices[0];
  const otherDevices = devices.slice(1);

  const results = { total: 0, accepted: 0, duplicates: 0, rejected: 0, rateLimited: 0, failed: 0 };

  const state = devices.map(d => ({
    device: d,
    seq: 0,
    rainCounter: d.sensorRanges.rain_counter.start,
  }));

  console.log(`\n── Phase 1: Offline (${scenario.offlineMinutes} min) ──`);
  console.log(`${targetDevice.id} is offline, other devices normal...\n`);

  const phase1Start = Date.now();
  while (Date.now() - phase1Start < offlineMs) {
    const sendPromises = state.filter(s => s.device !== targetDevice).map(async (s) => {
      s.seq++;
      s.rainCounter = incrementRainCounter(s.rainCounter, s.device);
      const ts = Math.floor(Date.now() / 1000);
      const result = await sendTelemetry(s.device, ts, s.seq, s.rainCounter);

      results.total++;
      if (result.status === 200 || result.status === 207) {
        results.accepted += result.data?.data?.accepted || 0;
        results.duplicates += result.data?.data?.duplicates || 0;
      } else if (result.status === 429) {
        results.rateLimited++;
      } else if (result.status === 403) {
        results.rejected++;
      } else {
        results.failed++;
      }
    });

    await Promise.all(sendPromises);
    await sleep(intervalMs);
  }

  console.log(`\n── Phase 2: Batch Upload (${scenario.batchSize} records) ──`);
  console.log(`${targetDevice.id} comes back online, sends buffered data...\n`);

  const targetState = state.find(s => s.device === targetDevice);
  const batchItems = [];
  const now = Math.floor(Date.now() / 1000);

  for (let i = 0; i < scenario.batchSize; i++) {
    targetState.seq++;
    targetState.rainCounter = incrementRainCounter(targetState.rainCounter, targetDevice);
    batchItems.push({
      ts: now - (scenario.batchSize - i) * scenario.intervalSeconds,
      seq: targetState.seq,
      battery_v: varyBattery(targetDevice.baseBattery),
      rssi: varyRssi(targetDevice.baseRssi),
      readings: generatePartialReadings(targetDevice, targetState.rainCounter),
    });
  }

  const batchResult = await sendBatch(targetDevice, batchItems);
  results.total++;
  if (batchResult.status === 200 || batchResult.status === 207) {
    results.accepted += batchResult.data?.data?.accepted || 0;
    results.duplicates += batchResult.data?.data?.duplicates || 0;
  } else if (batchResult.status === 429) {
    results.rateLimited++;
  } else if (batchResult.status === 403) {
    results.rejected++;
  } else {
    results.failed++;
  }

  console.log(`\n── Phase 3: Resume Normal ──`);
  console.log(`${targetDevice.id} resumes normal interval...\n`);

  const remainingMs = (scenario.durationMinutes * 60 * 1000) - offlineMs - 5000;
  const phase3Start = Date.now();
  while (Date.now() - phase3Start < remainingMs) {
    const sendPromises = state.map(async (s) => {
      s.seq++;
      s.rainCounter = incrementRainCounter(s.rainCounter, s.device);
      const ts = Math.floor(Date.now() / 1000);
      const result = await sendTelemetry(s.device, ts, s.seq, s.rainCounter);

      results.total++;
      if (result.status === 200 || result.status === 207) {
        results.accepted += result.data?.data?.accepted || 0;
        results.duplicates += result.data?.data?.duplicates || 0;
      } else if (result.status === 429) {
        results.rateLimited++;
      } else if (result.status === 403) {
        results.rejected++;
      } else {
        results.failed++;
      }
    });

    await Promise.all(sendPromises);
    await sleep(intervalMs);
  }

  return results;
}

export { runOfflineBatch };
