import { defineConfig } from 'vitest/config';
import vue from '@vitejs/plugin-vue';
import path from 'path';

export default defineConfig({
  plugins: [vue()],
  resolve: {
    alias: {
      '@': path.resolve(__dirname, 'packages/shell/assets'),
      '@types': path.resolve(__dirname, 'shared/types'),
      '@components': path.resolve(__dirname, 'shared/components'),
      '@config': path.resolve(__dirname, 'shared/global-data'),
      '@styles': path.resolve(__dirname, 'shared/styles'),
      '@images': path.resolve(__dirname, 'shared/images'),
      '@helpers': path.resolve(__dirname, 'shared/helpers'),
      '@requests': path.resolve(__dirname, 'shared/requests'),
      '@stores': path.resolve(__dirname, 'shared/stores'),
      '@utils': path.resolve(__dirname, 'shared/utils'),
      '@composables': path.resolve(__dirname, 'shared/composables'),
      '@dialogs': path.resolve(__dirname, 'shared/dialogs'),
      '@common-images': path.resolve(__dirname, 'shared/images'),
    },
  },
  test: {
    globals: true,
    environment: 'jsdom',
    include: ['packages/**/*.test.js', 'packages/**/*.spec.js', 'shared/**/*.test.js', 'shared/**/*.spec.js'],
  },
});
