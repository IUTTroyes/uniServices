import { defineConfig, loadEnv } from 'vite'
import { fileURLToPath } from 'url'
import vue from '@vitejs/plugin-vue'
import path from 'path'
import { createProxyMiddleware } from 'http-proxy-middleware'
import { PrimeVueResolver } from '@primevue/auto-import-resolver'
import Components from 'unplugin-vue-components/vite'

console.log('toto')
export default defineConfig(({ mode }) => {
  const env = loadEnv(mode, process.cwd(), '')
  const base = env.VITE_BASE || '/'
  const appName = env.VITE_SUB_PROJECT_NAME || ''

  if (!appName) {
    throw new Error('VITE_SUB_PROJECT_NAME environment variable is not set!')
  }
console.log('toto')
  console.log(path.resolve(__dirname, `apps/${appName}/src`));

  let alias = {
    // '@': fileURLToPath(new URL(`./apps/${appName}/src`, import.meta.url)),
    '@': path.resolve(__dirname, `./apps/${appName}/src`),
    '@components': path.resolve(__dirname, './packages/common-components'),
    '@styles': path.resolve(__dirname, './packages/common-styles'),
    '@helpers': path.resolve(__dirname, './packages/common-helpers'),
    '@stores': path.resolve(__dirname, './packages/common-stores'),
  }

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
      alias: alias,
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
