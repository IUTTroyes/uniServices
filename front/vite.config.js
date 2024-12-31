import { defineConfig, loadEnv } from 'vite'
import vue from '@vitejs/plugin-vue'
import path from 'path'
import { createProxyMiddleware } from 'http-proxy-middleware'
import { PrimeVueResolver } from '@primevue/auto-import-resolver'
import Components from 'unplugin-vue-components/vite'

export default defineConfig(({ mode }) => {
  const env = loadEnv(mode, process.cwd(), '')
  const base = env.VITE_BASE || '/'
  const appName = env.VITE_SUB_PROJECT_NAME || ''

  return {
    optimizeDeps: {
      noDiscovery: true
    },
    plugins: [
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
        '@styles': path.resolve(__dirname, 'packages/common-styles'),
        '@helpers': path.resolve(__dirname, 'packages/common-helpers'),
        '@requests': path.resolve(__dirname, 'packages/common-requests'),
        '@stores': path.resolve(__dirname, 'packages/common-stores'),
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
      },
    },
  }
})
