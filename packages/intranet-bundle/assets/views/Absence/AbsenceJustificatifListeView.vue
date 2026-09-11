<script setup>
import {computed, onMounted, onUnmounted, ref, watch} from 'vue';
import {HeaderComponent, Kpi, EdtEventRow, ValidatedInput, validationRules, SimpleSkeleton, ButtonInfo, ButtonDelete} from '@components';
import {useAnneeStore, useUsersStore} from '@stores';
import {getAbsenceJustificatifsService, getAnneeService, updateAbsenceJustificatifService, deleteAbsenceJustificatifService} from '@requests';
import {useRoute, useRouter} from 'vue-router';
import {FilterMatchMode} from '@primevue/core/api';
import {useConfirm} from "primevue/useconfirm";

const route = useRoute();
const router = useRouter();
const anneeStore = useAnneeStore();
const usersStore = useUsersStore();
const anneeUniv = localStorage.getItem('selectedAnneeUniv') ? JSON.parse(localStorage.getItem('selectedAnneeUniv')) : {id: null};
const departementId = usersStore.departementDefaut.id;

const justificatifs = ref([]);
const nbJustificatifs = ref(0);
const selectedJustificatif = ref(null);
const showDetailsDialog = ref(false);
const isLoading = ref(true);
const isLoadingAnnee = ref(true);
const isLoadingAnnees = ref(false);
const page = ref(0);
const limit = ref(10);
const rowOptions = [10, 20, 50];
const annees = ref([]);
const annee = ref({});
const periode = ref(null);
const minDate = ref(new Date(new Date().getFullYear(), 0, 1));
const maxDate = ref(new Date(new Date().getFullYear(), 11, 31));
const multiSortMeta = ref([]);
const refuseMotif = ref('');

const filters = ref({
  'etudiant.display': {value: null, matchMode: FilterMatchMode.CONTAINS},
  periode: {value: null, matchMode: FilterMatchMode.CONTAINS},
  motif: {value: null, matchMode: FilterMatchMode.CONTAINS},
  etat: {value: null, matchMode: FilterMatchMode.EQUALS},
});

const FILTERS_DEBOUNCE_MS = 250;
let filtersDebounceTimeout = null;

const offset = computed(() => limit.value * page.value);

const stats = computed(() => {
  const total = justificatifs.value.length;
  const valides = justificatifs.value.filter(item => item.etat === 'VALIDE').length;
  const enAttente = justificatifs.value.filter(item => item.etat === 'EN_ATTENTE').length;
  const refuses = justificatifs.value.filter(item => item.etat === 'REFUSE').length;
  return [
    {title: 'Total justificatifs', value: total, color: 'blue-500', icon: 'pi pi-list'},
    {title: 'Validés', value: valides, color: 'green-500', icon: 'pi pi-check'},
    {title: 'En attente', value: enAttente, color: 'yellow-500', icon: 'pi pi-clock'},
    {title: 'Refusés', value: refuses, color: 'red-500', icon: 'pi pi-times'},
  ];
});

const getJustificatifs = async () => {
  isLoading.value = true;
  try {
    const params = {
      annee: annee.value?.id,
      anneeUniversitaire: anneeUniv.id,
      itemsPerPage: limit.value,
      page: page.value + 1,
      filters: filters.value,
      sort: multiSortMeta.value,
    };
    const response = await getAbsenceJustificatifsService(params, '/administration');
    justificatifs.value = response?.member ?? [];
    nbJustificatifs.value = response?.totalRecords ?? response?.totalItems ?? response?.length ?? 0;
  } finally {
    isLoading.value = false;
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
  } finally {
    isLoadingAnnees.value = false;
  }
};

const getAnnee = async () => {
  isLoadingAnnee.value = true;
  try {
    const anneeId = route.params.anneeId;
    annee.value = await getAnneeService(anneeId);
    await anneeStore.setSelectedAnnee(annee.value);
  } finally {
    isLoadingAnnee.value = false;
  }
};

const updateEtat = async (item, etat) => {
  const params = {
    etat: etat,
    motif_refus: etat === 2 ? item.motif_refus : null,
  };
  await updateAbsenceJustificatifService(item.id, params, '/administration', true);
  await getJustificatifs();
};

const deleteJustificatif = async item => {
  await deleteAbsenceJustificatifService(item.id, '/administration', true);
  await getJustificatifs();
};

const openDetails = item => {
  selectedJustificatif.value = item;
  showDetailsDialog.value = true;
};

const getEtatBadge = item => {
  return item?.etatBadge || 'secondary';
};

const getEtatLibelle = item => {
  return item?.etatLibelle || '-';
};

const getEtatOptions = () => {
  const options = justificatifs.value?.[0]?.etatOptions;

  if (!options || typeof options !== 'object') {
    return [];
  }
  return Object.entries(options).map(([value, label]) => ({
    label,
    value: Number(value),
  }));

};

const formatDateTime = date => {
  if (!date) return '-';
  return new Date(date).toLocaleString('fr-FR');
};

