export default [
    {
        path: 'cahier-de-texte',
        component: () => import('@/views/Etudiants/CahierDeTexteView.vue'),
        name: 'Cahier de texte',
        meta: { breadcrumb: [{ label: 'Dashboard', route: '/'}, { label: 'Cahier de texte', route: null, icon: 'pi pi-book'}] },
    },
];
