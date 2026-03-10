import NewAnneeUniv from "@/views/Super-Administration/NewAnneeUniv.vue";

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
];
