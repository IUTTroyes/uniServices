import { defineConfig, loadEnv } from 'vite'
import vue from '@vitejs/plugin-vue'
import path from 'path'
import { createProxyMiddleware } from 'http-proxy-middleware'
import { PrimeVueResolver } from '@primevue/auto-import-resolver'
import Components from 'unplugin-vue-components/vite'
import vueDevTools from 'vite-plugin-vue-devtools'

export default defineConfig(({ mode }) => {
  const env = loadEnv(mode, process.cwd(), '')
  const base = env.VITE_BASE || '/'
  const appName = env.VITE_SUB_PROJECT_NAME || ''

  return {
    optimizeDeps: {
      noDiscovery: true
    },
    plugins: [
        vueDevTools(),
      vue(),
      Components({
        resolvers: [PrimeVueResolver()]
      })
    ],
    root: path.resolve(__dirname, `apps/${appName}`),
    resolve: {
      alias: {
        '@': path.resolve(__dirname, `apps/${appName}/src`),
        '@components': path.resolve(__dirname, 'packages/common-components'),
        '@config': path.resolve(__dirname, 'packages/common-global-data'),
        '@styles': path.resolve(__dirname, 'packages/common-styles'),
        '@images': path.resolve(__dirname, 'packages/common-images'),
        '@helpers': path.resolve(__dirname, 'packages/common-helpers'),
        '@requests': path.resolve(__dirname, 'packages/common-requests'),
        '@stores': path.resolve(__dirname, 'packages/common-stores'),
        '@common-images': path.resolve(__dirname, 'packages/common-images'),
      },
    },
    base: base,
    server: {
      proxy: {
        '/auth': {
          target: 'http://localhost:3000',
          changeOrigin: true,
          rewrite: (path) => path.replace(/^\/auth/, ''),
        },
        '/intranet': {
          target: 'http://localhost:3001',
          changeOrigin: true,
          rewrite: (path) => path.replace(/^\/intranet/, ''),
        },
        '/edt': {
          target: 'http://localhost:3003',
          changeOrigin: true,
          rewrite: (path) => path.replace(/^\/edt/, ''),
        },
        '/correcto': {
          target: 'http://localhost:3004',
          changeOrigin: true,
          rewrite: (path) => path.replace(/^\/correcto/, ''),
        },
      },
    },
    test: {
      globals: true, // Utiliser les globales comme describe, it, etc.
      environment: 'jsdom', // Utiliser un environnement DOM pour les tests
      setupFiles: './vitest.setup.js', // Fichier de configuration pour Vitest
    },
  }
})
