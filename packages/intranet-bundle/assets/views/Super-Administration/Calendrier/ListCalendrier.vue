<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { useRouter } from 'vue-router';
import {
  getAllCalendriersService,
  createCalendrierService,
  updateCalendrierService,
  deleteCalendrierService,
  getAllAnneesUniversitairesService
} from '@requests';
import { ErrorView, ListSkeleton, ButtonDelete, ButtonEdit } from '@components';
import { useAnneeUnivStore } from '@stores';
import { useToast } from 'primevue/usetoast';

const router = useRouter();
const toast = useToast();
const anneeUnivStore = useAnneeUnivStore();

const hasError = ref(false);
const isLoading = ref(false);
const isSubmitting = ref(false);
const calendriers = ref([]);
const anneesUniv = ref([]);
const selectedAnneeUniv = ref(null);
const searchQuery = ref('');

const page = ref(0);
const rowOptions = [10, 20, 30, 52];
const offset = computed(() => limit.value * page.value);
const limit = ref(rowOptions[0]);

// Dialog state
const dialogVisible = ref(false);
const isEditing = ref(false);
const formCalendrier = ref({
  id: null,
  anneeUniversitaire: null,
  semaineFormation: 1,
  semaineReelle: 36,
  dateLundi: null
});

const filteredCalendriers = computed(() => {
  let list = calendriers.value;

  if (selectedAnneeUniv.value) {
    list = list.filter(c => {
      const anneeId = c.anneeUniversitaire?.id || (typeof c.anneeUniversitaire === 'string' ? parseInt(c.anneeUniversitaire.split('/').pop()) : c.anneeUniversitaire);
      return anneeId === selectedAnneeUniv.value;
    });
  }

  if (!searchQuery.value.trim()) return list;
  const q = searchQuery.value.toLowerCase().trim();
  return list.filter(c =>
    (c.id && String(c.id).includes(q)) ||
    (c.semaineFormation && String(c.semaineFormation).includes(q)) ||
    (c.semaineReelle && String(c.semaineReelle).includes(q)) ||
    (c.dateLundi && c.dateLundi.includes(q))
  );
});

onMounted(async () => {
  await loadAnneesUniv();
  await getCalendriers();
});

const loadAnneesUniv = async () => {
  try {
    const res = await getAllAnneesUniversitairesService();
    anneesUniv.value = res || [];
    if (anneeUnivStore.anneeUniv?.id) {
      selectedAnneeUniv.value = anneeUnivStore.anneeUniv.id;
    } else if (anneesUniv.value.length > 0) {
      const active = anneesUniv.value.find(a => a.actif) || anneesUniv.value[0];
      selectedAnneeUniv.value = active.id;
    }
  } catch (error) {
    console.error('Erreur lors du chargement des années universitaires:', error);
  }
};

const getCalendriers = async () => {
  try {
    isLoading.value = true;
    hasError.value = false;
    const res = await getAllCalendriersService();
    calendriers.value = res || [];
  } catch (error) {
    console.error('Erreur lors de la récupération du calendrier:', error);
    hasError.value = true;
  } finally {
    isLoading.value = false;
  }
};

const formatDate = (dateStr) => {
  if (!dateStr) return '-';
  const d = new Date(dateStr);
  return d.toLocaleDateString('fr-FR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric'
  });
};

const openNewDialog = () => {
  isEditing.value = false;
  const defaultAnnee = selectedAnneeUniv.value
    ? `/api/structure_annee_universitaires/${selectedAnneeUniv.value}`
    : (anneesUniv.value[0] ? `/api/structure_annee_universitaires/${anneesUniv.value[0].id}` : null);

  formCalendrier.value = {
    id: null,
    anneeUniversitaire: defaultAnnee,
    semaineFormation: (calendriers.value.length > 0 ? Math.max(...calendriers.value.map(c => c.semaineFormation || 0)) + 1 : 1),
    semaineReelle: 36,
    dateLundi: new Date()
  };
  dialogVisible.value = true;
};

