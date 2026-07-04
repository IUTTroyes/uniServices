import { defineConfig } from "vite";
import vue from "@vitejs/plugin-vue";
import path from "path";
import Components from "unplugin-vue-components/vite";
import { PrimeVueResolver } from "@primevue/auto-import-resolver";
import tailwindcss from "@tailwindcss/vite";
import { loadEnv } from "vite";

/**
 * Returns a Vite configuration customized for a bundle.
 * 
 * @param {string} bundleDir - The __dirname of the bundle calling this
 * @param {string} baseName - The URL base path / directory name in public (e.g. 'auth', 'questionnaire')
 * @param {object} [customConfig] - Optional custom config overrides
 */
export function getBaseConfig(bundleDir, baseName, customConfig = {}) {
  return defineConfig(({ mode }) => {
    const rootDir = path.resolve(bundleDir, "../../");
    const env = loadEnv(mode, rootDir, "");

    const apiUrl = env.VITE_API_URL || env.VITE_BASE_URL || "https://localhost:8000";
    process.env.VITE_API_URL = apiUrl;

    const baseConfig = {
      plugins: [
        vue(),
        tailwindcss(),
        Components({
          resolvers: [PrimeVueResolver()],
          dts: path.resolve(bundleDir, "assets/components.d.ts"),
        }),
      ],
      root: path.resolve(bundleDir, "assets"),
      base: `/${baseName}/`,
      build: {
        outDir: path.resolve(rootDir, `back/public/${baseName}`),
        emptyOutDir: true,
      },
      server: {
        proxy: {
          "/api": {
            target: apiUrl,
            changeOrigin: true,
            secure: false,
          },
        },
      },
      resolve: {
        alias: {
          "@": path.resolve(bundleDir, "assets"),
          "@types": path.resolve(rootDir, "shared/types"),
          "@components": path.resolve(rootDir, "shared/components"),
          "@config": path.resolve(rootDir, "shared/global-data"),
          "@styles": path.resolve(rootDir, "shared/styles"),
          "@images": path.resolve(rootDir, "shared/images"),
          "@helpers": path.resolve(rootDir, "shared/helpers"),
          "@requests": path.resolve(rootDir, "shared/requests"),
          "@stores": path.resolve(rootDir, "shared/stores"),
          "@utils": path.resolve(rootDir, "shared/utils"),
          "@composables": path.resolve(rootDir, "shared/composables"),
          "@dialogs": path.resolve(rootDir, "shared/dialogs"),
          "@common-images": path.resolve(rootDir, "shared/images"),
        },
      },
    };

    // Merge plugins
    const mergedPlugins = [
      ...baseConfig.plugins,
      ...(customConfig.plugins || []),
    ];

    // Merge aliases (supporting both array and object formats)
    let mergedAlias;
    if (Array.isArray(customConfig.resolve?.alias)) {
      // If custom is an array, compile base alias as an array and merge
      const baseAliasArray = Object.entries(baseConfig.resolve.alias).map(([find, replacement]) => ({ find, replacement }));
      mergedAlias = [...customConfig.resolve.alias];
      // Add base aliases that are not overridden by custom aliases
      baseAliasArray.forEach(baseAlias => {
        if (!mergedAlias.some(a => String(a.find) === String(baseAlias.find))) {
          mergedAlias.push(baseAlias);
        }
      });
    } else {
      // Standard object merge
      mergedAlias = {
        ...baseConfig.resolve.alias,
        ...(customConfig.resolve?.alias || {}),
      };
    }

    // Return merged config
    return {
      ...baseConfig,
      ...customConfig,
      plugins: mergedPlugins,
      resolve: {
        ...baseConfig.resolve,
        ...customConfig.resolve,
        alias: mergedAlias,
      },
      build: {
        ...baseConfig.build,
        ...customConfig.build,
      },
      server: {
        ...baseConfig.server,
        ...customConfig.server,
        proxy: {
          ...baseConfig.server.proxy,
          ...(customConfig.server?.proxy || {}),
        },
      },
    };
  });
}
