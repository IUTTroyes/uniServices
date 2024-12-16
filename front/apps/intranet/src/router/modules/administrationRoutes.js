import {LayoutComponent} from "common-components";

export default [
    {
        path: 'administration',
        component: () => import('@/views/AdministrationView.vue'),
        meta: { breadcrumb: [{ label: 'Dashboard', route: '/' }, { label: 'Administration', route: null, icon: 'pi pi-wrench' }] },
    }
];
