<script setup>

import { onMounted, ref, computed } from "vue";
import { getDepartementSemestresService, getAnneesService } from "@requests";
import {ErrorView, ListSkeleton} from "@components";
import { useUsersStore, useSemestreStore } from "@stores";

const userStore = useUsersStore();
const selectedSemestre = ref(null);
const isLoading = ref(true);
const hasError = ref(false);
const anneesGrouped = ref({ fi: [], fc: [] });

const panelMenuItems = computed(() => {
  if (!selectedSemestre.value) return []
  return [
    {
      label: 'Étudiants', icon: 'pi pi-user', command: () => {}, items: [
        { label: 'Liste des étudiants', icon: 'pi pi-list', command: () => {} },
        { label: 'Ajouter des étudiants', icon: 'pi pi-plus-circle', command: () => {} },
      ]
    },
    {
      label: 'Groupes', icon: 'pi pi-users', command: () => {}, items: [
        {
          label: 'Composition des groupes', icon: 'pi pi-list',
          route: '/administration/semestre/' + selectedSemestre.value.id + '/groupes/affectation',
        },
        {
          label: 'Structure des groupes', icon: 'pi pi-cog', route: '/administration/semestre/' + selectedSemestre.value.id + '/groupes/structure',
        }
      ]
    },
    {
      label: 'Absences', icon: 'pi pi-calendar', command: () => {}, items: [
        {
          label: 'Liste des absences', icon: 'pi pi-list',
          route: '/administration/semestre/' + selectedSemestre.value.id + '/absences/liste',
        },
        {
          label: 'Liste des justificatifs', icon: 'pi pi-folder-open',
          route: '/administration/semestre/' + selectedSemestre.value.id + '/justificatifs-absences/liste',
        },
        { label: 'Suivi des pointages de présence', icon: 'pi pi-eye', command: () => {} },
      ]
    },
    {
      label: 'Notes et Évaluations', icon: 'pi pi-book', command: () => {}, items: [
        {
          label: 'Liste des notes', icon: 'pi pi-list',
          route: '/administration/semestre/' + selectedSemestre.value.id + '/evaluations/liste'
        },
        { label: 'Gestion des évaluations', icon: 'pi pi-cog', command: () => {} },
        {
          label: 'Demandes de rattrapages', icon: 'pi pi-history',
          route: '/administration/semestre/' + selectedSemestre.value.id + '/rattrapages/liste' },
        { label: 'Modalités du contrôle continu', icon: 'pi pi-map',
          route: '/administration/semestre/' + selectedSemestre.value.id + '/mccc/liste'
        },
      ]
    },
    {
      label: 'Fin de semestre', icon: 'pi pi-check', command: () => {}, items: [
        { label: 'Préparation de la sous-commission', icon: 'pi pi-calculator',
          route: '/administration/semestre/' + selectedSemestre.value.id + '/sous-commission'},
        { label: 'Changement de semestre des étudiants', icon: 'pi pi-forward', command: () => {} },
      ]
    },
  ]
})

onMounted(async () => {
  await getAnneesSemestres();
});

const getAnneesSemestres = async () => {
  try {
    isLoading.value = true;
    const departementId = userStore.departementDefaut.id;

    if (!departementId) {
      console.error("Aucun département par défaut trouvé pour l'utilisateur.");
      hasError.value = true;
      return;
    }
    try {
      const params = {
        departement: departementId,
        actif: true,
      };
      const annees = await getAnneesService(params);
      console.log(annees)
      // Créer un nouvel objet pour stocker les années de formation initiale et continue
      anneesGrouped.value = {
        fi: annees.filter(a => a.opt.alternance === false).map(a => a),
        fc: annees.filter(a => a.opt.alternance === true).map(a => a),
      };
    } catch (error) {
      console.error("Erreur lors de la récupération des années :", error);
      hasError.value = true;
    }

  } catch (error) {
    console.error("Erreur lors de la récupération des semestres :", error);
    hasError.value = true;
  } finally {
    isLoading.value = false;

    selectedSemestre.value =
        anneesGrouped.value.fi?.[0]?.semestres?.[0] ??
        anneesGrouped.value.fc?.[0]?.semestres?.[0] ??
        null;
  }
};

const selectSemestre = (semestre) => {
  selectedSemestre.value = semestre;
};
</script>

<template>
  <div class="flex justify-between gap-10">
    <Fieldset class="w-full">
      <template #legend>
        <div class="flex items-center pl-2">
          <i class="pi pi-briefcase bg-yellow-400 bg-opacity-20 rounded-full p-4 text-yellow-500"/>
          <div class="flex flex-col">
            <span class="font-bold px-2 capitalize">Semestres</span>
            <em class="text-muted-color px-2">Étudiants, absences, notes, fin de semestre</em>
          </div>
        </div>
      </template>
      <ListSkeleton v-if="isLoading" class="mt-4"/>
      <ErrorView v-else-if="hasError" />
      <Message v-else-if="anneesGrouped.fi.length < 1 && anneesGrouped.fc.length < 1" severity="error" icon="pi pi-times-circle" class="m-6">
        Aucun semestre disponible.
      </Message>
      <div v-else class="flex gap-10 mt-4">
        <div class="w-1/2 flex gap-4">
          <ul v-for="(annee, type) in anneesGrouped" class="w-1/2">
            <Fieldset :legend="type === 'fi' ? 'Formation Initiale' : 'Formation continue'" class="max-h-96 overflow-auto">
              <ul>
                <li v-for="annee in annee"
                    :key="annee.id"
                    class="mb-2 text-sm">
                  <div class="text-muted-color text-sm">{{ annee.libelle }}</div>
                  <ul>
                    <li v-for="semestre in annee.semestres"
                        :key="semestre.id"
                        @click="selectSemestre(semestre)"
                        class="cursor-pointer w-full border-b p-1">
                      <div class="hover:bg-primary-400 hover:bg-opacity-10 rounded-md w-full p-2"
                           :class="{'bg-primary-400 bg-opacity-10': selectedSemestre && selectedSemestre.id === semestre.id}">
                        {{ semestre.libelle }}
                      </div>
                    </li>
                  </ul>
                </li>
              </ul>
            </Fieldset>
          </ul>
        </div>
        <div class="w-1/2 " v-if="selectedSemestre">
          <h3 class="font-bold text-xl mb-4">Actions pour {{ selectedSemestre.libelle }}</h3>
          <PanelMenu :model="panelMenuItems" multiple>
            <template #item="{ item }">
              <router-link v-if="item.route" v-slot="{ href, navigate }" :to="item.route" custom>
                <a v-ripple class="flex items-center cursor-pointer text-surface-700 dark:text-surface-0 px-4 py-2" :href="href" @click="navigate">
                  <span :class="item.icon" />
                  <span class="ml-2">{{ item.label }}</span>
                </a>
              </router-link>
              <a v-else v-ripple class="flex items-center cursor-pointer text-surface-700 dark:text-surface-0 px-4 py-2" :href="item.url" :target="item.target">
                <span :class="item.icon" />
                <span class="ml-2">{{ item.label }}</span>
                <span v-if="item.items" class="pi pi-angle-down text-primary ml-auto" />
              </a>
            </template>
          </PanelMenu>
        </div>
      </div>
    </Fieldset>
  </div>
</template>

<style scoped>
.error-message {
  color: red;
  text-align: center;
  font-size: 1.2em;
}
</style>
