import { defineConfig } from 'vitest/config';
import vue from '@vitejs/plugin-vue';
import path from 'path';

export default defineConfig({
  plugins: [vue()],
  resolve: {
    alias: [
      // « @/… » désigne le dossier assets du package qui importe (comme dans packages/shell/vite.config.js),
      // à défaut celui du shell.
      {
        find: /^@\/(.*)/,
        replacement: '$1',
        customResolver(source, importer) {
          const match = importer && importer.match(/\/packages\/([^/]+)\/assets\//);
          const base = match ? path.resolve(__dirname, `packages/${match[1]}/assets`) : path.resolve(__dirname, 'packages/shell/assets');
          return this.resolve(path.resolve(base, source), importer, { skipSelf: true });
        },
      },
      { find: '@types', replacement: path.resolve(__dirname, 'shared/types') },
      { find: '@components', replacement: path.resolve(__dirname, 'shared/components') },
      { find: '@config', replacement: path.resolve(__dirname, 'shared/global-data') },
      { find: '@styles', replacement: path.resolve(__dirname, 'shared/styles') },
      { find: '@images', replacement: path.resolve(__dirname, 'shared/images') },
      { find: '@helpers', replacement: path.resolve(__dirname, 'shared/helpers') },
      { find: '@requests', replacement: path.resolve(__dirname, 'shared/requests') },
      { find: '@stores', replacement: path.resolve(__dirname, 'shared/stores') },
      { find: '@utils', replacement: path.resolve(__dirname, 'shared/utils') },
      { find: '@composables', replacement: path.resolve(__dirname, 'shared/composables') },
      { find: '@dialogs', replacement: path.resolve(__dirname, 'shared/dialogs') },
      { find: '@common-images', replacement: path.resolve(__dirname, 'shared/images') },
    ],
  },
  test: {
    globals: true,
    environment: 'jsdom',
    include: ['packages/**/*.test.js', 'packages/**/*.spec.js', 'shared/**/*.test.js', 'shared/**/*.spec.js'],
  },
});
