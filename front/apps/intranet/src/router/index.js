// router/index.js
import { createRouter, createWebHistory } from 'vue-router';
import dashboardRoutes from './modules/dashboardRoutes';
import previsionnelRoutes from './modules/previsionnelRoutes';
// import userRoutes from './modules/userRoutes';
// import settingsRoutes from './modules/settingsRoutes';

const routes = [
  ...dashboardRoutes,
  ...previsionnelRoutes,
  // ...userRoutes,
  // ...settingsRoutes,
];

const router = createRouter({
  history: createWebHistory('/intranet/'), // Base commune
  routes,
});

export default router;
