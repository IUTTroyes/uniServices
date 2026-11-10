import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';
import path from 'path';
import { fileURLToPath } from 'url';

const ici = path.dirname(fileURLToPath(import.meta.url));

// Rendu serveur autonome (vue embarqué) : dist/cli.mjs s'exécute avec un simple `node`, sans node_modules.
export default defineConfig({
  plugins: [vue()],
  resolve: {
    alias: [
      { find: /^.*\/requests\/traceService$/, replacement: path.resolve(ici, 'traceService.stub.js') },
      { find: '@helpers', replacement: path.resolve(ici, '../../../shared/helpers') },
    ],
  },
  ssr: { noExternal: true },
  build: {
    ssr: path.resolve(ici, 'cli.js'),
    outDir: path.resolve(ici, 'dist'),
    emptyOutDir: false,
    target: 'node20',
    rollupOptions: { output: { format: 'es', entryFileNames: 'cli.mjs' } },
  },
});