const detailsAbsences = computed(() => selectedJustificatif.value?.absence || []);

const onPageChange = async event => {
  limit.value = event.rows;
  page.value = event.page;
  await getJustificatifs();
};

const onSortChange = async event => {
  multiSortMeta.value = event.multiSortMeta ?? [];
  page.value = 0;
  await getJustificatifs();
};

const formatEvent = event => ({
  id: event?.id,
  heure: `${event?.debut ? new Date(event.debut).toLocaleTimeString('fr-FR', {hour: '2-digit', minute: '2-digit'}) : '--:--'} - ${event?.fin ? new Date(event.fin).toLocaleTimeString('fr-FR', {hour: '2-digit', minute: '2-digit'}) : '--:--'}`,
  groupe: event?.libGroupe || event?.codeGroupe || '-',
  cours: event?.codeModule ? `${event.codeModule} - ${event?.libModule || ''}` : (event?.libModule || '-'),
  salle: event?.salle || '-',
  color: event?.couleur,
  intervenant: event?.libPersonnel || '-',
});

onMounted(async () => {
  await getAnnees();
  await getAnnee();
  await getJustificatifs();
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
    getJustificatifs();
  }, FILTERS_DEBOUNCE_MS);
}, {deep: true});

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
  await getJustificatifs();
});

const confirm = useConfirm()
const showRefuse = item => {
  refuseMotif.value = '';
  confirm.require({
    group: 'refuse',
    header: 'Confirmation de refus',
    message: 'Êtes-vous sûr de vouloir refuser ce justificatif ?',
    icon: 'pi pi-exclamation-triangle',
    rejectProps: {
      label: 'Annuler',
      outlined: true,
      severity: 'secondary'
    },
    acceptProps: {
      label: 'Confirmer',
      severity: 'warn'
    },
    accept: async () => {
      await updateEtat({...item, motif_refus: refuseMotif.value}, 2);
      refuseMotif.value = '';
    },
    reject: () => {
      refuseMotif.value = '';
    }
  });
};
const showValidate = item => {
  confirm.require({
    group: 'valide',
    header: 'Confirmation de validation',
    message: 'Êtes-vous sûr de vouloir valider ce justificatif ?',
    icon: 'pi pi-check-circle',

    rejectProps: {
      label: 'Annuler',
      outlined: true,
      severity: 'secondary'
    },
    acceptProps: {
      label: 'Confirmer',
      severity: 'success'
    },
    accept: async () => {
      await updateEtat(item, 1);
    },
  });
};
</script>

<template>
  <HeaderComponent icon="pi pi-file" titre="Justificatifs d'absences" description="Gérez les justificatifs déposés" /><div class="flex justify-around items-center mb-12">
  <div v-for="stat in stats" :key="stat.title" class="card w-1/5 flex items-center justify-center flex-col">
    <Kpi :label="stat.title" :value="stat.value" :icon="stat.icon" :color="stat.color" />
  </div>
