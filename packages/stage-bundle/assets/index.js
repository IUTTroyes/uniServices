import dashboardRoutes from './router/modules/dashboardRoutes.js';
import Logo from "@images/logo/logo_intranet_iut_troyes.svg";
import { LayoutComponent } from '@components';
import { hasPermission } from '@utils';

// Define the menu structure
const intranetMenu = [
    {
        items: [
            { label: 'Tableau de Bord', icon: 'pi pi-fw pi-home', to: '/stage/' },
            { label: 'Espace Étudiant', icon: 'pi pi-fw pi-user', to: '/stage/etudiant', permission: 'isEtudiant' },
            { label: 'Demande de Convention', icon: 'pi pi-fw pi-file-edit', to: '/stage/demande', permission: 'isEtudiant' },
            { label: 'Espace Tuteur', icon: 'pi pi-fw pi-users', to: '/stage/enseignant', permission: 'isPersonnel' },
            { label: 'Espace Responsable', icon: 'pi pi-fw pi-shield', to: '/stage/responsable', permission: 'isPersonnel' },
            { label: 'Modèles de Convention', icon: 'pi pi-fw pi-cog', to: '/stage/admin/templates', permission: 'isSuperAdmin' },
        ]
    }
];

const appName = 'Stage';

export default {
  name: 'stage',
  routes: [
    {
      path: '/stage',
      component: LayoutComponent,
      props: route => {
        // Process menu items and check permissions every time the component is rendered
        const processedMenu = intranetMenu.map(category => {
          const processedItems = category.items.map(item => {
            if (item.permission) {
              return {
                ...item,
                visible: hasPermission(item.permission)
              };
            }
            return item;
          });

          return {
            ...category,
            items: processedItems
          };
        });

        let breadcrumbItems = route.meta.breadcrumb || [];
        if (typeof route.meta.breadcrumb === 'function') {
          try {
            breadcrumbItems = route.meta.breadcrumb(route) || [];
          } catch (e) {
            console.error('Error while evaluating breadcrumb function for route', route.name, e);
            breadcrumbItems = [];
          }
        }

        return {
          menuItems: processedMenu,
          logoUrl: Logo,
          appName: appName,
          breadcrumbItems
        };
      },
      children: [
        ...dashboardRoutes
      ]
    }
  ]
};
