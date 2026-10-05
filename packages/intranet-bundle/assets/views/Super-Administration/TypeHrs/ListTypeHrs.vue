<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import {
  getAllTypeHrsService,
  createTypeHrsService,
  updateTypeHrsService,
  deleteTypeHrsService
} from '@requests';
import { ErrorView, ListSkeleton, ButtonDelete, ButtonEdit, HeaderComponent, Card } from '@components';
import { useToast } from 'primevue/usetoast';

const router = useRouter();
const toast = useToast();

const hasError = ref(false);
const isLoading = ref(false);
const isSubmitting = ref(false);
const typesHrs = ref([]);
const searchQuery = ref('');

const page = ref(0);
const rowOptions = [10, 20, 30, 50];
const offset = computed(() => limit.value * page.value);
const limit = ref(rowOptions[0]);

const typeOptions = [
  { label: 'HRS', value: 'HRS' },
  { label: 'PCA', value: 'PCA' },
  { label: 'PRP', value: 'PRP' },
  { label: 'Suivi', value: 'Suivi' },
  { label: 'Autre', value: 'Autre' }
];

// Dialog state
const dialogVisible = ref(false);
const isEditing = ref(false);
const formTypeHrs = ref({
  id: null,
  libelle: '',
  type: 'HRS',
  incluService: false,
  maximum: 96
});

const filteredTypesHrs = computed(() => {
  if (!searchQuery.value.trim()) return typesHrs.value;
  const q = searchQuery.value.toLowerCase().trim();
  return typesHrs.value.filter(t =>
    (t.libelle && t.libelle.toLowerCase().includes(q)) ||
    (t.type && t.type.toLowerCase().includes(q)) ||
    (t.id && String(t.id).includes(q))
  );
});

onMounted(async () => {
  await getTypesHrs();
});

const getTypesHrs = async () => {
  try {
    isLoading.value = true;
    hasError.value = false;
    const res = await getAllTypeHrsService();
    typesHrs.value = res || [];
  } catch (error) {
    console.error('Erreur lors de la récupération des types d\'heures:', error);
    hasError.value = true;
  } finally {
    isLoading.value = false;
  }
};

const openNewDialog = () => {
  isEditing.value = false;
  formTypeHrs.value = {
    id: null,
    libelle: '',
    type: 'HRS',
    incluService: false,
    maximum: 96
  };
  dialogVisible.value = true;
};

const openEditDialog = (item) => {
  isEditing.value = true;
  formTypeHrs.value = {
    id: item.id,
    libelle: item.libelle || '',
    type: item.type || 'HRS',
    incluService: !!item.incluService,
    maximum: item.maximum !== null && item.maximum !== undefined ? item.maximum : 96
  };
  dialogVisible.value = true;
};

