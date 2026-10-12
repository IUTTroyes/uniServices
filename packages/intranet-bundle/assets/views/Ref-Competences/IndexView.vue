<script setup lang="ts">
import { computed, onMounted, ref, watch } from "vue";
import { useUsersStore } from '@stores';
import { getReferentiels } from '@requests';
import { useConfirm } from "primevue/useconfirm";
import { useToast } from "primevue/usetoast";
import api from '@helpers/axios';
import {
  ErrorView,
  ListSkeleton,
  SimpleSkeleton,
  HeaderComponent
} from "@components";
import AfficheReferentielCompetences from "@/components/Pn/AfficheReferentielCompetences.vue";

const usersStore = useUsersStore();
const confirm = useConfirm();
const toast = useToast();

const hasError = ref(false);
const isLoading = ref(true);
const isSyncing = ref(false);
const isCreating = ref(false);
const createDialogVisible = ref(false);

const selectedDepartementId = ref<number | null>(null);
const referentiels = ref<any[]>([]);
const selectedReferentiel = ref<any>(null);

// Formulaire nouveau référentiel
const newReferentiel = ref({
  libelle: '',
  anneePublication: new Date().getFullYear(),
  description: '',
  oreofId: ''
});

// Liste des départements rattachés à l'utilisateur courant
const userDepartements = computed(() => {
  if (Array.isArray(usersStore.departements) && usersStore.departements.length > 0) {
    return usersStore.departements;
  }
  if (usersStore.departementDefaut?.id) {
    return [usersStore.departementDefaut];
  }
  return [];
});

const currentDepartement = computed(() => {
  return userDepartements.value.find(d => d.id === selectedDepartementId.value) || usersStore.departementDefaut;
});

onMounted(async () => {
  try {
    if (!usersStore.isLoaded) {
      await usersStore.getUser();
    }

    // Sélectionner le département par défaut ou le premier accessible
    if (usersStore.departementDefaut?.id) {
      selectedDepartementId.value = usersStore.departementDefaut.id;
    } else if (userDepartements.value.length > 0) {
      selectedDepartementId.value = userDepartements.value[0].id;
    }

    await loadReferentiels();
  } catch (error) {
    console.error('Erreur initialisation référentiels:', error);
    hasError.value = true;
  }
});

// Recharger quand le département sélectionné change
watch(selectedDepartementId, async (newDeptId, oldDeptId) => {
  if (newDeptId && newDeptId !== oldDeptId) {
    await loadReferentiels();
  }
});

const loadReferentiels = async () => {
  if (!selectedDepartementId.value) {
    isLoading.value = false;
    return;
  }

  try {
    isLoading.value = true;
    const data = await getReferentiels(selectedDepartementId.value);
    
    // Filtrer strictement sur le département sélectionné (garantie supplémentaire côté front)
    const list = Array.isArray(data) ? data : [];
    referentiels.value = list.filter(r => {
      const deptId = r.departement?.id ?? r.departementId;
      return !deptId || deptId === selectedDepartementId.value;
    });

    if (referentiels.value.length > 0) {
      // Conserver la sélection si elle existe toujours, sinon sélectionner le premier
      const existing = referentiels.value.find(r => r.id === selectedReferentiel.value?.id);
      selectedReferentiel.value = existing || referentiels.value[0];
    } else {
      selectedReferentiel.value = null;
    }
  } catch (error) {
    console.error('Erreur lors du chargement des référentiels:', error);
    hasError.value = true;
  } finally {
    isLoading.value = false;
  }
};

const selectReferentiel = (referentiel: any) => {
  selectedReferentiel.value = referentiel;
};

const synchronisationOreof = () => {
  if (!selectedDepartementId.value) return;

  confirm.require({
    message: `Voulez-vous synchroniser les référentiels de compétences pour le département ${currentDepartement.value?.libelle || ''} depuis ORéOF ? Les données existantes seront mises à jour.`,
    header: 'Synchronisation ORéOF',
    icon: 'pi pi-exclamation-triangle',
    rejectProps: {
      label: 'Annuler',
      severity: 'secondary',
      outlined: true
    },
    acceptProps: {
      label: 'Lancer la synchronisation',
      icon: 'pi pi-refresh'
    },
    accept: async () => {
      try {
        isSyncing.value = true;
        toast.add({
          severity: 'info',
          summary: 'Synchronisation lancée',
          detail: 'Récupération du référentiel en cours...',
          life: 3000
        });

        await api.post('/api/oreof/ref-competences/synchronisation', {
          departementId: selectedDepartementId.value,
          diplomeId: null
        });

        toast.add({
          severity: 'success',
          summary: 'Succès',
          detail: 'Synchronisation terminée avec succès.',
          life: 3000
        });

        await loadReferentiels();
      } catch (error) {
        console.error('Erreur lors de la synchronisation:', error);
        toast.add({
          severity: 'error',
          summary: 'Erreur',
          detail: 'Une erreur est survenue lors de la synchronisation.',
          life: 4000
        });
      } finally {
        isSyncing.value = false;
      }
    }
  });
};

