// router/modules/dashboardRoutes.js
export default [
    {
      path: '',
      component: () => import('@/views/Dashboard.vue'),
      name: 'Dashboard',
      //meta: { requiresAuth: true },
    },
  ];