</div><div class="card">
  <div class="flex flex-col md:flex-row justify-between items-start w-full card-header">
    <div>
      <p class="top-card-header">
        Contrôle des justificatifs
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
    <Message severity="info" :closable="false" icon="pi pi-info-circle" class="mb-2">
      Maintenez Ctrl ou Cmd et cliquez sur plusieurs colonnes pour trier par plusieurs champs à la fois.
    </Message>
    <DataTable
        :value="justificatifs"
        v-model:filters="filters"
        lazy
        paginator
        removableSort
        sortMode="multiple"
        filterDisplay="row"
        :rows="limit"
        :first="offset"
        :rowsPerPageOptions="rowOptions"
        :totalRecords="nbJustificatifs"
        :loading="isLoading"
        @page="onPageChange"
        @sort="onSortChange"
    >
      <Column field="etudiant.display" header="Étudiant" style="min-width: 6rem" sortable :showFilterMenu="false">
        <template #filter="{ filterModel, filterCallback }">
          <InputText v-model="filterModel.value" type="text" @input="filterCallback()" placeholder="Filtrer par étudiant"/>
        </template>
      </Column>
      <Column field="periode" header="Période" sortable :showFilterMenu="false">
        <template #body="slotProps">
          {{ new Date(slotProps.data.debut).toLocaleString('fr-FR') }} - {{ new Date(slotProps.data.fin).toLocaleString('fr-FR') }}
        </template>
        <template #filter="{ filterModel, filterCallback }">
          <ValidatedInput
              v-model="filterModel.value"
              name="date"
              type="date"
              :rules="[]"
              selectionMode="range"
              :manualInput="false"
              :minDate="minDate"
              :maxDate="maxDate"
              placeholder="Filtrer par période"
              @input="filterCallback()"
              class="mb-0!"
          />
        </template>
      </Column>
      <Column field="motif" header="Motif" :showFilterMenu="false">
        <template #filter="{ filterModel, filterCallback }">
          <InputText v-model="filterModel.value" type="text" @input="filterCallback()" placeholder="Filtrer par motif"/>
        </template>
      </Column>
      <Column field="etat" header="État" sortable :showFilterMenu="false">
        <template #body="slotProps">
          <div class="flex items-center gap-2">
            <Badge :severity="getEtatBadge(slotProps.data)">{{ getEtatLibelle(slotProps.data) }}</Badge>
            <Badge v-if="slotProps.data.motif_refus" severity="danger" v-tooltip.top="`${slotProps.data.motif_refus}`"><i class="pi pi-info-circle text-xs!"></i></Badge>
          </div>
        </template>
        <template #filter="{ filterModel, filterCallback }">
          <Select v-model="filterModel.value" @change="filterCallback()" :options="getEtatOptions()" option-label="label" option-value="value"
                  placeholder="Filtrer" style="min-width: 8rem" :showClear="true"/>
        </template>
      </Column>
      <Column header="Fichier">
        <template #body="slotProps">
          <Button v-if="slotProps.data.fichier" size="small" target="_blank" icon="pi pi-eye" label="Afficher" severity="secondary" />
          <span v-else>-</span>
        </template>
      </Column>
      <Column field="absencesCount" header="Cours manqués" />
      <Column header="Actions">
        <template #body="slotProps">
          <div class="flex">
            <Button icon="pi pi-check" class="mr-2" rounded variant="outlined" severity="success" v-tooltip.top="`Valider le justificatif`" @click="showValidate(slotProps.data)" />
            <Button icon="pi pi-times" class="mr-2" rounded variant="outlined" severity="warn" v-tooltip.top="`Refuser le justificatif`" @click="showRefuse(slotProps.data)" />
            <ButtonDelete :tooltip="`Supprimer le justificatif`" @confirm-delete="deleteJustificatif(slotProps.data)" />
            <ButtonInfo :tooltip="`Voir le détail du justificatif`" @click="openDetails(slotProps.data)" />
          </div>
        </template>
      </Column>
      <template #footer> {{ nbJustificatifs }} résultat(s).</template>
    </DataTable>
    <ConfirmDialog group="refuse">
      <template #message="{ message, icon }">
        <div class="flex flex-col gap-4">
          <div class="flex items-center gap-4">
            <i :class="message.icon" class="text-4xl!" />
            <p>{{ message.message }}</p>
          </div>
          <ValidatedInput
              type="textarea"
              label="Motif du refus"
              v-model="refuseMotif"
              name="motif_refus"
              :rules="[validationRules.required]"
              placeholder="Motif du refus"
              class="mt-4"
              help-text="'Indiquez le motif de refus du justificatif. Ce motif sera visible par l\'étudiant.'"
          />
        </div>
      </template>
    </ConfirmDialog>
    <ConfirmDialog group="valide">
      <template #message="{ message, icon }">
        <div class="flex items-center gap-4">
          <i :class="message.icon" class="text-4xl!" />
          <p>{{ message.message }}</p>
        </div>
      </template>
    </ConfirmDialog>
  </div>
</div><Dialog header="Détail du justificatif" :visible="showDetailsDialog" modal dismissable-mask :style="{ width: '85vw' }" @update:visible="showDetailsDialog = $event">
  <div v-if="selectedJustificatif" class="mb-4">
    <div class="text-900 text-xl font-semibold mb-1">{{ selectedJustificatif.etudiant?.display || '-' }}</div>
    <div class="text-500 mb-3">Période : {{ formatDateTime(selectedJustificatif.debut) }} → {{ formatDateTime(selectedJustificatif.fin) }}</div>

    <div class="flex flex-wrap gap-2 mb-3">
      <Badge :severity="getEtatBadge(selectedJustificatif)">{{ getEtatLibelle(selectedJustificatif) }}</Badge>
      <Badge severity="success">{{ selectedJustificatif.absencesCount ?? detailsAbsences.length }} cours manqué(s)</Badge>
    </div>

    <div class="my-8">
      <div class="text-lg font-semibold">Motif du justificatif</div>
      <div class="line-height-3">{{ selectedJustificatif.motif || 'Aucun motif saisi' }}</div>
      <a
          v-if="selectedJustificatif.fichier"
          :href="selectedJustificatif.fichier"
          target="_blank"
          class="inline-flex align-items-center gap-2 text-primary font-medium"
      >
        <i class="pi pi-download" />
        Télécharger le fichier
      </a>
      <div v-else class="text-500">Aucun fichier joint</div>
    </div>
  </div><Message v-if="!detailsAbsences.length" severity="warn" :closable="false" icon="pi pi-exclamation-triangle">
  Aucun cours manqué sur cette période.
</Message>
  <DataTable v-else :value="detailsAbsences" striped-rows>
    <Column header="Cours manqués sur cette période">
      <template #body="slotProps">
        <EdtEventRow :item="formatEvent(slotProps.data.event)" />
      </template>
    </Column>
  </DataTable>
</Dialog>
</template>
