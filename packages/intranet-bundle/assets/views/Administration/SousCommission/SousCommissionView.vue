<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue';
import { useRoute } from 'vue-router';
import { HeaderComponent, SimpleSkeleton, Kpi } from '@components';
import {
  UsersIcon,
  ChartBarIcon,
  ArrowsRightLeftIcon,
  CheckCircleIcon,
  AcademicCapIcon,
} from '@heroicons/vue/24/outline';
import { useAnneeStore, useAnneeUnivStore } from '@stores';
import { getSemestresService } from '@requests';
import {
  getCommissionSemestre,
  type CommissionViewData,
  type CommissionRow,
} from '@/service/commissionService';
import SousCommissionApcTable from './components/SousCommissionApcTable.vue';
import SousCommissionClassiqueTable from './components/SousCommissionClassiqueTable.vue';

const route = useRoute();
const anneeStore = useAnneeStore();
const anneeUnivStore = useAnneeUnivStore();

const anneeId = computed(() => route.params.anneeId || anneeStore.annee?.id);
const selectedAnneeUnivId = computed(() => anneeUnivStore.selectedAnneeUniv?.id ?? null);

// Données du semestre
const semestres = ref<any[]>([]);
const selectedSemestre = ref<any>(null);
const isLoadingSemestres = ref(true);

// Données de la commission
const commissionData = ref<CommissionViewData | null>(null);
const isLoadingCommission = ref(false);
const errorMessage = ref<string | null>(null);

// Options d'affichage
const searchQuery = ref('');
const showDetails = ref(false);
const stickyHeader = ref(true);
const stickyColumn = ref(true);
const apcDisplayMode = ref<'ue' | 'enseignement'>('ue');

// Filtrage instantané par nom / prénom / numéro étudiant / bac
const filteredRows = computed<CommissionRow[]>(() => {
  if (!commissionData.value?.rows) return [];
  const q = searchQuery.value.trim().toLowerCase();
  if (!q) return commissionData.value.rows;

  return commissionData.value.rows.filter((row) =>
    row.nom.toLowerCase().includes(q) ||
    row.prenom.toLowerCase().includes(q) ||
    (row.numeroEtudiant && row.numeroEtudiant.toLowerCase().includes(q)) ||
    (row.bac && row.bac.toLowerCase().includes(q))
  );
});

// Chargement des semestres de l'année
const loadSemestres = async () => {
  isLoadingSemestres.value = true;
  try {
    const params: Record<string, any> = {};
    if (anneeId.value) params.annee = anneeId.value;
    if (selectedAnneeUnivId.value) params.anneeUniversitaire = selectedAnneeUnivId.value;

    const list = await getSemestresService(params);
    semestres.value = Array.isArray(list) ? list : [];

    if (semestres.value.length > 0) {
      selectedSemestre.value = semestres.value[0];
    } else {
      selectedSemestre.value = null;
    }
  } catch (e: any) {
    console.error('Erreur chargement semestres:', e);
  } finally {
    isLoadingSemestres.value = false;
  }
};

// Chargement de la vue commission
const loadCommission = async () => {
  if (!selectedSemestre.value?.id) {
    commissionData.value = null;
    return;
  }

  isLoadingCommission.value = true;
  errorMessage.value = null;

  try {
    commissionData.value = await getCommissionSemestre(selectedSemestre.value.id);
  } catch (e: any) {
    console.error('Erreur chargement commission:', e);
    errorMessage.value = e?.response?.data?.error || 'Impossible de charger la commission pour ce semestre.';
    commissionData.value = null;
  } finally {
    isLoadingCommission.value = false;
  }
};

// Export CSV de la grille
const exportCsv = () => {
  if (!commissionData.value) return;

  const data = commissionData.value;
  const isApcByEns = data.mode === 'apc' && apcDisplayMode.value === 'enseignement' && (data.columnsByEnseignement?.length ?? 0) > 0;
  const cols = isApcByEns ? (data.columnsByEnseignement ?? data.columns) : data.columns;

  const headers = [
    'Nom',
    'Prénom',
    'N° Étudiant',
    'Date de naissance',
    'Bac',
    'Rang',
    ...cols.map((c) => c.label + (c.sublabel ? ` (${c.sublabel})` : '')),
    'Moyenne',
    'Décision',
  ];

  const rowsCsv = data.rows.map((r) => [
    `"${r.nom}"`,
    `"${r.prenom}"`,
    `"${r.numeroEtudiant || ''}"`,
    `"${r.dateNaissance || ''}"`,
    `"${r.bac || ''}"`,
    r.rang ?? '',
    ...cols.map((c) => (r.cells[c.key] !== null && r.cells[c.key] !== undefined ? r.cells[c.key] : '')),
    r.moyenne !== null && r.moyenne !== undefined ? r.moyenne : '',
    `"${r.decisionLibelle || ''}"`,
  ]);

  const csvContent = '\uFEFF' + [headers.join(';'), ...rowsCsv.map((e) => e.join(';'))].join('\n');
  const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
  const url = URL.createObjectURL(blob);
  const link = document.createElement('a');
  link.setAttribute('href', url);
  link.setAttribute('download', `commission_${data.mode}_${apcDisplayMode.value}_${selectedSemestre.value?.libelle || 'export'}.csv`);
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
};

