import { defineConfig } from 'cypress';
import dotenv from 'dotenv';
import { resolve } from 'path';

// Charger les variables d'environnement à partir du fichier .env
dotenv.config({ path: resolve(__dirname, '../.env') });

export default defineConfig({
  projectId: 'fviiku',
  env: {
    VITE_BASE_URL: process.env.VITE_BASE_URL,
  },
  e2e: {
    setupNodeEvents(on, config) {
      // implement node event listeners here
    },
  },
});
