import NewAnneeUniv from "@/views/Super-Administration/NewAnneeUniv.vue";
import ListAnneeUniv from "@/views/Super-Administration/ListAnneeUniv.vue";
import EditAnneeUniv from "@/views/Super-Administration/EditAnneeUniv.vue";
import AnneeUnivDiplomes from "@/views/Super-Administration/AnneeUnivDiplomes.vue";

export default [
  {
    path: 'super-administration/annee-universitaire',
    component: NewAnneeUniv,
    meta: {
      breadcrumb: [
        { label: 'Dashboard', route: '/' },
        {
          label: 'Nouvelle Année Universitaire',
          route: null,
          icon: 'pi pi-wrench'
        }
      ]
    },
  },
  {
    path: 'super-administration/annees-universitaires',
    component: ListAnneeUniv,
    meta: {
      breadcrumb: [
        { label: 'Dashboard', route: '/' },
        {
          label: 'Années Universitaires',
          route: null,
          icon: 'pi pi-wrench'
        }
      ]
    },
  },
  {
    path: 'super-administration/annee-universitaire/:id/edit',
    component: EditAnneeUniv,
    meta: {
      breadcrumb: [
        { label: 'Dashboard', route: '/' },
        {
          label: 'Années Universitaires',
          route: '/super-administration/annees-universitaires',
          icon: 'pi pi-wrench'
        },
        {
          label: 'Modifier',
          route: null,
          icon: 'pi pi-pencil'
        }
      ]
    },
  },
  {
    path: 'super-administration/annee-universitaire/:id/diplomes',
    component: AnneeUnivDiplomes,
    meta: {
      breadcrumb: [
        { label: 'Dashboard', route: '/' },
        {
          label: 'Années Universitaires',
          route: '/super-administration/annees-universitaires',
          icon: 'pi pi-wrench'
        },
        {
          label: 'Diplômes',
          route: null,
          icon: 'pi pi-book'
        }
      ]
    },
  },
];
