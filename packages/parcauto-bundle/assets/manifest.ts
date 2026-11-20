export default {
  name: 'parcauto',
  routes: [
    {
      path: '/parcauto',
      name: 'parcauto-home',
      component: () => import('./App.vue'),
      meta: { title: 'Parcauto' },
    },
  ],
};
