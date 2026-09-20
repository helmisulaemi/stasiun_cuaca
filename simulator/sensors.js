function randomBetween(min, max) {
  return Math.random() * (max - min) + min;
}

function generateSensorReadings(device, rainCounter) {
  const ranges = device.sensorRanges;
  const readings = [
    { s: 'temp_air', v: parseFloat(randomBetween(ranges.temp_air.min, ranges.temp_air.max).toFixed(1)) },
    { s: 'humidity', v: parseFloat(randomBetween(ranges.humidity.min, ranges.humidity.max).toFixed(1)) },
    { s: 'pressure', v: parseFloat(randomBetween(ranges.pressure.min, ranges.pressure.max).toFixed(1)) },
    { s: 'wind_speed', v: parseFloat(randomBetween(ranges.wind_speed.min, ranges.wind_speed.max).toFixed(1)) },
    { s: 'wind_dir', v: Math.round(randomBetween(ranges.wind_dir.min, ranges.wind_dir.max)) },
    { s: 'rain_counter', v: rainCounter },
    { s: 'solar_rad', v: parseFloat(randomBetween(ranges.solar_rad.min, ranges.solar_rad.max).toFixed(1)) },
  ];

  return readings;
}

function generatePartialReadings(device, rainCounter) {
  const ranges = device.sensorRanges;
  const readings = [
    { s: 'temp_air', v: parseFloat(randomBetween(ranges.temp_air.min, ranges.temp_air.max).toFixed(1)) },
    { s: 'rain_counter', v: rainCounter },
  ];

  return readings;
}

function incrementRainCounter(current, device) {
  const increment = device.sensorRanges.rain_counter.incrementPerMinute;
  if (Math.random() < increment) {
    return current + 1;
  }
  return current;
}

function varyBattery(base) {
  const variation = (Math.random() - 0.5) * 0.1;
  return parseFloat((base + variation).toFixed(2));
}

function varyRssi(base) {
  const variation = Math.round((Math.random() - 0.5) * 6);
  return base + variation;
}

export {
  generateSensorReadings,
  generatePartialReadings,
  incrementRainCounter,
  varyBattery,
  varyRssi,
};
