import NewAnneeUniv from "@/components/SuperAdministration/annee_univ/NewAnneeUniv.vue";
import ListAnneeUniv from "@/components/SuperAdministration/annee_univ/ListAnneeUniv.vue";
import EditAnneeUniv from "@/components/SuperAdministration/annee_univ/EditAnneeUniv.vue";
import AnneeUnivDiplomes from "@/components/SuperAdministration/annee_univ/AnneeUnivDiplomes.vue";
import SuperAdministrationView from "@/views/SuperAdministrationView.vue";

export default [
  {
    path: 'super-administration',
    component: SuperAdministrationView,
    meta: {
      breadcrumb: [{ label: 'Dashboard', route: '/' }, {
        label: 'Super-Administration',
        route: null,
        icon: 'pi pi-cog'
      }]
    },
  },

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
