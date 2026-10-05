<script setup>
import {computed, onMounted, onUnmounted, ref, watch} from "vue";
import {SimpleSkeleton, HeaderComponent, Kpi, ListSkeleton} from "@components";
import {useAnneeStore, useEtablissementStore, useUsersStore} from "@stores";
import {getAnneeService, getEdtEventsService} from "@requests";
import { formatDateCourt, heuresMinutesDate } from "@helpers/date";

import {useRoute, useRouter} from "vue-router";
import {FilterMatchMode} from "@primevue/core/api";

const route = useRoute();
const router = useRouter();
const hasError = ref(false);
const anneeUniv = localStorage.getItem('selectedAnneeUniv') ? JSON.parse(localStorage.getItem('selectedAnneeUniv')) : { id: null };
const usersStore = useUsersStore();
const departementId = usersStore.departementDefaut.id;
const anneeStore = useAnneeStore();
const etablissementStore = useEtablissementStore();
const annees = ref([]);
const annee = ref({});
const etablissement = ref(null);
const isLoadingAnnee = ref(true);
const isLoadingAnnees = ref(false);
const isLoadingEtablissement = ref(true);
const events = ref([]);
const totalEvents = ref(0);
const isLoadingEvents = ref(false);
const page = ref(0);
const rowOptions = [10, 20, 50];
const limit = ref(rowOptions[0]);
const offset = computed(() => limit.value * page.value);
const filters = ref({
  'enseignement.display': {value: null, matchMode: FilterMatchMode.CONTAINS},
});
const FILTERS_DEBOUNCE_MS = 250;
let filtersDebounceTimeout = null;

const edusignScopeMessage = computed(() => {
  const edusignSettings = etablissement.value?.settings?.integrations?.edusign;
  const isEnabled = !!edusignSettings?.enabled;

  if (!isEnabled) {
    return null;
  }

  const rawScope = edusignSettings?.scope;
  const normalizedScope = Array.isArray(rawScope)
      ? rawScope.filter(item => ['FI', 'FC'].includes(item))
      : [];

  if (normalizedScope.length !== 1) {
    return null;
  }

  const [scope] = normalizedScope;
  return `Edusign est activé pour la ${scope} qui n'est donc pas disponible pour le suivi du pointage des présences.`;
});

onMounted(async () => {
  await getAnnees();
  await getAnnee();
  await getEtablissement();
});

const getEtablissement = async () => {
  try {
    isLoadingEtablissement.value = true;
    await etablissementStore.getEtablissement();
    etablissement.value = etablissementStore.etablissement;
  } catch (error) {
    console.error("Erreur lors de la récupération de l'établissement :", error);
  } finally {
    isLoadingEtablissement.value = false;
  }
};

const getAnnees = async () => {
  if (anneeStore.annees && Array.isArray(anneeStore.annees) && anneeStore.annees.length > 0) {
    annees.value = anneeStore.annees;
    return;
  }
  try {
    isLoadingAnnees.value = true;
    const params = {
      departement: departementId,
      actif: true,
    };
    await anneeStore.getAnneesDepartement(params);
    annees.value = Array.isArray(anneeStore.annees) ? anneeStore.annees : [];
  } catch (error) {
    console.error("Erreur lors de la récupération des années :", error);
    hasError.value = true;
  } finally {
    isLoadingAnnees.value = false;
  }
};

const getAnnee = async () => {
  isLoadingAnnee.value = true;
  hasError.value = false;
  try {
    const anneeId = route.params.anneeId;
    annee.value = await getAnneeService(anneeId);
    await anneeStore.setSelectedAnnee(annee.value);
  } catch (error) {
    hasError.value = true;
    console.error("Erreur lors de la récupération de l'année :", error);
  } finally {
    isLoadingAnnee.value = false;
  }
};

watch(annee, async (newAnnee, oldAnnee) => {
  if (newAnnee?.id === oldAnnee?.id) return;

  if (newAnnee?.id && String(route.params.anneeId) !== String(newAnnee.id)) {
    await router.replace({
      name: route.name,
      params: {
        ...route.params,
        anneeId: String(newAnnee.id),
      },
      query: route.query,
    });
  }

  await anneeStore.setSelectedAnnee(newAnnee);
  page.value = 0;

  // ICI: appeler les requêtes qui récupères les datas
  await getEdtEvents();
});


onUnmounted(() => {
  if (filtersDebounceTimeout) {
    clearTimeout(filtersDebounceTimeout);
  }
});

watch(filters, () => {
  page.value = 0;
  if (filtersDebounceTimeout) {
    clearTimeout(filtersDebounceTimeout);
  }
  filtersDebounceTimeout = setTimeout(() => {
    getEdtEvents();
  }, FILTERS_DEBOUNCE_MS);
}, {deep: true});

