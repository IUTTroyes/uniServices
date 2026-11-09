export default [
  {
    path: 'configuration',
    name: 'intranet-configuration',
    component: () => import('@/views/Administration/Configuration/IntranetConfigurationView.vue'),
    meta: {
      permission: 'isChefDepartement',
      breadcrumb: [
        { label: 'Dashboard', route: '/' },
        { label: 'Administration', route: '/intranet/administration' },
        { label: 'Configuration', route: null, icon: 'pi pi-sliders-h' }
      ]
    }
  },
];
