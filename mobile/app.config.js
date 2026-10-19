// app.json holds the config; this only adds a web base path when one is asked for.
// `npm run web:local` builds the web version under /app so the API at localhost:8000 can serve it
// from the same address (browsers, including Claude's browser pane, block requests to another port).
module.exports = ({ config }) =>
  process.env.EXPO_BASE_URL
    ? { ...config, experiments: { ...config.experiments, baseUrl: process.env.EXPO_BASE_URL } }
    : config;