onMounted(async () => {
  await loadSemestres();
  if (selectedSemestre.value?.id) {
    await loadCommission();
  }
});

watch(selectedSemestre, async (newSem, oldSem) => {
  if (newSem?.id && newSem.id !== oldSem?.id) {
    await loadCommission();
  }
});

watch(anneeId, async () => {
  await loadSemestres();
  await loadCommission();
});
</script>

<template>
  <div class="flex flex-col gap-6">
    <!-- Header principal -->
    <HeaderComponent
      icon="pi pi-calculator"
      titre="Sous commission"
      description="Calcul, analyse des moyennes et délibération de fin de semestre"
    />

    <!-- Bandeau de configuration & sélection du semestre -->
    <div class="card p-4 rounded-xl border border-surface-200 dark:border-surface-800 bg-surface-0 dark:bg-surface-900 shadow-xs flex flex-wrap items-center justify-between gap-4">
      <div class="flex items-center gap-4 flex-wrap">
        <div class="flex items-center gap-2">
          <label class="text-sm font-semibold text-surface-700 dark:text-surface-300">Semestre :</label>
          <SimpleSkeleton v-if="isLoadingSemestres" class="w-48 h-9" />
          <Select
            v-else
            v-model="selectedSemestre"
            :options="semestres"
            option-label="libelle"
            placeholder="Choisir un semestre"
            class="w-56"
          />
        </div>

        <!-- Badge du mode détecté (APC vs Classique) -->
        <div v-if="commissionData" class="flex items-center gap-2">
          <Tag
            :value="commissionData.mode === 'apc' ? 'Mode APC (BUT)' : 'Mode Classique'"
            :severity="commissionData.mode === 'apc' ? 'info' : 'secondary'"
            class="font-semibold text-xs px-2.5 py-1"
          />
        </div>

        <!-- Bascule des 2 modes APC (Par UE vs Par Ressource/SAÉ) -->
        <div
          v-if="commissionData?.mode === 'apc'"
          class="inline-flex rounded-lg border border-surface-200 dark:border-surface-700 bg-surface-100 dark:bg-surface-800 p-0.5"
        >
          <button
            type="button"
            class="flex items-center gap-1.5 px-3 py-1 text-xs font-semibold rounded-md transition-all cursor-pointer"
            :class="apcDisplayMode === 'ue' ? 'bg-surface-0 dark:bg-surface-900 text-primary-600 dark:text-primary-400 shadow-xs' : 'text-surface-600 dark:text-surface-400 hover:text-surface-900 dark:hover:text-surface-100'"
            @click="apcDisplayMode = 'ue'"
            title="Affichage regroupé par Unité d'Enseignement (UE)"
          >
            <i class="pi pi-th-large text-xs"></i>
            <span>Par UE</span>
          </button>
          <button
            type="button"
            class="flex items-center gap-1.5 px-3 py-1 text-xs font-semibold rounded-md transition-all cursor-pointer"
            :class="apcDisplayMode === 'enseignement' ? 'bg-surface-0 dark:bg-surface-900 text-primary-600 dark:text-primary-400 shadow-xs' : 'text-surface-600 dark:text-surface-400 hover:text-surface-900 dark:hover:text-surface-100'"
            @click="apcDisplayMode = 'enseignement'"
            title="Affichage à plat par Ressource et SAÉ avec carrés d'UE / compétences"
          >
            <i class="pi pi-list text-xs"></i>
            <span>Par Ressource / SAÉ</span>
          </button>
        </div>
      </div>

      <!-- Actions de droite : recherche, bascule détail, export -->
      <div class="flex items-center gap-3 flex-wrap">
        <!-- Champ de recherche -->
        <div class="relative">
          <i class="pi pi-search absolute left-3 top-1/2 -translate-y-1/2 text-surface-400 text-xs"></i>
          <InputText
            v-model="searchQuery"
            placeholder="Rechercher un étudiant..."
            class="pl-8 text-xs w-48 sm:w-56"
            size="small"
          />
        </div>

        <!-- Bascule Afficher/Masquer le détail (actif en mode Par UE) -->
        <Button
          v-if="commissionData?.mode !== 'apc' || apcDisplayMode === 'ue'"
          :label="showDetails ? 'Masquer matières/SAÉ' : 'Détail matières/SAÉ'"
          :icon="showDetails ? 'pi pi-eye-slash' : 'pi pi-eye'"
          size="small"
          :severity="showDetails ? 'primary' : 'secondary'"
          variant="outlined"
          @click="showDetails = !showDetails"
        />

        <!-- Bascule En-tête figé -->
        <Button
          :label="stickyHeader ? 'En-tête figé' : 'En-tête libre'"
          :icon="stickyHeader ? 'pi pi-arrow-down' : 'pi pi-arrows-v'"
          size="small"
          :severity="stickyHeader ? 'primary' : 'secondary'"
          variant="outlined"
          :title="stickyHeader ? 'Désactiver l\'en-tête figé' : 'Figer l\'en-tête lors du défilement vertical'"
          @click="stickyHeader = !stickyHeader"
        />

        <!-- Bascule Colonne Étudiant figée -->
        <Button
          :label="stickyColumn ? 'Étudiant figé' : 'Étudiant libre'"
          :icon="stickyColumn ? 'pi pi-arrow-right' : 'pi pi-arrows-h'"
          size="small"
          :severity="stickyColumn ? 'primary' : 'secondary'"
          variant="outlined"
          :title="stickyColumn ? 'Désactiver la colonne étudiant figée' : 'Figer la colonne étudiant lors du défilement horizontal'"
          @click="stickyColumn = !stickyColumn"
        />

        <!-- Bouton export CSV -->
        <Button
          label="Exporter CSV"
          icon="pi pi-download"
          size="small"
          severity="secondary"
          :disabled="!commissionData || !commissionData.rows.length"
          @click="exportCsv"
        />
      </div>
    </div>

    <!-- KPIs / Statistiques promo -->
    <div v-if="selectedSemestre || commissionData" class="grid grid-cols-2 md:grid-cols-5 gap-4">
      <Kpi
        label="Effectif"
        :value="commissionData?.statistiques?.effectif ?? 0"
        :icon="UsersIcon"
        color="blue"
        description="étudiants inscrits"
        :loading="isLoadingCommission"
      />

      <Kpi
        label="Moyenne promo"
        :value="commissionData?.statistiques?.moyennePromo !== null && commissionData?.statistiques?.moyennePromo !== undefined ? commissionData.statistiques.moyennePromo : '-'"
        :icon="ChartBarIcon"
        color="indigo"
        description="sur 20"
        :loading="isLoadingCommission"
      />

      <Kpi
        label="Min / Max"
        :value="commissionData?.statistiques?.min !== null && commissionData?.statistiques?.min !== undefined ? `${commissionData.statistiques.min} / ${commissionData.statistiques.max}` : '-'"
        :icon="ArrowsRightLeftIcon"
        color="amber"
        description="fourchette promo"
        :loading="isLoadingCommission"
      />

      <Kpi
        label="Semestres validés"
        :value="commissionData?.statistiques?.valides ?? 0"
        :icon="CheckCircleIcon"
        color="emerald"
        description="étudiants admis"
        :loading="isLoadingCommission"
      />

      <Kpi
        class="col-span-2 md:col-span-1"
        label="Taux de validation"
        :value="commissionData?.statistiques?.tauxReussite !== null && commissionData?.statistiques?.tauxReussite !== undefined ? `${commissionData.statistiques.tauxReussite}%` : '-'"
        :icon="AcademicCapIcon"
        color="green"
        description="réussite semestre"
        :loading="isLoadingCommission"
      />
    </div>

    <!-- Message d'erreur -->
    <div v-if="errorMessage" class="p-4 rounded-xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 text-rose-700 dark:text-rose-300 text-sm flex items-center gap-3">
      <i class="pi pi-exclamation-circle text-lg"></i>
      <span>{{ errorMessage }}</span>
    </div>

    <!-- Loader de commission -->
    <div v-if="isLoadingCommission" class="card p-8 rounded-xl border border-surface-200 dark:border-surface-800 bg-surface-0 dark:bg-surface-900 flex flex-col items-center justify-center gap-3">
      <i class="pi pi-spin pi-spinner text-3xl text-primary-500"></i>
      <span class="text-sm text-surface-500">Calcul et chargement des moyennes de la sous-commission en cours...</span>
    </div>

    <!-- Affichage du tableau selon le mode (APC vs Classique) -->
    <template v-else-if="commissionData">
      <div v-if="!commissionData.rows.length" class="card p-8 rounded-xl border border-surface-200 dark:border-surface-800 bg-surface-0 dark:bg-surface-900 text-center text-surface-500">
        <i class="pi pi-info-circle text-2xl mb-2"></i>
        <p>Aucun étudiant ou aucune moyenne trouvée pour ce semestre.</p>
      </div>

      <!-- Composant APC (BUT) -->
      <SousCommissionApcTable
        v-else-if="commissionData.mode === 'apc'"
        :rows="filteredRows"
        :columns="commissionData.columns"
        :columns-by-enseignement="commissionData.columnsByEnseignement"
        :mode="apcDisplayMode"
        :show-details="showDetails"
        :sticky-header="stickyHeader"
        :sticky-column="stickyColumn"
      />

      <!-- Composant Classique -->
      <SousCommissionClassiqueTable
        v-else
        :rows="filteredRows"
        :columns="commissionData.columns"
        :show-details="showDetails"
        :sticky-header="stickyHeader"
        :sticky-column="stickyColumn"
      />
    </template>
  </div>
</template>
