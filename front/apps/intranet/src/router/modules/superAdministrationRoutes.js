import NewAnneeUniv from "@/views/Super-Administration/NewAnneeUniv.vue";
import ListAnneeUniv from "@/views/Super-Administration/ListAnneeUniv.vue";

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
];