const saveTypeHrs = async () => {
  if (!formTypeHrs.value.libelle || !formTypeHrs.value.libelle.trim()) {
    toast.add({
      severity: 'warn',
      summary: 'Validation',
      detail: 'Le libellé est obligatoire.',
      life: 3000
    });
    return;
  }

  isSubmitting.value = true;
  try {
    const payload = {
      libelle: formTypeHrs.value.libelle.trim(),
      type: formTypeHrs.value.type,
      incluService: Boolean(formTypeHrs.value.incluService),
      maximum: formTypeHrs.value.maximum !== null ? Number(formTypeHrs.value.maximum) : null
    };

    if (isEditing.value) {
      await updateTypeHrsService(formTypeHrs.value.id, payload);
      toast.add({
        severity: 'success',
        summary: 'Succès',
        detail: 'Type d\'heures mis à jour avec succès.',
        life: 3000
      });
    } else {
      await createTypeHrsService(payload);
      toast.add({
        severity: 'success',
        summary: 'Succès',
        detail: 'Type d\'heures créé avec succès.',
        life: 3000
      });
    }

    dialogVisible.value = false;
    await getTypesHrs();
  } catch (error) {
    console.error('Erreur lors de l\'enregistrement du type d\'heures:', error);
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

const toggleIncluService = async (item) => {
  try {
    await updateTypeHrsService(item.id, { incluService: !item.incluService });
    item.incluService = !item.incluService;
    toast.add({
      severity: 'success',
      summary: 'Succès',
      detail: `Inclusion au service ${item.incluService ? 'activée' : 'désactivée'}.`,
      life: 3000
    });
  } catch (error) {
    console.error('Erreur lors du changement de l\'inclusion au service:', error);
    toast.add({
      severity: 'error',
      summary: 'Erreur',
      detail: 'Impossible de modifier l\'inclusion au service.',
      life: 3000
    });
  }
};

const deleteItem = async (item) => {
  try {
    await deleteTypeHrsService(item.id);
    toast.add({
      severity: 'success',
      summary: 'Succès',
      detail: 'Type d\'heures supprimé avec succès.',
      life: 3000
    });
    await getTypesHrs();
  } catch (error) {
    console.error('Erreur lors de la suppression du type d\'heures:', error);
    toast.add({
      severity: 'error',
      summary: 'Erreur',
      detail: 'Impossible de supprimer ce type d\'heures. Des affectations y sont probablement rattachées.',
      life: 4000
    });
  }
};
</script>

<template>
  <HeaderComponent
    icon="pi pi-clock"
    color="orange"
    titre="Types d'heures (HRS / PCA / PRP)"
    description="Gérer les types d'heures complémentaires, référentiels et plafonds horaires."
    :show-back="true"
    back-url="/intranet/super-administration"
  >
    <template #actions>
      <Button
        label="Nouveau type d'heures"
        icon="pi pi-plus"
        severity="primary"
        @click="openNewDialog"
      />
    </template>
  </HeaderComponent>

  <Card>
    <ErrorView v-if="hasError" />
    <ListSkeleton v-else-if="isLoading" :count="5" />
    <template v-else>
      <div class="mb-4 flex justify-between items-center">
        <IconField iconPosition="left" class="w-full md:w-80">
          <InputIcon class="pi pi-search" />
          <InputText v-model="searchQuery" placeholder="Rechercher un type d'heures..." class="w-full" />
        </IconField>
      </div>

      <DataTable
        :value="filteredTypesHrs"
        striped-rows
        paginator
        :first="offset"
        :rows="limit"
        :rowsPerPageOptions="rowOptions"
        responsiveLayout="scroll"
      >
        <template #empty>
          <div class="text-center py-8 text-muted-color">
            <i class="pi pi-clock text-3xl mb-2 block"></i>
            <span>Aucun type d'heures trouvé.</span>
          </div>
        </template>

        <Column field="id" header="ID" :sortable="true" style="width: 80px;" />
        <Column field="type" header="Catégorie" :sortable="true" style="width: 140px;">
          <template #body="{ data }">
            <Tag
              :severity="data.type === 'HRS' ? 'info' : data.type === 'PCA' ? 'success' : data.type === 'PRP' ? 'warn' : 'secondary'"
              :value="data.type || 'Autre'"
              class="font-mono font-bold"
            />
          </template>
        </Column>
        <Column field="libelle" header="Libellé" :sortable="true" class="font-semibold" />
        <Column field="maximum" header="Plafond max (heures)" :sortable="true" style="width: 180px;">
          <template #body="{ data }">
            <span v-if="data.maximum !== null" class="font-bold text-primary">
              {{ data.maximum }} h
            </span>
            <span v-else class="text-muted-color italic">Non plafonné</span>
          </template>
        </Column>
        <Column field="incluService" header="Inclus dans le service" :sortable="true" style="width: 200px;">
          <template #body="{ data }">
            <div class="flex items-center gap-2 cursor-pointer" @click="toggleIncluService(data)" v-tooltip.bottom="'Cliquer pour basculer'">
              <Tag
                :severity="data.incluService ? 'success' : 'secondary'"
                :value="data.incluService ? 'Oui' : 'Non'"
                :icon="data.incluService ? 'pi pi-check' : 'pi pi-times'"
              />
            </div>
          </template>
        </Column>
        <Column header="Actions" style="width: 140px;" class="text-right">
          <template #body="slotProps">
            <div class="flex justify-end gap-1">
              <ButtonEdit
                tooltip="Modifier ce type d'heures"
                @click="openEditDialog(slotProps.data)"
              />
              <ButtonDelete
                tooltip="Supprimer ce type d'heures"
                @confirm-delete="deleteItem(slotProps.data)"
              />
            </div>
          </template>
        </Column>
      </DataTable>
    </template>
  </Card>

  <!-- Modal d'ajout / modification -->
  <Dialog
    v-model:visible="dialogVisible"
    modal
    :header="isEditing ? 'Modifier un type d\'heures' : 'Nouveau type d\'heures'"
    :style="{ width: '500px' }"
  >
    <div class="flex flex-col gap-4 py-2">
      <div class="flex flex-col gap-2">
        <label for="hrs-type" class="font-semibold text-sm">Catégorie <span class="text-red-500">*</span></label>
        <Select
          id="hrs-type"
          v-model="formTypeHrs.type"
          :options="typeOptions"
          optionLabel="label"
          optionValue="value"
          placeholder="Sélectionner une catégorie"
          class="w-full font-bold"
        />
      </div>

      <div class="flex flex-col gap-2">
        <label for="hrs-libelle" class="font-semibold text-sm">Libellé <span class="text-red-500">*</span></label>
        <InputText
          id="hrs-libelle"
          v-model="formTypeHrs.libelle"
          placeholder="Ex: Responsabilité de diplôme, Suivi stage..."
          class="w-full"
          autofocus
        />
      </div>

      <div class="flex flex-col gap-2">
        <label for="hrs-maximum" class="font-semibold text-sm">Plafond maximum (heures)</label>
        <InputNumber
          id="hrs-maximum"
          v-model="formTypeHrs.maximum"
          :min="0"
          :max="1000"
          :minFractionDigits="0"
          :maxFractionDigits="2"
          suffix=" h"
          class="w-full"
        />
      </div>

      <div class="flex items-center gap-3 pt-2">
        <Checkbox
          id="hrs-inclu-service"
          v-model="formTypeHrs.incluService"
          :binary="true"
        />
        <label for="hrs-inclu-service" class="font-medium text-sm cursor-pointer select-none">
          Inclus dans le calcul du service obligatoire
        </label>
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
          @click="saveTypeHrs"
          :loading="isSubmitting"
        />
      </div>
    </template>
  </Dialog>
</template>

<style scoped>
</style>
