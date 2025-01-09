import { defineConfig } from "cypress";

export default defineConfig({
  projectId: "fviiku",
  e2e: {
    setupNodeEvents(on, config) {
      // implement node event listeners here
    },
  },
  env: {
    VITE_BASE_URL: 'http://localhost:3000'
  },
});
