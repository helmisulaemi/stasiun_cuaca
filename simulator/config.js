const config = {
  baseUrl: process.env.API_BASE_URL || 'http://localhost:8000',
  apiPrefix: '/api/v1',

  scenarios: {
    normal: {
      durationMinutes: 10,
      intervalSeconds: 60,
    },
    'offline-batch': {
      durationMinutes: 10,
      offlineMinutes: 5,
      batchSize: 5,
      intervalSeconds: 60,
    },
    duplicate: {
      durationMinutes: 2,
      intervalSeconds: 60,
      duplicateCount: 3,
    },
  },

  rateLimitPerDevicePerMinute: 10,
};

export default config;
