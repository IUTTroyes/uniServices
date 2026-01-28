<script setup>
import { onMounted, ref, computed, watch } from "vue";
import { getAnneesService } from "@requests";
import { ErrorView, ListSkeleton } from "@components";
import { useUsersStore, useSemestreStore, useAnneeStore } from "@stores";

const userStore = useUsersStore();
const semestreStore = useSemestreStore();
const anneeStore = useAnneeStore();

const selectedAnnee = ref(null);
const isLoading = ref(true);
const hasError = ref(false);
const anneesGrouped = ref({ fi: [], fc: [] });

// When the selected year changes, store it globally and select the first semester of that year if needed
watch(selectedAnnee, (newVal) => {
  anneeStore.setSelectedAnnee(newVal);
  const firstSem = newVal?.semestres?.[0] ?? null;
  semestreStore.setSelectedSemestre(firstSem);
});

const firstSemestreId = computed(() => selectedAnnee.value?.semestres?.[0]?.id ?? null);

const panelMenuItems = computed(() => {
  // For now, actions needing a semestre use the first semester of the selected year
  const sid = firstSemestreId.value;
  if (!selectedAnnee.value) return [];
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
          route: sid ? '/administration/semestre/' + sid + '/groupes/affectation' : null,
        },
        {
          label: 'Structure des groupes', icon: 'pi pi-cog', route: sid ? '/administration/semestre/' + sid + '/groupes/structure' : null,
        }
      ]
    },
    {
      label: 'Absences', icon: 'pi pi-calendar', command: () => {}, items: [
        {
          label: 'Liste des absences', icon: 'pi pi-list',
          route: sid ? '/administration/semestre/' + sid + '/absences/liste' : null,
        },
        {
          label: 'Liste des justificatifs', icon: 'pi pi-folder-open',
          route: sid ? '/administration/semestre/' + sid + '/justificatifs-absences/liste' : null,
        },
        { label: 'Suivi des pointages de présence', icon: 'pi pi-eye', command: () => {} },
      ]
    },
    {
      label: 'Notes et Évaluations', icon: 'pi pi-book', command: () => {}, items: [
        {
          label: 'Liste des notes', icon: 'pi pi-list',
          route: sid ? '/administration/semestre/' + sid + '/evaluations/liste' : null
        },
        { label: 'Gestion des évaluations', icon: 'pi pi-cog', command: () => {} },
        {
          label: 'Demandes de rattrapages', icon: 'pi pi-history',
          route: sid ? '/administration/semestre/' + sid + '/rattrapages/liste' : null },
        { label: 'Modalités du contrôle continu', icon: 'pi pi-map',
          route: sid ? '/administration/semestre/' + sid + '/mccc/liste' : null
        },
      ]
    },
    {
      label: 'Fin de semestre', icon: 'pi pi-check', command: () => {}, items: [
        { label: 'Préparation de la sous-commission', icon: 'pi pi-calculator',
          route: sid ? '/administration/semestre/' + sid + '/sous-commission' : null},
        { label: 'Changement de semestre des étudiants', icon: 'pi pi-forward', command: () => {} },
      ]
    },
  ]
});

onMounted(async () => {
  await getAnnees();
});

const getAnnees = async () => {
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
    console.error("Erreur lors de la récupération des années :", error);
    hasError.value = true;
  } finally {
    isLoading.value = false;

    // sélectionner par défaut la première année disponible (FI puis FC)
    selectedAnnee.value =
        anneesGrouped.value.fi?.[0] ??
        anneesGrouped.value.fc?.[0] ??
        null;
  }
};

const selectAnnee = (annee) => {
  selectedAnnee.value = annee;
};
</script>

<template>
  <div class="flex justify-between gap-10">
    <Fieldset class="w-full">
      <template #legend>
        <div class="flex items-center pl-2">
          <i class="pi pi-briefcase bg-yellow-400 bg-opacity-20 rounded-full p-4 text-yellow-500"/>
          <div class="flex flex-col">
            <span class="font-bold px-2 capitalize">Années</span>
            <em class="text-muted-color px-2">Étudiants, absences, notes, fin d'année</em>
          </div>
        </div>
      </template>
      <ListSkeleton v-if="isLoading" class="mt-4"/>
      <ErrorView v-else-if="hasError" />
      <Message v-else-if="anneesGrouped.fi.length < 1 && anneesGrouped.fc.length < 1" severity="error" icon="pi pi-times-circle" class="m-6">
        Aucune année disponible.
      </Message>
      <div v-else class="flex gap-10 mt-4">
        <div class="w-1/2 flex gap-4">
          <ul v-for="(list, type) in anneesGrouped" class="w-1/2">
            <Fieldset :legend="type === 'fi' ? 'Formation Initiale' : 'Formation continue'" class="max-h-96 overflow-auto">
              <ul>
                <li v-for="annee in list"
                    :key="annee.id"
                    class="mb-2 text-sm">
                  <div @click="selectAnnee(annee)"
                       class="cursor-pointer hover:bg-primary-400 hover:bg-opacity-10 rounded-md w-full p-2 border"
                       :class="{'bg-primary-400 bg-opacity-10': selectedAnnee && selectedAnnee.id === annee.id}">
                    {{ annee.libelle }}
                  </div>
                </li>
              </ul>
            </Fieldset>
          </ul>
        </div>
        <div class="w-1/2 " v-if="selectedAnnee">
          <h3 class="font-bold text-xl mb-4">Actions pour {{ selectedAnnee.libelle }}</h3>
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
