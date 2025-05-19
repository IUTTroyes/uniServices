import { defineConfig } from "cypress";

export default defineConfig({
  projectId: "fviiku",

  e2e: {
    setupNodeEvents(on, config) {
      // implement node event listeners here
    },
    baseUrl: 'http://localhost:3000',
    env: {
      apiUrl: 'https://127.0.0.1:8000'
    },
    defaultCommandTimeout: 10000,
    requestTimeout: 10000,
  },

  component: {
    devServer: {
      framework: "vue",
      bundler: "vite",
    },
  },
});
