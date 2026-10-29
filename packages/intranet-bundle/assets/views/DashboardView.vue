<script setup>
import { computed } from 'vue';
import { HeaderComponent, PermissionGuard } from '@components';
import { useUsersStore } from '@stores';
import IntranetDashboardSection from '../components/IntranetDashboardSection.vue';
import EnseignantDashboardSection from '../components/Dashboard/EnseignantDashboardSection.vue';
import AbsencesDashboardSection from '../components/Dashboard/AbsencesDashboardSection.vue';
import NotesDashboardSection from '../components/Dashboard/NotesDashboardSection.vue';
import EdusignDashboardSection from '../components/Dashboard/EdusignDashboardSection.vue';

const userStore = useUsersStore();
const departmentLabel = computed(() => userStore.departementDefaut?.libelle ?? 'Votre espace de travail');
const actions = [
  {
    "id": "agenda",
    "label": "Agenda",
    "description": "Consulter votre emploi du temps et vos séances.",
    "icon": "pi pi-calendar",
    "to": "/intranet/agenda",
    "permission": "isPersonnel",
    "application": null
  },
  {
    "id": "cours",
    "label": "Plans de cours",
    "description": "Préparer et retrouver vos supports pédagogiques.",
    "icon": "pi pi-book",
    "to": "/intranet/plan-de-cours",
    "permission": "isEnseignant",
    "application": null
  },
  {
    "id": "trombinoscope",
    "label": "Trombinoscope",
    "description": "Retrouver les étudiants et les équipes.",
    "icon": "pi pi-users",
    "to": "/intranet/trombinoscope",
    "permission": "isPersonnel",
    "application": null
  },
  {
    "id": "formations",
    "label": "Organisation des formations",
    "description": "Accéder aux années, semestres et outils pédagogiques.",
    "icon": "pi pi-sitemap",
    "to": "/intranet/administration",
    "permission": "canViewAdministration",
    "application": null
  },
  {
    "id": "etudiants",
    "label": "Dossiers étudiants",
    "description": "Retrouver les dossiers et les informations de scolarité.",
    "icon": "pi pi-graduation-cap",
    "to": "/intranet/administration/etudiants",
    "permission": "canViewAdministration",
    "application": null
  },
  {
    "id": "personnels",
    "label": "Équipes du département",
    "description": "Retrouver les personnels et leurs affectations.",
    "icon": "pi pi-id-card",
    "to": "/intranet/administration/personnels",
    "permission": "isDirectionDepartement",
    "application": null
  },
  {
    "id": "previsionnel",
    "label": "Services prévisionnels",
    "description": "Consulter l’organisation des services d’enseignement.",
    "icon": "pi pi-chart-bar",
    "to": "/intranet/administration/previsionnel",
    "permission": "isDirectionDepartement",
    "application": null
  },
  {
    "id": "configuration",
    "label": "Configuration",
    "description": "Gérer les paramètres et les accès de l’établissement.",
    "icon": "pi pi-cog",
    "to": "/auth/configuration",
    "permission": "SUPER_ADMIN",
    "application": null
  },
  {
    "id": "scolarite",
    "label": "Ma scolarité",
    "description": "Retrouver vos informations de scolarité.",
    "icon": "pi pi-graduation-cap",
    "to": "/intranet/scolarite",
    "permission": "isEtudiant"
  },
  {
    "id": "agenda-etudiant",
    "label": "Mon agenda",
    "description": "Consulter votre emploi du temps.",
    "icon": "pi pi-calendar",
    "to": "/intranet/agenda",
    "permission": "isEtudiant"
  },
  {
    "id": "cahier",
    "label": "Cahier de texte",
    "description": "Retrouver le suivi de vos enseignements.",
    "icon": "pi pi-book",
    "to": "/intranet/cahier-de-texte",
    "permission": "isEtudiant"
  }
];
</script>

<template>
  <div class="flex flex-col gap-6 pb-8">
    <HeaderComponent icon="pi pi-desktop" titre="Intranet"
                     description="Retrouvez vos outils et le suivi de vos activités." :show-back="false" />
    <section aria-labelledby="workspace-actions">
      <div class="mb-4">
        <p class="m-0! text-sm text-muted-color">{{ departmentLabel }}</p>
        <h2 id="workspace-actions" class="mt-1! mb-0! text-xl!">Vos accès métier</h2>
      </div>
      <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
        <template v-for="action in actions" :key="action.id">
          <PermissionGuard :permission="action.permission">
            <RouterLink v-if="!action.application || userStore.applications.includes(action.application)"
                        :to="action.to" class="business-access card p-5! mb-0! flex items-start gap-4">
              <span class="flex items-center justify-center w-10 h-10 shrink-0 rounded-xl bg-primary-50 text-primary-600 dark:bg-primary-950 dark:text-primary-300">
                <i :class="action.icon" aria-hidden="true" />
              </span>
              <span class="flex flex-col gap-1 flex-1 min-w-0">
                <span class="font-semibold">{{ action.label }}</span>
                <span class="text-sm text-muted-color">{{ action.description }}</span>
              </span>
              <i class="pi pi-arrow-right text-muted-color text-xs! mt-3" aria-hidden="true" />
            </RouterLink>
          </PermissionGuard>
        </template>
      </div>
    </section>

    <PermissionGuard permission="isEnseignant">
      <IntranetDashboardSection section="activite" title="Votre journée" icon="pi pi-calendar" />
    </PermissionGuard>

    <p class="m-0! text-xs text-muted-color flex items-center gap-2"><i class="pi pi-info-circle" aria-hidden="true" />Les enseignements, rappels, messages et indicateurs de suivi utilisent des données de démonstration.</p>
    <PermissionGuard :permission="userStore.isRespParcours">
      <IntranetDashboardSection section="parcours" title="Les diplômes dont vous êtes responsable" icon="pi pi-compass" />
    </PermissionGuard>
    <PermissionGuard permission="isEnseignant">
      <EnseignantDashboardSection />
    </PermissionGuard>
    <PermissionGuard :permission="['isAbsence', 'isChefDepartement', 'isDirecteurEtudes']">
      <AbsencesDashboardSection />
    </PermissionGuard>
    <PermissionGuard :permission="['isNote', 'isChefDepartement', 'isDirecteurEtudes']">
      <NotesDashboardSection />
    </PermissionGuard>
    <PermissionGuard permission="isEdusign">
      <EdusignDashboardSection />
    </PermissionGuard>
    <PermissionGuard :permission="['isChefDepartement', 'isDirecteurEtudes', 'isScolarite', 'isAssistant']">
      <IntranetDashboardSection section="formations" title="Les formations du département" icon="pi pi-graduation-cap" />
    </PermissionGuard>
    <PermissionGuard permission="isDirectionIut">
      <IntranetDashboardSection section="iut" title="Vos départements de direction" icon="pi pi-building" />
    </PermissionGuard>
  </div>
</template>

<style scoped>
.business-access { border: 1px solid var(--p-surface-200); transition: border-color .15s, box-shadow .15s; }
.business-access:hover, .business-access:focus-visible { border-color: var(--p-primary-color); box-shadow: 0 2px 8px color-mix(in srgb, var(--p-primary-color) 12%, transparent); }
:global(.app-dark) .business-access { border-color: var(--p-surface-700); }
</style>
