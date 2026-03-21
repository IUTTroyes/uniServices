import {LayoutComponent} from "@components";

export default [
    {
        path: 'profil',
        component: () => import('@/views/ProfilView.vue'),
        meta: { breadcrumb: [{ label: 'Dashboard', route: '/' }, { label: 'Profil', route: null, icon: 'pi pi-user' }] },
    }
];
