import config from '../config.js';
import { generateSensorReadings, incrementRainCounter, varyBattery, varyRssi } from '../sensors.js';

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

async function runNormal(devices) {
  const scenario = config.scenarios.normal;
  const durationMs = scenario.durationMinutes * 60 * 1000;
  const intervalMs = scenario.intervalSeconds * 1000;

  console.log(`\n═══ SCENARIO: NORMAL ═══`);
  console.log(`Duration: ${scenario.durationMinutes} min | Interval: ${scenario.intervalSeconds}s`);
  console.log(`Devices: ${devices.map(d => d.id).join(', ')}\n`);

  const state = devices.map(d => ({
    device: d,
    seq: 0,
    rainCounter: d.sensorRanges.rain_counter.start,
  }));

  const results = { total: 0, accepted: 0, duplicates: 0, rejected: 0, rateLimited: 0, failed: 0 };
  const startTime = Date.now();

  while (Date.now() - startTime < durationMs) {
    const sendPromises = state.map(async (s) => {
      s.seq++;
      s.rainCounter = incrementRainCounter(s.rainCounter, s.device);
      const ts = Math.floor(Date.now() / 1000);
      const result = await sendTelemetry(s.device, ts, s.seq, s.rainCounter);

      results.total++;
      if (result.status === 200 || result.status === 207) {
        const accepted = result.data?.data?.accepted || 0;
        const dupes = result.data?.data?.duplicates || 0;
        results.accepted += accepted;
        results.duplicates += dupes;
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

export { runNormal };
