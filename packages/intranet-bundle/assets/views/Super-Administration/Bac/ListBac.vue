<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import { getAllBacsService, createBacService, updateBacService, deleteBacService } from '@requests';
import { ErrorView, ListSkeleton, ButtonDelete, ButtonEdit } from '@components';
import { useToast } from 'primevue/usetoast';

const router = useRouter();
const toast = useToast();

const hasError = ref(false);
const isLoadingBacs = ref(false);
const isSubmitting = ref(false);
const bacs = ref([]);
const searchQuery = ref('');

const page = ref(0);
const rowOptions = [10, 20, 30, 50];
const offset = computed(() => limit.value * page.value);
const limit = ref(rowOptions[0]);

// Dialog state
const dialogVisible = ref(false);
const isEditing = ref(false);
const formBac = ref({
  id: null,
  libelle: '',
  libelleLong: '',
  codeApogee: ''
});

const filteredBacs = computed(() => {
  if (!searchQuery.value.trim()) return bacs.value;
  const q = searchQuery.value.toLowerCase().trim();
  return bacs.value.filter(b =>
    (b.libelle && b.libelle.toLowerCase().includes(q)) ||
    (b.libelleLong && b.libelleLong.toLowerCase().includes(q)) ||
    (b.libelle_long && b.libelle_long.toLowerCase().includes(q)) ||
    (b.codeApogee && b.codeApogee.toLowerCase().includes(q)) ||
    (b.id && String(b.id).includes(q))
  );
});

onMounted(async () => {
  await getBacs();
});

const getBacs = async () => {
  try {
    isLoadingBacs.value = true;
    hasError.value = false;
    const res = await getAllBacsService();
    bacs.value = res || [];
  } catch (error) {
    console.error('Erreur lors de la récupération des bacs:', error);
    hasError.value = true;
  } finally {
    isLoadingBacs.value = false;
  }
};

const openNewDialog = () => {
  isEditing.value = false;
  formBac.value = {
    id: null,
    libelle: '',
    libelleLong: '',
    codeApogee: ''
  };
  dialogVisible.value = true;
};

const openEditDialog = (bac) => {
  isEditing.value = true;
  formBac.value = {
    id: bac.id,
    libelle: bac.libelle || '',
    libelleLong: bac.libelleLong || bac.libelle_long || '',
    codeApogee: bac.codeApogee || ''
  };
  dialogVisible.value = true;
};

const saveBac = async () => {
  if (!formBac.value.libelle || !formBac.value.libelle.trim()) {
    toast.add({
      severity: 'warn',
      summary: 'Validation',
      detail: 'Le libellé court est obligatoire.',
      life: 3000
    });
    return;
  }

  isSubmitting.value = true;
  try {
    const payload = {
      libelle: formBac.value.libelle.trim(),
      libelleLong: formBac.value.libelleLong ? formBac.value.libelleLong.trim() : formBac.value.libelle.trim(),
      codeApogee: formBac.value.codeApogee ? formBac.value.codeApogee.trim() : null
    };

    if (isEditing.value) {
      await updateBacService(formBac.value.id, payload);
      toast.add({
        severity: 'success',
        summary: 'Succès',
        detail: 'Bac mis à jour avec succès.',
        life: 3000
      });
    } else {
      await createBacService(payload);
      toast.add({
        severity: 'success',
        summary: 'Succès',
        detail: 'Bac créé avec succès.',
        life: 3000
      });
    }

    dialogVisible.value = false;
    await getBacs();
  } catch (error) {
    console.error('Erreur lors de l\'enregistrement du bac:', error);
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

const deleteBac = async (bac) => {
  try {
    await deleteBacService(bac.id);
    toast.add({
      severity: 'success',
      summary: 'Succès',
      detail: 'Bac supprimé avec succès.',
      life: 3000
    });
    await getBacs();
  } catch (error) {
    console.error('Erreur lors de la suppression du bac:', error);
    toast.add({
      severity: 'error',
      summary: 'Erreur',
      detail: 'Impossible de supprimer ce bac. Vérifiez qu\'aucun étudiant n\'y est rattaché.',
      life: 4000
    });
  }
};
</script>

<template>
  <div class="card">
    <div class="card-title mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold">Gestion des types de bacs</h1>
        <p class="text-muted-color">Gérer les types de baccalauréat disponibles dans l'établissement.</p>
      </div>
      <div class="flex items-center gap-3">
        <Button
          label="Nouveau bac"
          icon="pi pi-plus"
          severity="primary"
          @click="openNewDialog"
        />
      </div>
    </div>

    <ErrorView v-if="hasError" />
    <ListSkeleton v-else-if="isLoadingBacs" :count="5" />
    <template v-else>
      <div class="mb-4 flex justify-between items-center">
        <IconField iconPosition="left" class="w-full md:w-80">
          <InputIcon class="pi pi-search" />
          <InputText v-model="searchQuery" placeholder="Rechercher un bac..." class="w-full" />
        </IconField>
      </div>

      <DataTable
        :value="filteredBacs"
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
            <span>Aucun bac trouvé.</span>
          </div>
        </template>

        <Column field="id" header="ID" :sortable="true" style="width: 80px;" />
        <Column field="libelle" header="Libellé court" :sortable="true" class="font-semibold" />
        <Column header="Libellé long" :sortable="true">
          <template #body="{ data }">
            {{ data.libelleLong || data.libelle_long || data.libelle }}
          </template>
        </Column>
        <Column field="codeApogee" header="Code Apogée" :sortable="true" style="width: 140px;">
          <template #body="{ data }">
            <Tag v-if="data.codeApogee" severity="secondary" :value="data.codeApogee" />
            <span v-else class="text-muted-color italic">-</span>
          </template>
        </Column>
        <Column header="Actions" style="width: 140px;" class="text-right">
          <template #body="slotProps">
            <div class="flex justify-end gap-1">
              <ButtonEdit
                tooltip="Modifier ce bac"
                @click="openEditDialog(slotProps.data)"
              />
              <ButtonDelete
                tooltip="Supprimer ce bac"
                @confirm-delete="deleteBac(slotProps.data)"
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
      :header="isEditing ? 'Modifier un bac' : 'Nouveau type de bac'"
      :style="{ width: '450px' }"
    >
      <div class="flex flex-col gap-4 py-2">
        <div class="flex flex-col gap-2">
          <label for="bac-libelle" class="font-semibold text-sm">Libellé court <span class="text-red-500">*</span></label>
          <InputText
            id="bac-libelle"
            v-model="formBac.libelle"
            placeholder="Ex: Général, STI2D, STMG..."
            class="w-full"
            autofocus
          />
        </div>

        <div class="flex flex-col gap-2">
          <label for="bac-libelle-long" class="font-semibold text-sm">Libellé long</label>
          <InputText
            id="bac-libelle-long"
            v-model="formBac.libelleLong"
            placeholder="Ex: Baccalauréat Général"
            class="w-full"
          />
        </div>

        <div class="flex flex-col gap-2">
          <label for="bac-code-apogee" class="font-semibold text-sm">Code Apogée</label>
          <InputText
            id="bac-code-apogee"
            v-model="formBac.codeApogee"
            placeholder="Ex: S, ES, L, 0001..."
            class="w-full font-mono"
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
            @click="saveBac"
            :loading="isSubmitting"
          />
        </div>
      </template>
    </Dialog>
  </div>
</template>

<style scoped>
</style>
