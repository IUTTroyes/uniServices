<script setup>
import { computed, onMounted, ref } from "vue";
import {
  getAllDepartementsService,
  createDepartementService,
  updateDepartementService,
  deleteDepartementService
} from "@requests";
import { ErrorView, ListSkeleton, ButtonDelete, ButtonEdit, ButtonSave, HeaderComponent, Card } from "@components";
import { useToast } from "primevue/usetoast";

const toast = useToast();
const hasError = ref(false);
const isLoadingDepartement = ref(false);
const isSubmitting = ref(false);
const departements = ref([]);
const searchQuery = ref('');

const page = ref(0);
const rowOptions = [10, 20, 30];
const offset = computed(() => limit.value * page.value);
const limit = ref(rowOptions[0]);

// Dialog state
const dialogVisible = ref(false);
const isEditing = ref(false);
const formDepartement = ref({
  id: null,
  libelle: '',
  couleur: '',
  telContact: '',
  siteWeb: '',
  description: '',
  actif: true
});

const filteredDepartements = computed(() => {
  if (!searchQuery.value.trim()) return departements.value;
  const q = searchQuery.value.toLowerCase().trim();
  return departements.value.filter(d =>
    (d.libelle && d.libelle.toLowerCase().includes(q)) ||
    (d.telContact && d.telContact.toLowerCase().includes(q)) ||
    (d.siteWeb && d.siteWeb.toLowerCase().includes(q)) ||
    (d.id && String(d.id).includes(q))
  );
});

onMounted(async () => {
  await getDepartements();
});

const getDepartements = async () => {
  try {
    isLoadingDepartement.value = true;
    hasError.value = false;
    const res = await getAllDepartementsService('/administration');
    departements.value = res || [];
  } catch (error) {
    console.error("Erreur lors de la récupération des départements:", error);
    hasError.value = true;
  } finally {
    isLoadingDepartement.value = false;
  }
};

const formatColor = (color) => {
  if (!color) return null;
  return color.startsWith('#') ? color : `#${color}`;
};

const openNewDialog = () => {
  isEditing.value = false;
  formDepartement.value = {
    id: null,
    libelle: '',
    couleur: '3B82F6',
    telContact: '',
    siteWeb: '',
    description: '',
    actif: true
  };
  dialogVisible.value = true;
};

const openEditDialog = (dept) => {
  isEditing.value = true;
  formDepartement.value = {
    id: dept.id,
    libelle: dept.libelle || '',
    couleur: dept.couleur ? dept.couleur.replace('#', '') : '3B82F6',
    telContact: dept.telContact || dept.tel_contact || '',
    siteWeb: dept.siteWeb || dept.site_web || '',
    description: dept.description || '',
    actif: dept.actif !== undefined ? !!dept.actif : true
  };
  dialogVisible.value = true;
};

const saveDepartement = async () => {
  if (!formDepartement.value.libelle || !formDepartement.value.libelle.trim()) {
    toast.add({
      severity: 'warn',
      summary: 'Validation',
      detail: 'Le libellé du département est obligatoire.',
      life: 3000
    });
    return;
  }

  isSubmitting.value = true;
  try {
    const payload = {
      libelle: formDepartement.value.libelle.trim(),
      couleur: formDepartement.value.couleur ? formDepartement.value.couleur.replace('#', '') : null,
      telContact: formDepartement.value.telContact ? formDepartement.value.telContact.trim() : null,
      siteWeb: formDepartement.value.siteWeb ? formDepartement.value.siteWeb.trim() : null,
      description: formDepartement.value.description ? formDepartement.value.description.trim() : null,
      actif: Boolean(formDepartement.value.actif)
    };

    if (isEditing.value) {
      await updateDepartementService(formDepartement.value.id, payload);
      toast.add({
        severity: 'success',
        summary: 'Succès',
        detail: 'Département mis à jour avec succès.',
        life: 3000
      });
    } else {
      await createDepartementService(payload);
      toast.add({
        severity: 'success',
        summary: 'Succès',
        detail: 'Département créé avec succès.',
        life: 3000
      });
    }

    dialogVisible.value = false;
    await getDepartements();
  } catch (error) {
    console.error("Erreur lors de l'enregistrement du département:", error);
    toast.add({
      severity: 'error',
      summary: 'Erreur',
      detail: "Une erreur est survenue lors de l'enregistrement.",
      life: 3000
    });
  } finally {
    isSubmitting.value = false;
  }
};

