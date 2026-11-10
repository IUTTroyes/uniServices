import { defineConfig } from 'vite';
import tailwindcss from '@tailwindcss/vite';
import path from 'path';
import { fileURLToPath } from 'url';

const ici = path.dirname(fileURLToPath(import.meta.url));

// Feuille de style unique des portfolios publiés (polices d'icônes incluses en base64).
export default defineConfig({
  plugins: [tailwindcss()],
  build: {
    outDir: path.resolve(ici, 'dist'),
    emptyOutDir: false,
    assetsInlineLimit: 1_000_000,
    cssCodeSplit: false,
    lib: { entry: path.resolve(ici, 'style.js'), formats: ['es'], fileName: () => 'style.tmp.js' },
    rollupOptions: { output: { assetFileNames: 'portfolio.css' } },
  },
});
