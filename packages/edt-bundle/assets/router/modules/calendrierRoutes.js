export default [
    {
        path: 'calendrier',
        component: () => import('@/views/CalendrierView.vue'),
        name: 'calendrier',
        meta: { breadcrumb: [{ label: 'Dashboard', route: '/' }, { label: 'Calendrier', route: null, icon: 'pi pi-calendar' }] },
    },
];
