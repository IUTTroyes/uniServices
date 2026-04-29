import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import path from 'path'
import Components from 'unplugin-vue-components/vite'
import { PrimeVueResolver } from '@primevue/auto-import-resolver'
import tailwindcss from '@tailwindcss/vite'

export default defineConfig({
  plugins: [
    vue(),
    tailwindcss(),
    Components({
      resolvers: [
        PrimeVueResolver()
      ],
      dts: path.resolve(__dirname, 'assets/components.d.ts'),
    })
  ],
  root: path.resolve(__dirname, 'assets'),
  base: '/intranet/',
  build: {
    outDir: path.resolve(__dirname, '../../back/public/intranet'),
    emptyOutDir: true,
  },
  server: {
    proxy: {
      '/api': {
        target: 'http://localhost:8000',
        changeOrigin: true,
        secure: false,
      },
    },
  },
  resolve: {
    alias: {
      '@': path.resolve(__dirname, 'assets'),
      '@types': path.resolve(__dirname, '../../shared/types'),
      '@components': path.resolve(__dirname, '../../shared/components'),
      '@config': path.resolve(__dirname, '../../shared/global-data'),
      '@styles': path.resolve(__dirname, '../../shared/styles'),
      '@images': path.resolve(__dirname, '../../shared/images'),
      '@helpers': path.resolve(__dirname, '../../shared/helpers'),
      '@requests': path.resolve(__dirname, '../../shared/requests'),
      '@stores': path.resolve(__dirname, '../../shared/stores'),
      '@utils': path.resolve(__dirname, '../../shared/utils'),
      '@composables': path.resolve(__dirname, 'assets/composables'),
      '@common-images': path.resolve(__dirname, '../../shared/images'),
    },
  },
})
