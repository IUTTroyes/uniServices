import { defineConfig, loadEnv } from "vite";

export default defineConfig(({ mode }) => {
  const env = loadEnv(mode, __dirname, "");
  const apiUrl = env.VITE_API_URL || env.VITE_BASE_URL || "https://127.0.0.1:8000";

  return {
    server: {
      port: 3000,
      proxy: {
        "/auth": {
          target: "http://localhost:3001",
          ws: true,
          changeOrigin: true,
        },
        "/intranet": {
          target: "http://localhost:3002",
          ws: true,
          changeOrigin: true,
        },
        "/unifolio": {
          target: "http://localhost:3003",
          ws: true,
          changeOrigin: true,
        },
        "/edt": {
          target: "http://localhost:3004",
          ws: true,
          changeOrigin: true,
        },
        "/helpdesk": {
          target: "http://localhost:3005",
          ws: true,
          changeOrigin: true,
        },
        "/questionnaire": {
          target: "http://localhost:3006",
          ws: true,
          changeOrigin: true,
        },
        "/stage": {
          target: "http://localhost:3007",
          ws: true,
          changeOrigin: true,
        },
        "/api": {
          target: apiUrl,
          changeOrigin: true,
          secure: false,
        },
      },
    },
  };
});