const getEdtEvents = async () => {
  try {
    const params = {
      annee: annee.value.id,
      anneeUniversitaire: anneeUniv.id,
      pagination: true,
      itemsPerPage: limit.value,
      page: page.value + 1,
      filters: filters.value,
    };

    events.value = await getEdtEventsService(params, '/pointage');
    totalEvents.value = events.value?.totalItems || 0;
    console.log("Events récupérés :", events.value);
  } catch (error) {
    console.error("Erreur lors de la récupération des événements :", error);
  } finally {
    isLoadingEvents.value = false;
  }
};

const onPageChange = async event => {
  limit.value = event.rows;
  page.value = event.page;
  await getEdtEvents();
};
</script>

<template>
  <HeaderComponent
      icon="pi pi-calendar"
      titre="Suivi du pointage des présences"
      description="Vérifiez que l'appel a bien été réalisée sur l'ensemble des cours"
  />

  <!--  <div class="flex justify-around items-center mb-12">-->
  <!--    <div v-for="stat in absencesStats" :key="stat.title" class="card w-1/5 flex items-center justify-center flex-col">-->
  <!--      <Kpi-->
  <!--          :label="stat.title"-->
  <!--          :value="stat.value"-->
  <!--          :icon="stat.icon"-->
  <!--          :color="stat.color"-->
  <!--      />-->
  <!--    </div>-->
  <!--  </div>-->

  <div class="flex flex-col gap-6">
    <div class="card">
      <div class="flex flex-col md:flex-row justify-between items-start w-full card-header">
        <div>
          <p class="top-card-header">
            Contrôle de l'appel
          </p>
          <div class="flex flex-col items-start">
            <p class="uppercase text-xs font-bold mb-0! text-muted-color">
              année
            </p>
            <h2 class="mt-0!">
              {{ annee?.libelle }}
            </h2>
          </div>
        </div>
        <SimpleSkeleton v-if="isLoadingAnnees || isLoadingAnnee" class="!w-60 !h-10"></SimpleSkeleton>
        <div v-else class="flex flex-col gap-2">
          <div class="flex flex-col justify-center items-end filters-card-header">
            <div class="text-sm uppercase font-semibold">Changer d'année</div>
            <Select class="w-60" v-model="annee" option-label="libelle" :options="annees">
              <template #value>
                {{ annee?.libelle || "Changer d'année" }}
              </template>
            </Select>
          </div>
        </div>
      </div>
      <div class="card-body">
        <ListSkeleton v-if="isLoadingEtablissement"/>
        <div v-else class="">
          <Message v-if="edusignScopeMessage" icon="pi pi-info-circle" severity="warn" class="mx-auto w-1/2 mb-12 mt-12">
            {{ edusignScopeMessage }}
          </Message>
          <Divider></Divider>
        </div>

        <Message severity="info" :closable="false" icon="pi pi-info-circle" class="mb-2">
          Maintenez Ctrl ou Cmd et cliquez sur plusieurs colonnes pour trier par plusieurs champs à la fois.
        </Message>
        <Message severity="info" icon="pi pi-info-circle" class="w-full flex justify-center" v-if="!isLoadingEvents && (!events || events.length === 0)">
          Aucuns événements trouvée pour cette année.
        </Message>
        <DataTable
            v-else
            :value="events"
            v-model:filters="filters"
            lazy
            striped-rows
            class="w-full"
            paginator
            removableSort
            sortMode="multiple"
            :first="offset"
            :rows="limit"
            :rowsPerPageOptions="rowOptions"
            :totalRecords="totalEvents"
            @page="onPageChange($event)"
            @update:rows="limit = $event"
        >
          <Column field="date" header="Date" sortable>
            <template #body="slotProps">
              {{ formatDateCourt(slotProps.data.date) }} <br /> {{ heuresMinutesDate(slotProps.data.debut) }} - {{ heuresMinutesDate(slotProps.data.fin) }}
            </template>
          </Column>
          <Column field="enseignement.display" header="Enseignement" sortable/>
          <Column field="groupe.libelle" header="Groupe" sortable/>
          <Column field="personnel.display" header="Enseignant" sortable/>
          <Column field="appel.etat" header="État" sortable>
            <template #body="slotProps">
              <Tag :severity="slotProps.data.appel ? 'success' : !slotProps.data.appel ? 'danger' : 'secondary'" :icon="slotProps.data.appel ? 'pi pi-check' : !slotProps.data.appel ? 'pi pi-times' : 'pi pi-question'">
                {{ slotProps.data.appel ? 'Appel fait' : !slotProps.data.appel ? 'Appel non fait' : 'Inconnu' }}
              </Tag>
            </template>
          </Column>
        </DataTable>
      </div>
    </div>


  </div>
</template>

<style scoped>

</style>
