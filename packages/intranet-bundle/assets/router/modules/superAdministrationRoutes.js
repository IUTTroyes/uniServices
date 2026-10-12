import ListDepartement from "@/views/Super-Administration/Departement/ListDepartement.vue";
import SuperAdministrationView from "@/views/SuperAdministrationView.vue";
import NewDepartement from "@/views/Super-Administration/Departement/NewDepartement.vue";
import EditGroupeView from "@/views/Super-Administration/Groupe/EditGroupeView.vue";
import ListBac from "@/views/Super-Administration/Bac/ListBac.vue";
import NewBac from "@/views/Super-Administration/Bac/NewBac.vue";
import ListTypeDiplome from "@/views/Super-Administration/TypeDiplome/ListTypeDiplome.vue";
import NewTypeDiplome from "@/views/Super-Administration/TypeDiplome/NewTypeDiplome.vue";
import ListCalendrier from "@/views/Super-Administration/Calendrier/ListCalendrier.vue";
import NewCalendrier from "@/views/Super-Administration/Calendrier/NewCalendrier.vue";
import ListTypeHrs from "@/views/Super-Administration/TypeHrs/ListTypeHrs.vue";
import NewTypeHrs from "@/views/Super-Administration/TypeHrs/NewTypeHrs.vue";

export default [
  {
    path: 'super-administration',
    component: SuperAdministrationView,
    meta: {
      permission: 'SUPER_ADMIN',
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
      permission: 'SUPER_ADMIN',
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
      permission: 'SUPER_ADMIN',
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
    path: 'super-administration/bac',
    name: 'super-admin-bac-new',
    component: NewBac,
    meta: {
      permission: 'SUPER_ADMIN',
      breadcrumb: [
        { label: 'Dashboard', route: '/' },
        { label: 'Super-Administration', route: '/intranet/super-administration' },
        {
          label: 'Nouveau type de bac',
          route: null,
          icon: 'pi pi-id-card'
        }
      ]
    },
  },
  {
    path: 'super-administration/bacs',
    name: 'super-admin-bacs',
    component: ListBac,
    meta: {
      permission: 'SUPER_ADMIN',
      breadcrumb: [
        { label: 'Dashboard', route: '/' },
        { label: 'Super-Administration', route: '/intranet/super-administration' },
        {
          label: 'Types de bacs',
          route: null,
          icon: 'pi pi-id-card'
        }
      ]
    },
  },
  {
    path: 'super-administration/type-diplome',
    name: 'super-admin-type-diplome-new',
    component: NewTypeDiplome,
    meta: {
      permission: 'SUPER_ADMIN',
      breadcrumb: [
        { label: 'Dashboard', route: '/' },
        { label: 'Super-Administration', route: '/intranet/super-administration' },
        {
          label: 'Nouveau type de diplôme',
          route: null,
          icon: 'pi pi-graduation-cap'
        }
      ]
    },
  },
  {
    path: 'super-administration/types-diplomes',
    name: 'super-admin-types-diplomes',
    component: ListTypeDiplome,
    meta: {
      permission: 'SUPER_ADMIN',
      breadcrumb: [
        { label: 'Dashboard', route: '/' },
        { label: 'Super-Administration', route: '/intranet/super-administration' },
        {
          label: 'Types de diplômes',
          route: null,
          icon: 'pi pi-graduation-cap'
        }
      ]
    },
  },
  {
    path: 'super-administration/calendrier',
    name: 'super-admin-calendrier-new',
    component: NewCalendrier,
    meta: {
      permission: 'SUPER_ADMIN',
      breadcrumb: [
        { label: 'Dashboard', route: '/' },
        { label: 'Super-Administration', route: '/intranet/super-administration' },
        {
          label: 'Nouvelle semaine de calendrier',
          route: null,
          icon: 'pi pi-calendar'
        }
      ]
    },
  },
  {
    path: 'super-administration/calendriers',
    name: 'super-admin-calendriers',
    component: ListCalendrier,
    meta: {
      permission: 'SUPER_ADMIN',
      breadcrumb: [
        { label: 'Dashboard', route: '/' },
        { label: 'Super-Administration', route: '/intranet/super-administration' },
        {
          label: 'Calendrier universitaire',
          route: null,
          icon: 'pi pi-calendar'
        }
      ]
    },
  },
  {
    path: 'super-administration/type-hrs',
    name: 'super-admin-type-hrs-new',
    component: NewTypeHrs,
    meta: {
      permission: 'SUPER_ADMIN',
      breadcrumb: [
        { label: 'Dashboard', route: '/' },
        { label: 'Super-Administration', route: '/intranet/super-administration' },
        {
          label: 'Nouveau type d\'heures',
          route: null,
          icon: 'pi pi-clock'
        }
      ]
    },
  },
  {
    path: 'super-administration/types-hrs',
    name: 'super-admin-types-hrs',
    component: ListTypeHrs,
    meta: {
      permission: 'SUPER_ADMIN',
      breadcrumb: [
        { label: 'Dashboard', route: '/' },
        { label: 'Super-Administration', route: '/intranet/super-administration' },
        {
          label: 'Types d\'heures (HRS)',
          route: null,
          icon: 'pi pi-clock'
        }
      ]
    },
  },
  {
    path: 'super-administration/groupe/:groupeId',
    name: 'groupe-edit',
    component: EditGroupeView,
    meta: {
      permission: 'SUPER_ADMIN',
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
