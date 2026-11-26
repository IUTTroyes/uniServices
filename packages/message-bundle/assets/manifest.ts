import Logo from "@images/logo/logo_intranet_iut_troyes.svg";
import LayoutComponent from '@components/components/layout/AppLayout.vue';
import { registerWidgets } from './widgets/registerWidgets';
import { getApplicationMeta } from '@utils/applicationColors';

const app = getApplicationMeta('message');

const messageMenu = {
  label: app.name,
  icon: `${app.icon} pi-fw`,
  items: [
    { label: 'Boîte de réception', icon: 'pi pi-fw pi-inbox', to: '/message' },
    { label: 'Messages envoyés', icon: 'pi pi-fw pi-send', to: '/message/envoyes', permission: 'isPersonnel' },
    { label: 'Rédiger un message', icon: 'pi pi-fw pi-pencil', to: '/message/nouveau', permission: 'isPersonnel' },
  ],
};

export default {
  name: 'message',
  registerWidgets,
  routes: [
    {
      path: '/message',
      component: LayoutComponent,
      props: (route) => ({
        logoUrl: Logo,
        appName: app.name,
        breadcrumbItems: typeof route.meta.breadcrumb === 'function'
          ? route.meta.breadcrumb(route)
          : (route.meta.breadcrumb || [{ label: app.name, route: null, icon: 'pi pi-inbox' }]),
      }),
      children: [
        {
          path: '',
          name: 'message-inbox',
          component: () => import('./views/MessagesInboxView.vue'),
          meta: {
            breadcrumb: [{ label: app.name, route: null, icon: 'pi pi-inbox' }],
          },
        },
        {
          path: 'nouveau',
          name: 'message-create',
          component: () => import('./views/MessageCreateView.vue'),
          meta: {
            permission: 'isPersonnel',
            breadcrumb: [
              { label: app.name, route: '/message', icon: 'pi pi-inbox' },
              { label: 'Nouveau message', route: null, icon: 'pi pi-pencil' },
            ],
          },
        },
        {
          path: 'envoyes',
          name: 'message-sent',
          component: () => import('./views/MessagesSentView.vue'),
          meta: {
            permission: 'isPersonnel',
            breadcrumb: [
              { label: app.name, route: '/message', icon: 'pi pi-inbox' },
              { label: 'Messages envoyés', route: null, icon: 'pi pi-send' },
            ],
          },
        },
        {
          path: ':id',
          name: 'message-detail',
          component: () => import('./views/MessageDetailView.vue'),
          meta: {
            breadcrumb: [
              { label: app.name, route: '/message', icon: 'pi pi-inbox' },
              { label: 'Lecture du message', route: null },
            ],
          },
        },
      ],
    },
  ],
  menu: messageMenu,
};

