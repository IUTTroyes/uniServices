<script setup lang="ts">
import ContexteSelection from '@/components/Structure/ContexteSelection.vue';
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
  validerCommission, rouvrirCommission, getHistoriqueCommission,
  type CommissionRevision,
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

const validationDialog = ref(false);
const reouvertureDialog = ref(false);
const historiqueDialog = ref(false);
const historique = ref<CommissionRevision[]>([]);
const historiqueSelection = ref<CommissionRevision | null>(null);
const saving = ref(false);
const motifReouverture = ref('');
const decisionsJury = ref<Record<number, { decision: boolean | null; motif: string }>>({});
const estValidee = computed(() => commissionData.value?.validation?.etat === 'validee');
const decisionsValides = computed(() => (commissionData.value?.rows ?? []).every(row => {
  const d = decisionsJury.value[row.scolariteSemestreId];
  return d && typeof d.decision === 'boolean' && (d.decision === row.decision || d.motif.trim().length > 0);
}));
watch(commissionData, (data) => {
  decisionsJury.value = Object.fromEntries((data?.rows ?? []).map(row => [row.scolariteSemestreId, { decision: row.decisionJury ?? row.decision ?? null, motif: row.motifJury ?? '' }]));
});
const messageErreur = (e: any) => e?.response?.data?.detail ?? e?.response?.data?.message ?? 'Impossible de mettre à jour la sous-commission.';
const valider = async () => {
  if (!commissionData.value || !selectedSemestre.value) return;
  saving.value = true;
  try {
    const decisions = Object.entries(decisionsJury.value).map(([id, d]) => ({ inscriptionId: Number(id), decision: d.decision as boolean, motif: d.motif || null }));
    commissionData.value = await validerCommission(selectedSemestre.value.id, commissionData.value.empreinte, decisions);
    validationDialog.value = false;
    errorMessage.value = null;
  } catch (e) { errorMessage.value = messageErreur(e); }
  finally { saving.value = false; }
};
const rouvrir = async () => {
  const revision = commissionData.value?.validation.revision;
  if (!revision || !selectedSemestre.value) return;
  saving.value = true;
  try {
    commissionData.value = await rouvrirCommission(selectedSemestre.value.id, revision.numero, motifReouverture.value);
    reouvertureDialog.value = false;
    motifReouverture.value = '';
    errorMessage.value = null;
  } catch (e) { errorMessage.value = messageErreur(e); }
  finally { saving.value = false; }
};
const voirHistorique = async () => {
  if (!selectedSemestre.value) return;
  saving.value = true;
  try {
    historique.value = await getHistoriqueCommission(selectedSemestre.value.id);
    historiqueSelection.value = null;
    historiqueDialog.value = true;
  } catch (e) { errorMessage.value = messageErreur(e); }
  finally { saving.value = false; }
};

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
  <ContexteSelection titre="Semestre de travail" :valeur="[selectedSemestre?.libelle].filter(Boolean).join(' · ')">
    <div class="flex flex-col gap-2"><label for="contexte-selectedSemestre" class="text-sm font-semibold text-muted-color">Semestre</label>
      <Select :loading="isLoadingSemestres" :disabled="isLoadingSemestres || saving" input-id="contexte-selectedSemestre"
            v-model="selectedSemestre"
            :options="semestres"
            option-label="libelle"
            placeholder="Choisir un semestre" class="w-full sm:w-60"
          />
    </div>
  </ContexteSelection>

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

    <section v-if="commissionData && !isLoadingCommission" class="card space-y-4">
      <p v-if="estValidee" class="font-medium">
        Résultats validés — révision {{ commissionData.validation.revision?.numero }} par
        {{ commissionData.validation.revision?.validePar.prenom }} {{ commissionData.validation.revision?.validePar.nom }}.
      </p>
      <p v-else>Résultats provisoires calculés depuis les notes courantes. La validation arrête les résultats de tout le semestre.</p>
      <Message v-if="commissionData.erreurs?.length" severity="warn">
        <p>Validation impossible tant que ces points ne sont pas résolus :</p>
        <ul class="list-disc pl-5"><li v-for="erreur in commissionData.erreurs" :key="erreur">{{ erreur }}</li></ul>
      </Message>
      <div class="flex flex-wrap gap-3">
        <Button v-if="!estValidee" label="Valider le semestre" icon="pi pi-check" :disabled="saving || !commissionData.rows.length || !!commissionData.erreurs?.length" @click="validationDialog = true" />
        <Button v-else label="Rouvrir la sous-commission" severity="warn" icon="pi pi-lock-open" :disabled="saving" @click="reouvertureDialog = true" />
        <Button label="Historique des validations" severity="secondary" :loading="saving" @click="voirHistorique" />
      </div>
      <table v-if="estValidee" class="w-full text-sm">
        <thead><tr><th class="text-left">Étudiant</th><th>Proposition calculée</th><th>Décision du jury</th><th>Motif</th></tr></thead>
        <tbody><tr v-for="row in filteredRows" :key="row.scolariteSemestreId">
          <td>{{ row.nom }} {{ row.prenom }}</td><td class="text-center">{{ row.decisionLibelle }}</td>
          <td class="text-center">{{ row.decisionJury ? 'Validé' : 'Non validé' }}</td><td>{{ row.motifJury || '—' }}</td>
        </tr></tbody>
      </table>
    </section>
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


    <Dialog v-model:visible="validationDialog" modal header="Valider les résultats du semestre" :closable="!saving" :style="{ width: '70rem', maxWidth: '95vw' }">
      <p class="mb-4">Vérifiez les décisions du jury. Toute différence avec la proposition calculée exige un motif. Les notes et la maquette seront verrouillées jusqu’à une réouverture explicite.</p>
      <table class="w-full text-sm">
        <thead><tr><th class="text-left">Étudiant</th><th>Proposition calculée</th><th>Décision du jury</th><th>Motif de dérogation</th></tr></thead>
        <tbody><tr v-for="row in commissionData?.rows ?? []" :key="row.scolariteSemestreId">
          <td>{{ row.nom }} {{ row.prenom }}</td><td>{{ row.decisionLibelle }}</td>
          <td class="p-2"><select v-if="decisionsJury[row.scolariteSemestreId]" v-model="decisionsJury[row.scolariteSemestreId].decision" class="border rounded p-2" :disabled="saving">
            <option :value="null">Choisir</option><option :value="true">Validé</option><option :value="false">Non validé</option>
          </select></td>
          <td><input v-if="decisionsJury[row.scolariteSemestreId]" v-model="decisionsJury[row.scolariteSemestreId].motif" class="border rounded p-2 w-full" aria-label="Motif de dérogation" :disabled="saving" /></td>
        </tr></tbody>
      </table>
      <Message v-if="errorMessage" severity="error" class="mt-4">{{ errorMessage }}</Message>
      <template #footer><Button label="Annuler" severity="secondary" :disabled="saving" @click="validationDialog = false" /><Button label="Valider tout le semestre" :loading="saving" :disabled="!decisionsValides" @click="valider" /></template>
    </Dialog>
    <Dialog v-model:visible="reouvertureDialog" modal header="Rouvrir la sous-commission" :closable="!saving">
      <p class="mb-4">La révision actuelle sera invalidée et conservée dans l’historique. Les résultats redeviendront provisoires.</p>
      <label for="motif-reouverture" class="block mb-2">Motif obligatoire</label>
      <textarea id="motif-reouverture" v-model="motifReouverture" class="border rounded p-2 w-full" :disabled="saving" />
      <Message v-if="errorMessage" severity="error">{{ errorMessage }}</Message>
      <template #footer><Button label="Annuler" severity="secondary" :disabled="saving" @click="reouvertureDialog = false" /><Button label="Rouvrir et invalider" severity="warn" :loading="saving" :disabled="!motifReouverture.trim()" @click="rouvrir" /></template>
    </Dialog>
    <Dialog v-model:visible="historiqueDialog" modal header="Historique des sous-commissions" :style="{ width: '75rem', maxWidth: '95vw' }">
      <p v-if="!historique.length">Aucune validation enregistrée.</p>
      <div v-for="revision in historique" :key="revision.id" class="border rounded p-3 mb-3">
        <p>Révision {{ revision.numero }} — {{ revision.valideLe }} — {{ revision.validePar.prenom }} {{ revision.validePar.nom }}</p>
        <p v-if="revision.invalideeLe">Invalidée le {{ revision.invalideeLe }} : {{ revision.motifReouverture }}</p>
        <Button label="Consulter les résultats figés" text @click="historiqueSelection = revision" />
      </div>
      <template v-if="historiqueSelection">
        <h3 class="font-bold my-4">Résultats de la révision {{ historiqueSelection.numero }}</h3>
        <SousCommissionClassiqueTable :rows="historiqueSelection.vue.rows" :columns="historiqueSelection.vue.columns" :show-details="true" />
        <table class="w-full mt-4"><thead><tr><th>Étudiant</th><th>Décision du jury</th><th>Motif</th></tr></thead><tbody>
        <tr v-for="row in historiqueSelection.vue.rows" :key="row.scolariteSemestreId"><td>{{ row.nom }} {{ row.prenom }}</td><td>{{ row.decisionJury ? 'Validé' : 'Non validé' }}</td><td>{{ row.motifJury || '—' }}</td></tr>
        </tbody></table>
      </template>
    </Dialog>
  </div>
</template>
