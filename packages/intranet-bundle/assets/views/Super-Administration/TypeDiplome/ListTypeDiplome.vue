<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import {
  getAllTypeDiplomesService,
  createTypeDiplomeService,
  updateTypeDiplomeService,
  deleteTypeDiplomeService
} from '@requests';
import { ErrorView, ListSkeleton, ButtonDelete, ButtonEdit } from '@components';
import { useToast } from 'primevue/usetoast';

const router = useRouter();
const toast = useToast();

const hasError = ref(false);
const isLoadingTypes = ref(false);
const isSubmitting = ref(false);
const typesDiplomes = ref([]);
const searchQuery = ref('');

const page = ref(0);
const rowOptions = [10, 20, 30, 50];
const offset = computed(() => limit.value * page.value);
const limit = ref(rowOptions[0]);

// Dialog state
const dialogVisible = ref(false);
const isEditing = ref(false);
const formType = ref({
  id: null,
  libelle: '',
  sigle: '',
  apc: false
});

const filteredTypesDiplomes = computed(() => {
  if (!searchQuery.value.trim()) return typesDiplomes.value;
  const q = searchQuery.value.toLowerCase().trim();
  return typesDiplomes.value.filter(t =>
    (t.libelle && t.libelle.toLowerCase().includes(q)) ||
    (t.sigle && t.sigle.toLowerCase().includes(q)) ||
    (t.id && String(t.id).includes(q))
  );
});

onMounted(async () => {
  await getTypeDiplomes();
});

const getTypeDiplomes = async () => {
  try {
    isLoadingTypes.value = true;
    hasError.value = false;
    const res = await getAllTypeDiplomesService();
    typesDiplomes.value = res || [];
  } catch (error) {
    console.error('Erreur lors de la récupération des types de diplômes:', error);
    hasError.value = true;
  } finally {
    isLoadingTypes.value = false;
  }
};

const openNewDialog = () => {
  isEditing.value = false;
  formType.value = {
    id: null,
    libelle: '',
    sigle: '',
    apc: false
  };
  dialogVisible.value = true;
};

const openEditDialog = (type) => {
  isEditing.value = true;
  formType.value = {
    id: type.id,
    libelle: type.libelle || '',
    sigle: type.sigle || '',
    apc: !!type.apc
  };
  dialogVisible.value = true;
};