const openCreateDialog = () => {
  newReferentiel.value = {
    libelle: '',
    anneePublication: new Date().getFullYear(),
    description: '',
    oreofId: ''
  };
  createDialogVisible.value = true;
};

const handleCreateReferentiel = async () => {
  if (!newReferentiel.value.libelle || !selectedDepartementId.value) {
    toast.add({
      severity: 'warn',
      summary: 'Champ requis',
      detail: 'Le libellé du référentiel est obligatoire.',
      life: 3000
    });
    return;
  }

  try {
    isCreating.value = true;
    const payload = {
      libelle: newReferentiel.value.libelle,
      anneePublication: Number(newReferentiel.value.anneePublication) || new Date().getFullYear(),
      description: newReferentiel.value.description || null,
      departement: `/api/structure_departements/${selectedDepartementId.value}`
    };

    const res = await api.post('/api/apc_referentiels', payload, {
      headers: { 'Content-Type': 'application/ld+json' }
    });

    toast.add({
      severity: 'success',
      summary: 'Créé',
      detail: 'Le référentiel a été créé avec succès.',
      life: 3000
    });

    createDialogVisible.value = false;
    await loadReferentiels();
    if (res.data) {
      selectedReferentiel.value = res.data;
    }
  } catch (error) {
    console.error('Erreur création référentiel:', error);
    toast.add({
      severity: 'error',
      summary: 'Erreur',
      detail: 'Impossible de créer le référentiel.',
      life: 4000
    });
  } finally {
    isCreating.value = false;
  }
};
</script>