const openEditDialog = (item) => {
  isEditing.value = true;
  const anneeIri = typeof item.anneeUniversitaire === 'string'
    ? item.anneeUniversitaire
    : (item.anneeUniversitaire?.id ? `/api/structure_annee_universitaires/${item.anneeUniversitaire.id}` : null);

  formCalendrier.value = {
    id: item.id,
    anneeUniversitaire: anneeIri,
    semaineFormation: item.semaineFormation,
    semaineReelle: item.semaineReelle,
    dateLundi: item.dateLundi ? new Date(item.dateLundi) : null
  };
  dialogVisible.value = true;
};

const saveCalendrier = async () => {
  if (!formCalendrier.value.dateLundi) {
    toast.add({
      severity: 'warn',
      summary: 'Validation',
      detail: 'La date du lundi est obligatoire.',
      life: 3000
    });
    return;
  }

  isSubmitting.value = true;
  try {
    const formattedDate = formCalendrier.value.dateLundi instanceof Date
      ? formCalendrier.value.dateLundi.toISOString().split('T')[0]
      : formCalendrier.value.dateLundi;

    const payload = {
      semaineFormation: Number(formCalendrier.value.semaineFormation),
      semaineReelle: Number(formCalendrier.value.semaineReelle),
      dateLundi: formattedDate
    };

    if (formCalendrier.value.anneeUniversitaire) {
      payload.anneeUniversitaire = formCalendrier.value.anneeUniversitaire;
    }

    if (isEditing.value) {
      await updateCalendrierService(formCalendrier.value.id, payload);
      toast.add({
        severity: 'success',
        summary: 'Succès',
        detail: 'Semaine de calendrier mise à jour.',
        life: 3000
      });
    } else {
      await createCalendrierService(payload);
      toast.add({
        severity: 'success',
        summary: 'Succès',
        detail: 'Semaine ajoutée au calendrier.',
        life: 3000
      });
    }

    dialogVisible.value = false;
    await getCalendriers();
  } catch (error) {
    console.error('Erreur lors de l\'enregistrement de la semaine de calendrier:', error);
    toast.add({
      severity: 'error',
      summary: 'Erreur',
      detail: 'Une erreur est survenue lors de l\'enregistrement.',
      life: 3000
    });
  } finally {
    isSubmitting.value = false;
  }
};

const deleteItem = async (item) => {
  try {
    await deleteCalendrierService(item.id);
    toast.add({
      severity: 'success',
      summary: 'Succès',
      detail: 'Semaine supprimée du calendrier.',
      life: 3000
    });
    await getCalendriers();
  } catch (error) {
    console.error('Erreur lors de la suppression de la semaine de calendrier:', error);
    toast.add({
      severity: 'error',
      summary: 'Erreur',
      detail: 'Impossible de supprimer cette semaine du calendrier.',
      life: 4000
    });
  }
};
</script>

