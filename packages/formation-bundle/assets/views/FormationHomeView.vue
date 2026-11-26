<script setup>
import { computed, markRaw, onMounted, ref } from 'vue';
import {
  AcademicCapIcon,
  BookOpenIcon,
  BuildingOffice2Icon,
  CalendarDaysIcon,
  ListBulletIcon,
  TagIcon,
  UserPlusIcon,
  UsersIcon,
} from '@heroicons/vue/24/outline';
import { Card, EmptyState, ErrorState, HeaderComponent, ListSkeleton, QuickActionCard } from '@components';
import { getAnneesService } from '@requests';
import { useUsersStore } from '@stores';
import { hasPermission } from '@utils/permissions';

const userStore = useUsersStore();
const annees = ref([]);
const isLoading = ref(true);
const hasError = ref(false);

const action = (title, description, icon, to, color, permission = null, buttonLabel = 'Ouvrir') => ({
  title, description, icon: markRaw(icon), to, color, permission, buttonLabel,
});

// Chaque groupe suit l'ordre de mise en place.
const groupes = [
  {
    title: 'Établissement',
    subtitle: 'Le cadre commun à toutes les formations',
    icon: 'pi pi-building',
    color: 'indigo',
    actions: [
      action('Départements', 'Créer et organiser les départements', BuildingOffice2Icon, '/formation/departements', 'purple', 'SUPER_ADMIN'),
      action('Types de diplômes', 'BUT, licences et autres diplômes', TagIcon, '/formation/types-diplomes', 'purple', 'SUPER_ADMIN'),
      action('Années universitaires', 'Années et diplômes ouverts', CalendarDaysIcon, '/auth/configuration/annees-universitaires', 'purple', 'isReferent'),
    ],
  },
  {
    title: 'Maquettes',
    subtitle: 'Ce que les étudiants doivent acquérir',
    icon: 'pi pi-book',
    color: 'blue',
    actions: [
      action('Référentiels de compétences', 'Compétences, niveaux et apprentissages critiques', ListBulletIcon, '/formation/referentiels-competences', 'blue'),
      action('Programmes (PN)', 'Années, semestres et enseignements', BookOpenIcon, '/formation/pn', 'blue'),
    ],
  },
  {
    title: 'Personnes',
    subtitle: 'Les étudiants et les équipes',
    icon: 'pi pi-users',
    color: 'emerald',
    actions: [
      action('Ajouter des étudiants', 'Import par fichier', UserPlusIcon, '/formation/etudiant/ajout', 'green', null, 'Importer'),
      action('Étudiants', 'Liste, scolarité et accès aux applications', AcademicCapIcon, '/formation/etudiants', 'green'),
      action('Personnels', 'Équipe du département', UsersIcon, '/formation/personnels', 'green'),
    ],
  },
];

const groupesVisibles = computed(() => groupes
  .map((groupe) => ({ ...groupe, actions: groupe.actions.filter((a) => !a.permission || hasPermission(a.permission)) }))
  .filter((groupe) => groupe.actions.length));

const description = computed(() => {
  const departement = userStore.departementDefaut?.libelle;

  return departement
    ? `Maquettes, étudiants et personnels du département ${departement}`
    : 'Diplômes, maquettes, compétences, étudiants et personnels';
});

const chargerAnnees = async () => {
  isLoading.value = true;
  hasError.value = false;
  try {
    const departement = userStore.departementDefaut?.id;
    annees.value = departement ? await getAnneesService({ departement, actif: true }) : [];
  } catch (error) {
    console.error('Erreur lors du chargement des années :', error);
    hasError.value = true;
  } finally {
    isLoading.value = false;
  }
};

onMounted(chargerAnnees);
</script>

<template>
  <HeaderComponent titre="Formations" :description="description" icon="pi pi-sitemap" color="blue" :show-back="false" />

  <div class="flex flex-col gap-6">
    <div class="grid grid-cols-1 items-start gap-6 xl:grid-cols-3">
      <Card
        v-for="groupe in groupesVisibles"
        :key="groupe.title"
        :title="groupe.title"
        :subtitle="groupe.subtitle"
        :icon="groupe.icon"
        :color="groupe.color"
      >
        <div class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-1">
          <QuickActionCard
            v-for="item in groupe.actions"
            :key="item.to"
            :title="item.title"
            :description="item.description"
            :icon="item.icon"
            :color="item.color"
            :button-label="item.buttonLabel"
            :to="item.to"
          />
        </div>
      </Card>
    </div>

    <Card
      title="Années de formation"
      subtitle="Étudiants et groupes, année par année"
      icon="pi pi-calendar"
      color="amber"
      :badge="annees.length ? String(annees.length) : ''"
    >
      <ListSkeleton v-if="isLoading" />
      <ErrorState v-else-if="hasError" />
      <DataTable v-else :value="annees" data-key="id" class="ui-table-compact" striped-rows>
        <Column field="libelle" header="Année" sortable />
        <Column header="Formation">
          <template #body="{ data }">
            <Tag
              :value="data.opt?.alternance ? 'Alternance' : 'Formation initiale'"
              :severity="data.opt?.alternance ? 'info' : 'secondary'"
            />
          </template>
        </Column>
        <Column header="Actions" :pt="{ columnHeaderContent: { class: 'justify-end' } }">
          <template #body="{ data }">
            <div class="flex flex-wrap justify-end gap-1">
              <Button as="router-link" :to="`/formation/annee/${data.id}/etudiants`" label="Étudiants" icon="pi pi-graduation-cap" size="small" text />
              <Button as="router-link" :to="`/formation/annee/${data.id}/groupes/structure`" label="Structure des groupes" icon="pi pi-sitemap" size="small" text />
              <Button as="router-link" :to="`/formation/annee/${data.id}/groupes/affectation`" label="Affectation" icon="pi pi-users" size="small" text />
            </div>
          </template>
        </Column>
        <template #empty>
          <EmptyState
            title="Aucune année de formation"
            description="Les années apparaissent une fois le programme du diplôme créé."
            icon="pi pi-calendar"
            compact
          />
        </template>
      </DataTable>
    </Card>
  </div>
</template>
