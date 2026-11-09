export default [
    {
        path: 'scolarite',
        component: () => import('@/views/Scolarite/ScolariteView.vue'),
        name: 'Scolarité',
        meta: { breadcrumb: [{ label: 'Dashboard', route: '/'}, { label: 'Scolarité', route: null, icon: 'pi pi-graduation-cap'}] },
    },
    {
        path: 'cahier-de-texte',
        component: () => import('@/views/Etudiants/CahierDeTexteView.vue'),
        name: 'Cahier de texte',
        meta: { breadcrumb: [{ label: 'Dashboard', route: '/'}, { label: 'Cahier de texte', route: null, icon: 'pi pi-book'}] },
    },
];