<template>
  <div class="card">
    <div class="card-title mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold">Gestion du calendrier universitaire</h1>
        <p class="text-muted-color">Gérer les semaines de formation, semaines réelles et dates associées.</p>
      </div>
      <div class="flex items-center gap-3">
        <Button
          label="Nouvelle semaine"
          icon="pi pi-plus"
          severity="primary"
          @click="openNewDialog"
        />
      </div>
    </div>

    <ErrorView v-if="hasError" />
    <ListSkeleton v-else-if="isLoading" :count="5" />
    <template v-else>
      <div class="mb-4 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div class="flex flex-wrap items-center gap-3 w-full md:w-auto">
          <Select
            v-model="selectedAnneeUniv"
            :options="anneesUniv"
            optionLabel="libelle"
            optionValue="id"
            placeholder="Filtrer par année universitaire"
            class="w-full md:w-64"
            showClear
          />
          <IconField iconPosition="left" class="w-full md:w-72">
            <InputIcon class="pi pi-search" />
            <InputText v-model="searchQuery" placeholder="Rechercher une semaine..." class="w-full" />
          </IconField>
        </div>
      </div>

      <DataTable
        :value="filteredCalendriers"
        striped-rows
        paginator
        :first="offset"
        :rows="limit"
        :rowsPerPageOptions="rowOptions"
        responsiveLayout="scroll"
      >
        <template #empty>
          <div class="text-center py-8 text-muted-color">
            <i class="pi pi-calendar-times text-3xl mb-2 block"></i>
            <span>Aucune semaine trouvée dans le calendrier.</span>
          </div>
        </template>

        <Column field="id" header="ID" :sortable="true" style="width: 80px;" />
        <Column header="Année Universitaire" style="width: 180px;">
          <template #body="{ data }">
            <span v-if="data.anneeUniversitaire?.libelle" class="font-medium">
              {{ data.anneeUniversitaire.libelle }}
            </span>
            <span v-else class="text-muted-color italic">-</span>
          </template>
        </Column>
        <Column field="semaineFormation" header="Semaine de formation" :sortable="true" style="width: 200px;">
          <template #body="{ data }">
            <Tag severity="info" :value="`Semaine ${data.semaineFormation}`" class="font-bold" />
          </template>
        </Column>
        <Column field="semaineReelle" header="Semaine réelle (Calendrier)" :sortable="true" style="width: 220px;">
          <template #body="{ data }">
            <span class="font-mono font-semibold">Semaine {{ data.semaineReelle }}</span>
          </template>
        </Column>
        <Column header="Date du lundi" :sortable="true">
          <template #body="{ data }">
            <span class="font-medium">{{ formatDate(data.dateLundi) }}</span>
          </template>
        </Column>
        <Column header="Actions" style="width: 140px;" class="text-right">
          <template #body="slotProps">
            <div class="flex justify-end gap-1">
              <ButtonEdit
                tooltip="Modifier cette semaine"
                @click="openEditDialog(slotProps.data)"
              />
              <ButtonDelete
                tooltip="Supprimer cette semaine"
                @confirm-delete="deleteItem(slotProps.data)"
              />
            </div>
          </template>
        </Column>
      </DataTable>
    </template>

    <!-- Modal d'ajout / modification -->
    <Dialog
      v-model:visible="dialogVisible"
      modal
      :header="isEditing ? 'Modifier une semaine de calendrier' : 'Nouvelle semaine de calendrier'"
      :style="{ width: '480px' }"
    >
      <div class="flex flex-col gap-4 py-2">
        <div class="flex flex-col gap-2">
          <label for="cal-annee-univ" class="font-semibold text-sm">Année Universitaire</label>
          <Select
            id="cal-annee-univ"
            v-model="formCalendrier.anneeUniversitaire"
            :options="anneesUniv.map(a => ({ label: a.libelle, value: `/api/structure_annee_universitaires/${a.id}` }))"
            optionLabel="label"
            optionValue="value"
            placeholder="Sélectionner une année universitaire"
            class="w-full"
          />
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div class="flex flex-col gap-2">
            <label for="cal-semaine-formation" class="font-semibold text-sm">Semaine Formation <span class="text-red-500">*</span></label>
            <InputNumber
              id="cal-semaine-formation"
              v-model="formCalendrier.semaineFormation"
              :min="1"
              :max="53"
              class="w-full"
            />
          </div>

          <div class="flex flex-col gap-2">
            <label for="cal-semaine-reelle" class="font-semibold text-sm">Semaine Réelle <span class="text-red-500">*</span></label>
            <InputNumber
              id="cal-semaine-reelle"
              v-model="formCalendrier.semaineReelle"
              :min="1"
              :max="53"
              class="w-full"
            />
          </div>
        </div>

        <div class="flex flex-col gap-2">
          <label for="cal-date-lundi" class="font-semibold text-sm">Date du lundi <span class="text-red-500">*</span></label>
          <DatePicker
            id="cal-date-lundi"
            v-model="formCalendrier.dateLundi"
            dateFormat="dd/mm/yy"
            showIcon
            class="w-full"
          />
        </div>
      </div>

      <template #footer>
        <div class="flex justify-end gap-2 pt-2">
          <Button
            label="Annuler"
            icon="pi pi-times"
            severity="secondary"
            outlined
            @click="dialogVisible = false"
            :disabled="isSubmitting"
          />
          <Button
            :label="isEditing ? 'Mettre à jour' : 'Enregistrer'"
            icon="pi pi-check"
            severity="primary"
            @click="saveCalendrier"
            :loading="isSubmitting"
          />
        </div>
      </template>
    </Dialog>
  </div>
</template>

<style scoped>
</style>