<template>
  <div>
    <HeaderComponent
      icon="pi pi-book"
      titre="Référentiels de compétences"
      description="Gestion et consultation des référentiels de compétences et apprentissages critiques du B.U.T."
    />

    <ErrorView v-if="hasError" />

    <div v-else class="card mt-4 p-5 sm:p-6 bg-surface-0 dark:bg-surface-900 rounded-2xl border border-surface-200 dark:border-surface-800 shadow-sm">
      <!-- Barre d'outils supérieure : Sélecteur de département & Actions -->
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-5 mb-5 border-b border-surface-200 dark:border-surface-800">
        <div class="flex flex-wrap items-center gap-3">
        
        </div>

        <!-- Boutons d'action -->
        <div class="flex items-center gap-2.5 shrink-0">
          <Button
            label="Synchroniser ORéOF"
            icon="pi pi-refresh"
            severity="secondary"
            outlined
            size="small"
            :loading="isSyncing"
            @click="synchronisationOreof"
          />
          <Button
            label="Ajouter un référentiel"
            icon="pi pi-plus"
            severity="primary"
            size="small"
            @click="openCreateDialog"
          />
        </div>
      </div>

      <!-- Chargement initial -->
      <div v-if="isLoading" class="py-6 space-y-4">
        <SimpleSkeleton class="w-1/3 h-10" />
        <ListSkeleton class="mt-4" />
      </div>

      <!-- Contenu principal -->
      <div v-else>
        <!-- Onglets des référentiels du département -->
        <div v-if="referentiels.length > 0">
          <Tabs :value="selectedReferentiel?.id || referentiels[0]?.id" scrollable>
            <TabList class="bg-surface-50 dark:bg-surface-800/50 p-1.5 rounded-xl border border-surface-200 dark:border-surface-700">
              <Tab
                v-for="referentiel in referentiels"
                :key="referentiel.id"
                :value="referentiel.id"
                class="flex items-center gap-2 px-4 py-2.5 rounded-lg transition-all"
                @click="selectReferentiel(referentiel)"
              >
                <i class="pi pi-book text-xs text-muted-color"></i>
                <span class="font-medium text-sm">{{ referentiel.libelle }}</span>
                <span
                  v-if="referentiel.anneePublication"
                  class="px-2 py-0.5 rounded-md text-xs font-semibold bg-surface-200 dark:bg-surface-700 text-surface-700 dark:text-surface-300"
                >
                  {{ referentiel.anneePublication }}
                </span>
              </Tab>
            </TabList>
          </Tabs>

          <!-- Détail du référentiel sélectionné -->
          <div v-if="selectedReferentiel" class="mt-6">
            <!-- Carte résumé du référentiel -->
            <div class="p-5 rounded-2xl bg-gradient-to-br from-surface-50 to-surface-100/60 dark:from-surface-800/40 dark:to-surface-800/20 border border-surface-200 dark:border-surface-700 mb-6">
              <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div>
                  <div class="flex items-center gap-2">
                    <span class="text-xs uppercase tracking-wider font-bold text-primary">
                      {{ currentDepartement?.libelle || 'Département' }}
                    </span>
                    <span class="text-muted-color text-xs">•</span>
                    <span class="text-xs text-muted-color">
                      Publication : {{ selectedReferentiel.anneePublication || 'N/C' }}
                    </span>
                  </div>
                  <h3 class="text-xl sm:text-2xl font-bold text-surface-900 dark:text-surface-0 mt-1">
                    {{ selectedReferentiel.libelle }}
                  </h3>
                </div>

                <div class="flex items-center gap-2">
                  <Button
                    label="Re-synchroniser"
                    icon="pi pi-sync"
                    severity="secondary"
                    text
                    size="small"
                    :loading="isSyncing"
                    @click="synchronisationOreof"
                  />
                </div>
              </div>

              <p
                v-if="selectedReferentiel.description"
                class="mt-3 text-sm text-surface-600 dark:text-surface-300 leading-relaxed border-t border-surface-200 dark:border-surface-700/60 pt-3"
              >
                {{ selectedReferentiel.description }}
              </p>
            </div>

            <!-- Affichage des compétences du référentiel -->
            <AfficheReferentielCompetences :referentiel="selectedReferentiel" />
          </div>
        </div>

        <!-- Aucun référentiel pour ce département -->
        <div
          v-else
          class="text-center py-16 px-4 border border-dashed rounded-2xl border-surface-300 dark:border-surface-700 bg-surface-50/40 dark:bg-surface-900/40"
        >
          <div class="w-16 h-16 rounded-2xl bg-primary-50 dark:bg-primary-950/40 text-primary flex items-center justify-center mx-auto mb-4 text-2xl">
            <i class="pi pi-book"></i>
          </div>
          <h3 class="text-lg font-bold text-surface-900 dark:text-surface-0">
            Aucun référentiel de compétences
          </h3>
          <p class="text-sm text-muted-color max-w-md mx-auto mt-2 leading-relaxed">
            Aucun référentiel n'est actuellement rattaché au département <strong>{{ currentDepartement?.libelle }}</strong>.
            Vous pouvez synchroniser depuis ORéOF ou créer un nouveau référentiel.
          </p>
          <div class="flex items-center justify-center gap-3 mt-6">
            <Button
              label="Synchroniser depuis ORéOF"
              icon="pi pi-refresh"
              severity="primary"
              :loading="isSyncing"
              @click="synchronisationOreof"
            />
            <Button
              label="Créer manuellement"
              icon="pi pi-plus"
              severity="secondary"
              outlined
              @click="openCreateDialog"
            />
          </div>
        </div>
      </div>
    </div>

    <!-- Dialog Nouveau Référentiel -->
    <Dialog
      v-model:visible="createDialogVisible"
      modal
      header="Créer un nouveau référentiel de compétences"
      class="w-full max-w-lg"
    >
      <div class="space-y-4 pt-2">
        <div class="flex flex-col gap-1.5">
          <label for="ref-libelle" class="font-medium text-sm text-surface-700 dark:text-surface-200">
            Libellé du référentiel <span class="text-red-500">*</span>
          </label>
          <InputText
            id="ref-libelle"
            v-model="newReferentiel.libelle"
            placeholder="Ex : Référentiel de compétences du B.U.T. MMI"
            class="w-full"
          />
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div class="flex flex-col gap-1.5">
            <label for="ref-annee" class="font-medium text-sm text-surface-700 dark:text-surface-200">
              Année de publication
            </label>
            <InputText
              id="ref-annee"
              v-model="newReferentiel.anneePublication"
              type="number"
              placeholder="Ex : 2024"
              class="w-full"
            />
          </div>

          <div class="flex flex-col gap-1.5">
            <label for="ref-oreof" class="font-medium text-sm text-surface-700 dark:text-surface-200">
              ID ORéOF (optionnel)
            </label>
            <InputText
              id="ref-oreof"
              v-model="newReferentiel.oreofId"
              placeholder="Ex : 687"
              class="w-full"
            />
          </div>
        </div>

        <div class="flex flex-col gap-1.5">
          <label for="ref-desc" class="font-medium text-sm text-surface-700 dark:text-surface-200">
            Description
          </label>
          <Textarea
            id="ref-desc"
            v-model="newReferentiel.description"
            rows="3"
            placeholder="Description succincte du référentiel..."
            class="w-full"
          />
        </div>

        <div class="p-3 rounded-xl bg-surface-50 dark:bg-surface-800 text-xs text-muted-color">
          <i class="pi pi-info-circle mr-1 text-primary"></i>
          Le référentiel sera rattaché au département <strong>{{ currentDepartement?.libelle }}</strong>.
        </div>
      </div>

      <template #footer>
        <div class="flex justify-end gap-2 pt-3">
          <Button
            label="Annuler"
            severity="secondary"
            text
            @click="createDialogVisible = false"
          />
          <Button
            label="Créer le référentiel"
            icon="pi pi-check"
            :loading="isCreating"
            @click="handleCreateReferentiel"
          />
        </div>
      </template>
    </Dialog>
  </div>
</template>

<style scoped>
</style>