const toggleActif = async (departement) => {
  try {
    const data = {
      actif: !departement.actif,
    };
    await updateDepartementService(departement.id, data);
    departement.actif = !departement.actif;
    toast.add({
      severity: 'success',
      summary: 'Succès',
      detail: `Département ${departement.libelle} ${departement.actif ? 'activé' : 'suspendu'}.`,
      life: 3000
    });
  } catch (error) {
    console.error("Erreur lors de la mise à jour du département:", error);
    toast.add({
      severity: 'error',
      summary: 'Erreur',
      detail: 'Une erreur est survenue lors de la mise à jour.',
      life: 3000
    });
    hasError.value = true;
  }
};

const deleteDepartement = async (dept) => {
  try {
    await deleteDepartementService(dept.id);
    toast.add({
      severity: 'success',
      summary: 'Succès',
      detail: 'Département supprimé avec succès.',
      life: 3000
    });
    await getDepartements();
  } catch (error) {
    console.error("Erreur lors de la suppression du département:", error);
    toast.add({
      severity: 'error',
      summary: 'Erreur',
      detail: 'Impossible de supprimer ce département. Des diplômes ou personnels y sont rattachés.',
      life: 4000
    });
  }
};
</script>

<template>
  <HeaderComponent
    icon="pi pi-building"
    color="indigo"
    titre="Gestion des départements"
    description="Gérer les départements d'enseignement et leur configuration."
    :show-back="true"
    back-url="/intranet/super-administration"
  >
    <template #actions>
      <Button
        label="Nouveau département"
        icon="pi pi-plus"
        severity="primary"
        @click="openNewDialog"
      />
    </template>
  </HeaderComponent>

  <Card>
    <ErrorView v-if="hasError" />
    <ListSkeleton v-else-if="isLoadingDepartement" :count="5" />
    <template v-else>
      <div class="mb-4 flex justify-between items-center">
        <IconField iconPosition="left" class="w-full md:w-80">
          <InputIcon class="pi pi-search" />
          <InputText v-model="searchQuery" placeholder="Rechercher un département..." class="w-full" />
        </IconField>
      </div>

      <DataTable
        :value="filteredDepartements"
        striped-rows
        paginator
        :first="offset"
        :rows="limit"
        :rowsPerPageOptions="rowOptions"
        responsiveLayout="scroll"
      >
        <template #empty>
          <div class="text-center py-8 text-muted-color">
            <i class="pi pi-building text-3xl mb-2 block"></i>
            <span>Aucun département trouvé.</span>
          </div>
        </template>

        <Column field="id" header="ID" :sortable="true" style="width: 80px;" />
        <Column field="libelle" header="Libellé" :sortable="true" class="font-semibold">
          <template #body="{ data }">
            <div class="flex items-center gap-2">
              <span
                v-if="data.couleur"
                class="w-3.5 h-3.5 rounded-full inline-block shrink-0 border border-surface-300 dark:border-surface-600 shadow-xs"
                :style="{ backgroundColor: formatColor(data.couleur) }"
              />
              <span>{{ data.libelle }}</span>
            </div>
          </template>
        </Column>
        <Column header="Couleur" style="width: 140px;">
          <template #body="{ data }">
            <div
              v-if="data.couleur"
              class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold border"
              :style="{
                backgroundColor: formatColor(data.couleur) + '1A',
                borderColor: formatColor(data.couleur) + '4D',
                color: formatColor(data.couleur)
              }"
            >
              <span
                class="w-2.5 h-2.5 rounded-full shrink-0 shadow-xs"
                :style="{ backgroundColor: formatColor(data.couleur) }"
              />
              <span class="font-mono">{{ formatColor(data.couleur).toUpperCase() }}</span>
            </div>
            <span v-else class="text-muted-color italic">-</span>
          </template>
        </Column>
        <Column field="telContact" header="Téléphone" :sortable="true" style="width: 180px;">
          <template #body="{ data }">
            <span v-if="data.telContact || data.tel_contact">{{ data.telContact || data.tel_contact }}</span>
            <span v-else class="text-muted-color italic">-</span>
          </template>
        </Column>
        <Column field="siteWeb" header="Site Web" :sortable="true" style="width: 220px;">
          <template #body="{ data }">
            <a
              v-if="data.siteWeb || data.site_web"
              :href="data.siteWeb || data.site_web"
              target="_blank"
              class="text-primary hover:underline flex items-center gap-1 text-sm truncate max-w-[200px]"
            >
              <span>{{ data.siteWeb || data.site_web }}</span>
              <i class="pi pi-external-link text-xs" />
            </a>
            <span v-else class="text-muted-color italic">-</span>
          </template>
        </Column>
        <Column header="Statut" style="width: 140px;">
          <template #body="{ data }">
            <div class="cursor-pointer" @click="toggleActif(data)" v-tooltip.bottom="'Cliquer pour basculer'">
              <Tag
                :severity="data.actif ? 'success' : 'secondary'"
                :value="data.actif ? 'Actif' : 'Inactif'"
                :icon="data.actif ? 'pi pi-check' : 'pi pi-times'"
              />
            </div>
          </template>
        </Column>
        <Column header="Actions" style="width: 140px;" class="text-right">
          <template #body="slotProps">
            <div class="flex justify-end gap-1">
              <ButtonEdit
                tooltip="Modifier ce département"
                @click="openEditDialog(slotProps.data)"
              />
              <ButtonDelete
                tooltip="Supprimer ce département"
                @confirm-delete="deleteDepartement(slotProps.data)"
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
    :header="isEditing ? 'Modifier un département' : 'Nouveau département'"
    :style="{ width: '520px' }"
  >
    <div class="flex flex-col gap-4 py-2">
      <div class="flex flex-col gap-2">
        <label for="dept-libelle" class="font-semibold text-sm">Libellé <span class="text-red-500">*</span></label>
        <InputText
          id="dept-libelle"
          v-model="formDepartement.libelle"
          placeholder="Ex: Informatique, MMI, GEII, TC..."
          class="w-full"
          autofocus
        />
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="flex flex-col gap-2">
          <label for="dept-couleur" class="font-semibold text-sm">Couleur représentative</label>
          <div class="flex items-center gap-2">
            <ColorPicker id="dept-couleur" v-model="formDepartement.couleur" />
            <div class="relative flex-1">
              <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-muted-color font-mono text-sm">#</span>
              <InputText
                :model-value="formDepartement.couleur ? formDepartement.couleur.replace('#', '') : ''"
                @update:model-value="val => formDepartement.couleur = val ? val.replace('#', '') : ''"
                placeholder="3B82F6"
                class="w-full font-mono text-sm pl-7"
              />
            </div>
          </div>
        </div>

        <div class="flex flex-col gap-2">
          <label for="dept-tel" class="font-semibold text-sm">Téléphone de contact</label>
          <InputText
            id="dept-tel"
            v-model="formDepartement.telContact"
            placeholder="Ex: 03 25 42 46 00"
            class="w-full"
          />
        </div>
      </div>

      <div class="flex flex-col gap-2">
        <label for="dept-site" class="font-semibold text-sm">Site Web</label>
        <InputText
          id="dept-site"
          v-model="formDepartement.siteWeb"
          placeholder="Ex: https://iut-troyes.univ-reims.fr"
          class="w-full"
        />
      </div>

      <div class="flex flex-col gap-2">
        <label for="dept-desc" class="font-semibold text-sm">Description</label>
        <Textarea
          id="dept-desc"
          v-model="formDepartement.description"
          rows="3"
          placeholder="Description du département d'enseignement..."
          class="w-full"
        />
      </div>

      <div class="flex items-center gap-3 pt-2">
        <Checkbox
          id="dept-actif"
          v-model="formDepartement.actif"
          :binary="true"
        />
        <label for="dept-actif" class="font-medium text-sm cursor-pointer select-none">
          Département actif
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
          @click="saveDepartement"
          :loading="isSubmitting"
        />
      </div>
    </template>
  </Dialog>
</template>

<style scoped>
</style>


