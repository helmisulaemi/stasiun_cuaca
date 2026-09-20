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

async function runDuplicate(devices) {
  const scenario = config.scenarios.duplicate;
  const intervalMs = scenario.intervalSeconds * 1000;

  console.log(`\n═══ SCENARIO: DUPLICATE ═══`);
  console.log(`Duplicate count: ${scenario.duplicateCount}x per payload`);
  console.log(`Devices: ${devices.map(d => d.id).join(', ')}\n`);

  const state = devices.map(d => ({
    device: d,
    seq: 0,
    rainCounter: d.sensorRanges.rain_counter.start,
  }));

  const results = { total: 0, accepted: 0, duplicates: 0, rejected: 0, rateLimited: 0, failed: 0 };

  let minuteCount = 0;
  const maxMinutes = scenario.durationMinutes;

  while (minuteCount < maxMinutes) {
    const sendPromises = state.map(async (s) => {
      s.seq++;
      s.rainCounter = incrementRainCounter(s.rainCounter, s.device);
      const ts = Math.floor(Date.now() / 1000);

      if (minuteCount === 1) {
        console.log(`\n── Duplicate test: sending same payload ${scenario.duplicateCount}x ──`);

        for (let i = 0; i < scenario.duplicateCount; i++) {
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

          if (i < scenario.duplicateCount - 1) {
            await sleep(500);
          }
        }
      } else {
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
      }
    });

    await Promise.all(sendPromises);
    minuteCount++;
    await sleep(intervalMs);
  }

  return results;
}

export { runDuplicate };
