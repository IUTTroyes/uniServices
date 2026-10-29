<script setup>
import { onMounted, ref, computed, watch } from "vue";
import Tag from "primevue/tag";
import { ErrorView, ListSkeleton } from "@components";
import { useUsersStore, useSemestreStore, useAnneeStore, useAnneeUnivStore } from "@stores";

const userStore = useUsersStore();
const semestreStore = useSemestreStore();
const anneeStore = useAnneeStore();
const anneeUnivStore = useAnneeUnivStore();

const selectedAnneeUniversitaire = anneeUnivStore.selectedAnneeUniv;
const selectedAnnee = ref(null);
const isLoading = ref(true);
const hasError = ref(false);
const anneesGrouped = ref({ fi: [], fc: [] });

// Vérifier si l'année (ou le diplôme lié) est actif
const isAnneeActive = (annee) => {
  if (annee?.actif === false) return false;
  if (annee?.diplome && annee.diplome.actif === false) return false;
  if (annee?.pn?.diplome && annee.pn.diplome.actif === false) return false;
  return annee?.actif === true || annee?.actif === undefined;
};

// Récupérer et trier les semestres d'une année
const getSortedSemestres = (annee) => {
  if (!annee?.semestres || !Array.isArray(annee.semestres)) return [];
  return [...annee.semestres]
    .filter(s => s.actif === undefined || s.actif === true)
    .sort((a, b) => {
      if (a.ordreAnnee !== undefined && b.ordreAnnee !== undefined && a.ordreAnnee !== b.ordreAnnee) {
        return a.ordreAnnee - b.ordreAnnee;
      }
      return (a.libelle || '').localeCompare(b.libelle || '', undefined, { numeric: true });
    });
};

// When the selected year changes, store it globally and select the first semester of that year if needed
watch(selectedAnnee, (newVal) => {
  anneeStore.setSelectedAnnee(newVal);
  const sortedSemestres = getSortedSemestres(newVal);
  const firstSem = sortedSemestres[0] ?? null;
  semestreStore.setSelectedSemestre(firstSem);
});

const anneeId = computed(() => selectedAnnee.value?.id ?? null);

const actionButtons = computed(() => {
  // Use the annee id for routes
  const aid = anneeId.value;
  if (!selectedAnnee.value) return [];
  return [
    {
      label: 'Étudiants',
      icon: 'pi pi-user',
      route: aid ? '/intranet/administration/annee/' + aid + '/etudiants' : null,
    },
    {
      label: 'Groupes',
      icon: 'pi pi-users',
      route: aid ? '/intranet/administration/annee/' + aid + '/groupes/affectation' : null,
    },
    {
      label: 'Absences',
      icon: 'pi pi-calendar',
      route: aid ? '/intranet/administration/annee/' + aid + '/absences/liste' : null,
    },
    {
      label: 'Notes et Évaluations',
      icon: 'pi pi-book',
      route: aid ? '/intranet/administration/annee/' + aid + '/evaluations' : null,
    },
    {
      label: 'Fin de semestre',
      icon: 'pi pi-check',
      route: aid ? '/intranet/administration/annee/' + aid + '/sous-commission' : null,
    },
  ]
});

onMounted(async () => {
  await getAnnees();
});

const getAnnees = async () => {
  try {
    isLoading.value = true;
    const departementId = userStore.departementDefaut?.id;

    if (!departementId) {
      console.error("Aucun département par défaut trouvé pour l'utilisateur.");
      hasError.value = true;
      return;
    }
    try {
      const anneeUnivId = selectedAnneeUniversitaire?.id ?? selectedAnneeUniversitaire?.value?.id;
      const params = {
        departement: departementId,
        actif: true,
      };
      if (anneeUnivId) {
        params.anneeUniversitaire = anneeUnivId;
      }
      await anneeStore.getAnneesDepartement(params);
      const annees = Array.isArray(anneeStore.annees) ? anneeStore.annees : [];
      // Créer un nouvel objet pour stocker les années de formation initiale et continue
      anneesGrouped.value = {
        fi: annees.filter(a => isAnneeActive(a) && a.opt?.alternance === false),
        fc: annees.filter(a => isAnneeActive(a) && a.opt?.alternance === true),
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
          <i class="pi pi-briefcase bg-yellow-400/20 rounded-full p-4 text-yellow-500"/>
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
            <Fieldset :legend="type === 'fi' ? `Formation Initiale (${list.length})` : `Formation Alternance (${list.length})`" class="max-h-96 overflow-auto">
              <ul>
                <li v-for="annee in list"
                    :key="annee.id"
                    class="mb-2 text-sm">
                  <div @click="selectAnnee(annee)"
                       class="cursor-pointer hover:bg-primary-400/10 rounded-md w-full p-2 border border-neutral-200 dark:border-neutral-700 flex items-center justify-between gap-2 transition-colors"
                       :class="{'bg-primary-400/20': selectedAnnee && selectedAnnee.id === annee.id}">
                    <span class="truncate">{{ annee.libelle }}</span>
                    <div v-if="getSortedSemestres(annee).length > 0" class="flex items-center gap-1 shrink-0">
                      <Tag
                        v-for="semestre in getSortedSemestres(annee)"
                        :key="semestre.id"
                        severity="primary"
                        class="!text-xs !py-0.5 !px-1.5"
                        :value="semestre.libelle"
                      />
                    </div>
                  </div>
                </li>
              </ul>
            </Fieldset>
          </ul>
        </div>
        <div class="w-1/2 " v-if="selectedAnnee">
          <h3 class="font-bold text-xl mb-4">Actions pour {{ selectedAnnee.libelle }}</h3>
          <div class="flex flex-col gap-3">
            <router-link
              v-for="item in actionButtons"
              :key="item.label"
              :to="item.route"
              class="flex items-center gap-3 p-3 rounded-lg border border-neutral-200 dark:border-neutral-700 hover:bg-primary-400/10 transition-colors"
            >
              <i :class="item.icon" class="text-primary" />
              <span class="font-medium text-surface-700 dark:text-surface-0">{{ item.label }}</span>
            </router-link>
          </div>
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
