// vite.config.mjs
import { fileURLToPath, URL } from "node:url";
import { PrimeVueResolver } from "file:///Users/cyndel/Web/uniServices/front/node_modules/@primevue/auto-import-resolver/index.mjs";
import vue from "file:///Users/cyndel/Web/uniServices/front/node_modules/@vitejs/plugin-vue/dist/index.mjs";
import Components from "file:///Users/cyndel/Web/uniServices/front/node_modules/unplugin-vue-components/dist/vite.js";
import { defineConfig } from "file:///Users/cyndel/Web/uniServices/front/node_modules/vite/dist/node/index.js";
var __vite_injected_original_import_meta_url = "file:///Users/cyndel/Web/uniServices/front/apps/intranet/vite.config.mjs";
var vite_config_default = defineConfig({
  optimizeDeps: {
    noDiscovery: true
  },
  plugins: [
    vue(),
    Components({
      resolvers: [PrimeVueResolver()]
    })
  ],
  base: "/intranet/",
  // Base pour app2
  server: {
    host: "0.0.0.0",
    //
    port: 3001,
    // Port pour app2
    strictPort: true,
    cors: {
      origin: ["http://localhost:3001"],
      credentials: true
    }
  },
  resolve: {
    alias: {
      "@": fileURLToPath(new URL("./src", __vite_injected_original_import_meta_url))
    }
  }
});
export {
  vite_config_default as default
};
//# sourceMappingURL=data:application/json;base64,ewogICJ2ZXJzaW9uIjogMywKICAic291cmNlcyI6IFsidml0ZS5jb25maWcubWpzIl0sCiAgInNvdXJjZXNDb250ZW50IjogWyJjb25zdCBfX3ZpdGVfaW5qZWN0ZWRfb3JpZ2luYWxfZGlybmFtZSA9IFwiL1VzZXJzL2N5bmRlbC9XZWIvdW5pU2VydmljZXMvZnJvbnQvYXBwcy9pbnRyYW5ldFwiO2NvbnN0IF9fdml0ZV9pbmplY3RlZF9vcmlnaW5hbF9maWxlbmFtZSA9IFwiL1VzZXJzL2N5bmRlbC9XZWIvdW5pU2VydmljZXMvZnJvbnQvYXBwcy9pbnRyYW5ldC92aXRlLmNvbmZpZy5tanNcIjtjb25zdCBfX3ZpdGVfaW5qZWN0ZWRfb3JpZ2luYWxfaW1wb3J0X21ldGFfdXJsID0gXCJmaWxlOi8vL1VzZXJzL2N5bmRlbC9XZWIvdW5pU2VydmljZXMvZnJvbnQvYXBwcy9pbnRyYW5ldC92aXRlLmNvbmZpZy5tanNcIjtpbXBvcnQgeyBmaWxlVVJMVG9QYXRoLCBVUkwgfSBmcm9tICdub2RlOnVybCc7XG5cbmltcG9ydCB7IFByaW1lVnVlUmVzb2x2ZXIgfSBmcm9tICdAcHJpbWV2dWUvYXV0by1pbXBvcnQtcmVzb2x2ZXInO1xuaW1wb3J0IHZ1ZSBmcm9tICdAdml0ZWpzL3BsdWdpbi12dWUnO1xuaW1wb3J0IENvbXBvbmVudHMgZnJvbSAndW5wbHVnaW4tdnVlLWNvbXBvbmVudHMvdml0ZSc7XG5pbXBvcnQgeyBkZWZpbmVDb25maWcgfSBmcm9tICd2aXRlJztcblxuLy8gaHR0cHM6Ly92aXRlanMuZGV2L2NvbmZpZy9cbmV4cG9ydCBkZWZhdWx0IGRlZmluZUNvbmZpZyh7XG4gICAgb3B0aW1pemVEZXBzOiB7XG4gICAgICAgIG5vRGlzY292ZXJ5OiB0cnVlXG4gICAgfSxcbiAgICBwbHVnaW5zOiBbXG4gICAgICAgIHZ1ZSgpLFxuICAgICAgICBDb21wb25lbnRzKHtcbiAgICAgICAgICAgIHJlc29sdmVyczogW1ByaW1lVnVlUmVzb2x2ZXIoKV1cbiAgICAgICAgfSlcbiAgICBdLFxuICAgIGJhc2U6ICcvaW50cmFuZXQvJywgLy8gQmFzZSBwb3VyIGFwcDJcbiAgICBzZXJ2ZXI6IHtcbiAgICAgICAgaG9zdDogJzAuMC4wLjAnLCAvL1xuICAgICAgICBwb3J0OiAzMDAxLCAvLyBQb3J0IHBvdXIgYXBwMlxuICAgICAgICBzdHJpY3RQb3J0OiB0cnVlLFxuICAgICAgICBjb3JzOiB7XG4gICAgICAgICAgICBvcmlnaW46IFsnaHR0cDovL2xvY2FsaG9zdDozMDAxJ10sXG4gICAgICAgICAgICBjcmVkZW50aWFsczogdHJ1ZSxcbiAgICAgICAgfVxuICAgIH0sXG4gICAgcmVzb2x2ZToge1xuICAgICAgICBhbGlhczoge1xuICAgICAgICAgICAgJ0AnOiBmaWxlVVJMVG9QYXRoKG5ldyBVUkwoJy4vc3JjJywgaW1wb3J0Lm1ldGEudXJsKSlcbiAgICAgICAgfVxuICAgIH1cbn0pO1xuIl0sCiAgIm1hcHBpbmdzIjogIjtBQUF1VSxTQUFTLGVBQWUsV0FBVztBQUUxVyxTQUFTLHdCQUF3QjtBQUNqQyxPQUFPLFNBQVM7QUFDaEIsT0FBTyxnQkFBZ0I7QUFDdkIsU0FBUyxvQkFBb0I7QUFMOEssSUFBTSwyQ0FBMkM7QUFRNVAsSUFBTyxzQkFBUSxhQUFhO0FBQUEsRUFDeEIsY0FBYztBQUFBLElBQ1YsYUFBYTtBQUFBLEVBQ2pCO0FBQUEsRUFDQSxTQUFTO0FBQUEsSUFDTCxJQUFJO0FBQUEsSUFDSixXQUFXO0FBQUEsTUFDUCxXQUFXLENBQUMsaUJBQWlCLENBQUM7QUFBQSxJQUNsQyxDQUFDO0FBQUEsRUFDTDtBQUFBLEVBQ0EsTUFBTTtBQUFBO0FBQUEsRUFDTixRQUFRO0FBQUEsSUFDSixNQUFNO0FBQUE7QUFBQSxJQUNOLE1BQU07QUFBQTtBQUFBLElBQ04sWUFBWTtBQUFBLElBQ1osTUFBTTtBQUFBLE1BQ0YsUUFBUSxDQUFDLHVCQUF1QjtBQUFBLE1BQ2hDLGFBQWE7QUFBQSxJQUNqQjtBQUFBLEVBQ0o7QUFBQSxFQUNBLFNBQVM7QUFBQSxJQUNMLE9BQU87QUFBQSxNQUNILEtBQUssY0FBYyxJQUFJLElBQUksU0FBUyx3Q0FBZSxDQUFDO0FBQUEsSUFDeEQ7QUFBQSxFQUNKO0FBQ0osQ0FBQzsiLAogICJuYW1lcyI6IFtdCn0K
