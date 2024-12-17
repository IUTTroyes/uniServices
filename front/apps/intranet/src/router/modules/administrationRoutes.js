import {LayoutComponent} from "common-components";

export default [
    {
        path: 'administration',
        component: () => import('@/views/AdministrationView.vue'),
        meta: { breadcrumb: [{ label: 'Dashboard', route: '/' }, { label: 'Administration', route: null, icon: 'pi pi-wrench' }] },
    },
    {
        path: 'administration/pn',
        component: () => import('@/components/administration/Pn.vue'),
        meta: { breadcrumb: [{ label: 'Dashboard', route: '/' }, { label: 'Administration', route: '/administration' }, { label: 'PN', route: null }] },
    }
];
