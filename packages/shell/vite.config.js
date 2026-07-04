import { getBaseConfig } from "../../shared/vite.config.base.js";
import path from "path";

const rootDir = path.resolve(__dirname, "../../");

// The Vue Shell builds to back/public/app and runs with base path /app/
// During the migration, it also acts as a gateway proxy on port 3000 for non-migrated bundles.
export default getBaseConfig(__dirname, "app", {
  resolve: {
    alias: [
      {
        find: /^\@\/(.*)/,
        replacement: "$1",
        customResolver(source, importer) {
          if (importer) {
            // Convert to absolute path to guarantee matcher works regardless of relative resolution
            const absoluteImporter = path.isAbsolute(importer)
              ? importer
              : path.resolve(rootDir, "packages/shell", importer);
            
            const match = absoluteImporter.match(/\/packages\/([^\/]+)\/assets\//);
            if (match) {
              const bundleName = match[1];
              const resolvedPath = path.resolve(rootDir, `packages/${bundleName}/assets`, source);
              // Let Vite resolve extensions (e.g. .ts, .js, .vue) and index files
              return this.resolve(resolvedPath, importer, { skipSelf: true });
            }
          }
          // Default fallback to shell assets resolved by Vite
          return this.resolve(path.resolve(__dirname, "assets", source), importer, { skipSelf: true });
        }
      },
      // Rest of standard aliases
      { find: "@types", replacement: path.resolve(rootDir, "shared/types") },
      { find: "@components", replacement: path.resolve(rootDir, "shared/components") },
      { find: "@config", replacement: path.resolve(rootDir, "shared/global-data") },
      { find: "@styles", replacement: path.resolve(rootDir, "shared/styles") },
      { find: "@images", replacement: path.resolve(rootDir, "shared/images") },
      { find: "@helpers", replacement: path.resolve(rootDir, "shared/helpers") },
      { find: "@requests", replacement: path.resolve(rootDir, "shared/requests") },
      { find: "@stores", replacement: path.resolve(rootDir, "shared/stores") },
      { find: "@utils", replacement: path.resolve(rootDir, "shared/utils") },
      { find: "@composables", replacement: path.resolve(rootDir, "shared/composables") },
      { find: "@dialogs", replacement: path.resolve(rootDir, "shared/dialogs") },
      { find: "@common-images", replacement: path.resolve(rootDir, "shared/images") },
    ]
  },
  server: {
    port: 3000
  }
});