const saveTypeDiplome = async () => {
  if (!formType.value.libelle || !formType.value.libelle.trim()) {
    toast.add({
      severity: 'warn',
      summary: 'Validation',
      detail: 'Le libellé est obligatoire.',
      life: 3000
    });
    return;
  }

  if (!formType.value.sigle || !formType.value.sigle.trim()) {
    toast.add({
      severity: 'warn',
      summary: 'Validation',
      detail: 'Le sigle est obligatoire.',
      life: 3000
    });
    return;
  }

  isSubmitting.value = true;
  try {
    const payload = {
      libelle: formType.value.libelle.trim(),
      sigle: formType.value.sigle.trim().toUpperCase(),
      apc: Boolean(formType.value.apc)
    };

    if (isEditing.value) {
      await updateTypeDiplomeService(formType.value.id, payload);
      toast.add({
        severity: 'success',
        summary: 'Succès',
        detail: 'Type de diplôme mis à jour avec succès.',
        life: 3000
      });
    } else {
      await createTypeDiplomeService(payload);
      toast.add({
        severity: 'success',
        summary: 'Succès',
        detail: 'Type de diplôme créé avec succès.',
        life: 3000
      });
    }

    dialogVisible.value = false;
    await getTypeDiplomes();
  } catch (error) {
    console.error('Erreur lors de l\'enregistrement du type de diplôme:', error);
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

const toggleApc = async (type) => {
  try {
    await updateTypeDiplomeService(type.id, { apc: !type.apc });
    type.apc = !type.apc;
    toast.add({
      severity: 'success',
      summary: 'Succès',
      detail: `Mode APC ${type.apc ? 'activé' : 'désactivé'} pour ${type.sigle}.`,
      life: 3000
    });
  } catch (error) {
    console.error('Erreur lors du changement de mode APC:', error);
    toast.add({
      severity: 'error',
      summary: 'Erreur',
      detail: 'Impossible de modifier le mode APC.',
      life: 3000
    });
  }
};

const deleteTypeDiplome = async (type) => {
  try {
    await deleteTypeDiplomeService(type.id);
    toast.add({
      severity: 'success',
      summary: 'Succès',
      detail: 'Type de diplôme supprimé avec succès.',
      life: 3000
    });
    await getTypeDiplomes();
  } catch (error) {
    console.error('Erreur lors de la suppression du type de diplôme:', error);
    toast.add({
      severity: 'error',
      summary: 'Erreur',
      detail: 'Impossible de supprimer ce type de diplôme. Des diplômes y sont probablement rattachés.',
      life: 4000
    });
  }
};
</script>

<template>
  <div class="card">
    <div class="card-title mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold">Types de diplômes</h1>
        <p class="text-muted-color">Gérer les types de diplômes et leurs caractéristiques (APC, sigle, libellé).</p>
      </div>
      <div class="flex items-center gap-3">
        <Button
          label="Nouveau type de diplôme"
          icon="pi pi-plus"
          severity="primary"
          @click="openNewDialog"
        />
      </div>
    </div>

    <ErrorView v-if="hasError" />
    <ListSkeleton v-else-if="isLoadingTypes" :count="5" />
    <template v-else>
      <div class="mb-4 flex justify-between items-center">
        <IconField iconPosition="left" class="w-full md:w-80">
          <InputIcon class="pi pi-search" />
          <InputText v-model="searchQuery" placeholder="Rechercher un type de diplôme..." class="w-full" />
        </IconField>
      </div>

      <DataTable
        :value="filteredTypesDiplomes"
        striped-rows
        paginator
        :first="offset"
        :rows="limit"
        :rowsPerPageOptions="rowOptions"
        responsiveLayout="scroll"
      >
        <template #empty>
          <div class="text-center py-8 text-muted-color">
            <i class="pi pi-info-circle text-3xl mb-2 block"></i>
            <span>Aucun type de diplôme trouvé.</span>
          </div>
        </template>

        <Column field="id" header="ID" :sortable="true" style="width: 80px;" />
        <Column field="sigle" header="Sigle" :sortable="true" style="width: 120px;">
          <template #body="{ data }">
            <span class="font-bold text-primary px-2 py-1 bg-primary-50 dark:bg-primary-950/40 rounded">
              {{ data.sigle }}
            </span>
          </template>
        </Column>
        <Column field="libelle" header="Libellé" :sortable="true" class="font-semibold" />
        <Column field="apc" header="Approche Par Compétences (APC)" :sortable="true" style="width: 220px;">
          <template #body="{ data }">
            <div class="flex items-center gap-2 cursor-pointer" @click="toggleApc(data)" v-tooltip.bottom="'Cliquer pour basculer'">
              <Tag
                :severity="data.apc ? 'success' : 'secondary'"
                :value="data.apc ? 'Oui (APC)' : 'Non'"
                :icon="data.apc ? 'pi pi-check' : 'pi pi-times'"
              />
            </div>
          </template>
        </Column>
        <Column header="Actions" style="width: 140px;" class="text-right">
          <template #body="slotProps">
            <div class="flex justify-end gap-1">
              <ButtonEdit
                tooltip="Modifier ce type de diplôme"
                @click="openEditDialog(slotProps.data)"
              />
              <ButtonDelete
                tooltip="Supprimer ce type de diplôme"
                @confirm-delete="deleteTypeDiplome(slotProps.data)"
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
      :header="isEditing ? 'Modifier un type de diplôme' : 'Nouveau type de diplôme'"
      :style="{ width: '500px' }"
    >
      <div class="flex flex-col gap-4 py-2">
        <div class="flex flex-col gap-2">
          <label for="type-sigle" class="font-semibold text-sm">Sigle <span class="text-red-500">*</span></label>
          <InputText
            id="type-sigle"
            v-model="formType.sigle"
            placeholder="Ex: BUT, LP, DUT, MASTER..."
            class="w-full uppercase font-mono font-bold"
            autofocus
          />
        </div>

        <div class="flex flex-col gap-2">
          <label for="type-libelle" class="font-semibold text-sm">Libellé <span class="text-red-500">*</span></label>
          <InputText
            id="type-libelle"
            v-model="formType.libelle"
            placeholder="Ex: Bachelor Universitaire de Technologie"
            class="w-full"
          />
        </div>

        <div class="flex items-center gap-3 pt-2">
          <Checkbox
            id="type-apc"
            v-model="formType.apc"
            :binary="true"
          />
          <label for="type-apc" class="font-medium text-sm cursor-pointer select-none">
            Diplôme sous Approche Par Compétences (APC)
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
            @click="saveTypeDiplome"
            :loading="isSubmitting"
          />
        </div>
      </template>
    </Dialog>
  </div>
</template>

<style scoped>
</style>
