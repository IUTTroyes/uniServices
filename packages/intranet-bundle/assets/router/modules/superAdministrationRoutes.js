import ListDepartement from "@/views/Super-Administration/Departement/ListDepartement.vue"
import SuperAdministrationView from "@/views/SuperAdministrationView.vue";
import NewDepartement from "@/views/Super-Administration/Departement/NewDepartement.vue";
import EditGroupeView from "@/views/Super-Administration/Groupe/EditGroupeView.vue";

export default [
  {
    path: 'super-administration',
    component: SuperAdministrationView,
    meta: {
      permission: 'isSuperAdmin',
      breadcrumb: [{ label: 'Dashboard', route: '/' }, {
        label: 'Super-Administration',
        route: null,
        icon: 'pi pi-cog'
      }]
    },
  },
  {
    path: 'super-administration/departement',
    component: NewDepartement,
    meta: {
      permission: 'isSuperAdmin',
      breadcrumb: [
        { label: 'Dashboard', route: '/' },
        { label: 'Super-Administration', route: '/intranet/super-administration' },
        {
          label: 'Nouveau département',
          route: null,
          icon: 'pi pi-wrench'
        }
      ]
    },
  },
  {
    path: 'super-administration/departements',
    component: ListDepartement,
    meta: {
      permission: 'isSuperAdmin',
      breadcrumb: [
        { label: 'Dashboard', route: '/' },
        { label: 'Super-Administration', route: '/intranet/super-administration' },
        {
          label: 'Départements',
          route: null,
          icon: 'pi pi-wrench'
        }
      ]
    },
  },
  {
    path: 'super-administration/groupe/:groupeId',
    name: 'groupe-edit',
    component: EditGroupeView,
    meta: {
      permission: 'isSuperAdmin',
      breadcrumb: [
        { label: 'Dashboard', route: '/' },
        { label: 'Super-Administration', route: '/intranet/super-administration' },
        {
          label: 'Édition du groupe',
          route: null,
          icon: 'pi pi-wrench'
        }
      ]
    },
  },
];
