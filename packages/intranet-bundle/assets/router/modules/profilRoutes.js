import LayoutComponent from '@components/components/layout/AppLayout.vue';

export default [
    {
        path: 'profil',
        component: () => import('@/views/ProfilView.vue'),
        meta: { breadcrumb: [{ label: 'Dashboard', route: '/' }, { label: 'Profil', route: null, icon: 'pi pi-user' }] },
    }
];
