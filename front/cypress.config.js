import { defineConfig } from "cypress";

export default defineConfig({
  projectId: "fviiku",
  env: {
    VITE_BASE_URL: process.env.VITE_BASE_URL,
  },
  e2e: {
    setupNodeEvents(on, config) {
      // implement node event listeners here
    },
  },
});
