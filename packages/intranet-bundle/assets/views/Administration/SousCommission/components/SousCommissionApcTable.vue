<script setup lang="ts">
import { computed } from 'vue';
import type { CommissionColumn, CommissionRow } from '@/service/commissionService';

const props = withDefaults(
  defineProps<{
    rows: CommissionRow[];
    columns: CommissionColumn[];
    columnsByEnseignement?: CommissionColumn[];
    showDetails: boolean;
    mode?: 'ue' | 'enseignement';
    stickyHeader?: boolean;
    stickyColumn?: boolean;
  }>(),
  {
    mode: 'ue',
    columnsByEnseignement: () => [],
    stickyHeader: true,
    stickyColumn: true,
  }
);

// Mode par UE (hiérarchique)
const ueColumns = computed(() => props.columns.filter((col) => col.type === 'ue'));

const getEnseignementsForUe = (ueKey: string) => {
  return props.columns.filter((col) => col.type === 'enseignement' && col.parentKey === ueKey);
};

// Mode par Ressource / SAÉ (à plat)
const singleEnseignementColumns = computed(() =>
  (props.columnsByEnseignement || []).filter((col) => col.type === 'enseignement')
);

const singleUeColumns = computed(() => {
  const ues = (props.columnsByEnseignement || []).filter((col) => col.type === 'ue');
  return ues.length > 0 ? ues : ueColumns.value;
});

const formatNote = (val: number | null | undefined): string => {
  if (val === null || val === undefined || isNaN(val)) return '-';
  return Number(val).toFixed(2);
};

const getNoteClass = (val: number | null | undefined): string => {
  if (val === null || val === undefined) return 'text-slate-400';
  if (val >= 10) return 'text-emerald-600 dark:text-emerald-400 font-semibold';
  if (val >= 8) return 'text-amber-600 dark:text-amber-400 font-medium';
  return 'text-rose-600 dark:text-rose-400 font-bold bg-rose-50 dark:bg-rose-950/50 rounded px-1';
};

const getUeStatut = (val: number | null | undefined): { label: string; severity: string } => {
  if (val === null || val === undefined) return { label: '-', severity: 'secondary' };
  if (val >= 10) return { label: 'ADM', severity: 'success' };
  if (val >= 8) return { label: 'CMP', severity: 'warn' };
  return { label: 'AJ', severity: 'danger' };
};

const getDecisionBadge = (decision: boolean | null | undefined, libelle: string) => {
  if (decision === true) {
    if (libelle?.toLowerCase().includes('compensation')) {
      return { label: libelle || 'Validé par compensation', severity: 'warn' };
    }
    return { label: libelle || 'Validé', severity: 'success' };
  }
  if (decision === false) {
    return { label: libelle || 'Non validé', severity: 'danger' };
  }
  return { label: libelle || 'En attente', severity: 'secondary' };
};

// Styles thématiques par couleur de compétence (c1..c6 ou blue, emerald, amber, rose, purple, cyan)
const getCompetenceStyle = (colorName: string | undefined | null) => {
  const c = (colorName || 'blue').toLowerCase();
  const map: Record<string, {
    headerBg: string;
    headerText: string;
    subHeaderBg: string;
    badgeBg: string;
    squareBg: string;
    cellBg: string;
    footBg: string;
  }> = {
    c1: {
      headerBg: 'bg-blue-100/90 dark:bg-blue-950/60 border-blue-300 dark:border-blue-700',
      headerText: 'text-blue-900 dark:text-blue-200',
      subHeaderBg: 'bg-blue-100/60 dark:bg-blue-950/50 text-blue-900 dark:text-blue-200',
      badgeBg: 'bg-blue-600 text-white',
      squareBg: 'bg-blue-600 text-white',
      cellBg: 'bg-blue-50/30 dark:bg-blue-950/20',
      footBg: 'bg-blue-100/50 dark:bg-blue-950/30',
    },
    blue: {
      headerBg: 'bg-blue-100/90 dark:bg-blue-950/60 border-blue-300 dark:border-blue-700',
      headerText: 'text-blue-900 dark:text-blue-200',
      subHeaderBg: 'bg-blue-100/60 dark:bg-blue-950/50 text-blue-900 dark:text-blue-200',
      badgeBg: 'bg-blue-600 text-white',
      squareBg: 'bg-blue-600 text-white',
      cellBg: 'bg-blue-50/30 dark:bg-blue-950/20',
      footBg: 'bg-blue-100/50 dark:bg-blue-950/30',
    },
    c2: {
      headerBg: 'bg-emerald-100/90 dark:bg-emerald-950/60 border-emerald-300 dark:border-emerald-700',
      headerText: 'text-emerald-900 dark:text-emerald-200',
      subHeaderBg: 'bg-emerald-100/60 dark:bg-emerald-950/50 text-emerald-900 dark:text-emerald-200',
      badgeBg: 'bg-emerald-600 text-white',
      squareBg: 'bg-emerald-600 text-white',
      cellBg: 'bg-emerald-50/30 dark:bg-emerald-950/20',
      footBg: 'bg-emerald-100/50 dark:bg-emerald-950/30',
    },
    emerald: {
      headerBg: 'bg-emerald-100/90 dark:bg-emerald-950/60 border-emerald-300 dark:border-emerald-700',
      headerText: 'text-emerald-900 dark:text-emerald-200',
      subHeaderBg: 'bg-emerald-100/60 dark:bg-emerald-950/50 text-emerald-900 dark:text-emerald-200',
      badgeBg: 'bg-emerald-600 text-white',
      squareBg: 'bg-emerald-600 text-white',
      cellBg: 'bg-emerald-50/30 dark:bg-emerald-950/20',
      footBg: 'bg-emerald-100/50 dark:bg-emerald-950/30',
    },
    c3: {
      headerBg: 'bg-amber-100/90 dark:bg-amber-950/60 border-amber-300 dark:border-amber-700',
      headerText: 'text-amber-900 dark:text-amber-200',
      subHeaderBg: 'bg-amber-100/60 dark:bg-amber-950/50 text-amber-900 dark:text-amber-200',
      badgeBg: 'bg-amber-600 text-white',
      squareBg: 'bg-amber-600 text-white',
      cellBg: 'bg-amber-50/30 dark:bg-amber-950/20',
      footBg: 'bg-amber-100/50 dark:bg-amber-950/30',
    },
    amber: {
      headerBg: 'bg-amber-100/90 dark:bg-amber-950/60 border-amber-300 dark:border-amber-700',
      headerText: 'text-amber-900 dark:text-amber-200',
      subHeaderBg: 'bg-amber-100/60 dark:bg-amber-950/50 text-amber-900 dark:text-amber-200',
      badgeBg: 'bg-amber-600 text-white',
      squareBg: 'bg-amber-600 text-white',
      cellBg: 'bg-amber-50/30 dark:bg-amber-950/20',
      footBg: 'bg-amber-100/50 dark:bg-amber-950/30',
    },
    c4: {
      headerBg: 'bg-rose-100/90 dark:bg-rose-950/60 border-rose-300 dark:border-rose-700',
      headerText: 'text-rose-900 dark:text-rose-200',
      subHeaderBg: 'bg-rose-100/60 dark:bg-rose-950/50 text-rose-900 dark:text-rose-200',
      badgeBg: 'bg-rose-600 text-white',
      squareBg: 'bg-rose-600 text-white',
      cellBg: 'bg-rose-50/30 dark:bg-rose-950/20',
      footBg: 'bg-rose-100/50 dark:bg-rose-950/30',
    },
    rose: {
      headerBg: 'bg-rose-100/90 dark:bg-rose-950/60 border-rose-300 dark:border-rose-700',
      headerText: 'text-rose-900 dark:text-rose-200',
      subHeaderBg: 'bg-rose-100/60 dark:bg-rose-950/50 text-rose-900 dark:text-rose-200',
      badgeBg: 'bg-rose-600 text-white',
      squareBg: 'bg-rose-600 text-white',
      cellBg: 'bg-rose-50/30 dark:bg-rose-950/20',
      footBg: 'bg-rose-100/50 dark:bg-rose-950/30',
    },
    c5: {
      headerBg: 'bg-purple-100/90 dark:bg-purple-950/60 border-purple-300 dark:border-purple-700',
      headerText: 'text-purple-900 dark:text-purple-200',
      subHeaderBg: 'bg-purple-100/60 dark:bg-purple-950/50 text-purple-900 dark:text-purple-200',
      badgeBg: 'bg-purple-600 text-white',
      squareBg: 'bg-purple-600 text-white',
      cellBg: 'bg-purple-50/30 dark:bg-purple-950/20',
      footBg: 'bg-purple-100/50 dark:bg-purple-950/30',
    },
    purple: {
      headerBg: 'bg-purple-100/90 dark:bg-purple-950/60 border-purple-300 dark:border-purple-700',
      headerText: 'text-purple-900 dark:text-purple-200',
      subHeaderBg: 'bg-purple-100/60 dark:bg-purple-950/50 text-purple-900 dark:text-purple-200',
      badgeBg: 'bg-purple-600 text-white',
      squareBg: 'bg-purple-600 text-white',
      cellBg: 'bg-purple-50/30 dark:bg-purple-950/20',
      footBg: 'bg-purple-100/50 dark:bg-purple-950/30',
    },
    c6: {
      headerBg: 'bg-cyan-100/90 dark:bg-cyan-950/60 border-cyan-300 dark:border-cyan-700',
      headerText: 'text-cyan-900 dark:text-cyan-200',
      subHeaderBg: 'bg-cyan-100/60 dark:bg-cyan-950/50 text-cyan-900 dark:text-cyan-200',
      badgeBg: 'bg-cyan-600 text-white',
      squareBg: 'bg-cyan-600 text-white',
      cellBg: 'bg-cyan-50/30 dark:bg-cyan-950/20',
      footBg: 'bg-cyan-100/50 dark:bg-cyan-950/30',
    },
    cyan: {
      headerBg: 'bg-cyan-100/90 dark:bg-cyan-950/60 border-cyan-300 dark:border-cyan-700',
      headerText: 'text-cyan-900 dark:text-cyan-200',
      subHeaderBg: 'bg-cyan-100/60 dark:bg-cyan-950/50 text-cyan-900 dark:text-cyan-200',
      badgeBg: 'bg-cyan-600 text-white',
      squareBg: 'bg-cyan-600 text-white',
      cellBg: 'bg-cyan-50/30 dark:bg-cyan-950/20',
      footBg: 'bg-cyan-100/50 dark:bg-cyan-950/30',
    },
  };

  return map[c] || map.blue;
};

// Calcul des moyennes de promotion pour les pieds de tableau
const getColumnAverage = (key: string): number | null => {
  const values = props.rows
    .map((r) => r.cells[key])
    .filter((v): v is number => v !== null && v !== undefined && !isNaN(v));
  if (values.length === 0) return null;
  const sum = values.reduce((acc, curr) => acc + curr, 0);
  return Math.round((sum / values.length) * 100) / 100;
};

const getMoyenneGeneraleAverage = (): number | null => {
  const values = props.rows
    .map((r) => r.moyenne)
    .filter((v): v is number => v !== null && v !== undefined && !isNaN(v));
  if (values.length === 0) return null;
  const sum = values.reduce((acc, curr) => acc + curr, 0);
  return Math.round((sum / values.length) * 100) / 100;
};

const getUesValideesAverage = (): number | null => {
  if (props.rows.length === 0) return null;
  const sum = props.rows.reduce((acc, r) => acc + (r.uesValidees || 0), 0);
  return Math.round((sum / props.rows.length) * 10) / 10;
};

const getUeValidationRate = (ueKey: string): number | null => {
  const values = props.rows
    .map((r) => r.cells[ueKey])
    .filter((v): v is number => v !== null && v !== undefined && !isNaN(v));
  if (values.length === 0) return null;
  const validCount = values.filter((v) => v >= 10).length;
  return Math.round((validCount / values.length) * 100);
};

const getTauxReussiteSemestre = (): number | null => {
  if (props.rows.length === 0) return null;
  const valides = props.rows.filter((r) => r.decision === true).length;
  return Math.round((valides / props.rows.length) * 100);
};
</script>

<template>
  <div
    class="relative overflow-x-auto shadow-sm rounded-xl border border-surface-200 dark:border-surface-800 bg-surface-0 dark:bg-surface-900"
    :class="{
      'has-sticky-header': stickyHeader,
      'has-sticky-col': stickyColumn,
    }"
  >
    <!-- ======================================================== -->
    <!-- MODE 1 : AFFICHAGE PAR UE (HIÉRARCHIQUE)                 -->
    <!-- ======================================================== -->
    <table v-if="mode === 'ue'" class="w-full text-xs text-left text-surface-600 dark:text-surface-300 border-collapse">
      <thead class="bg-surface-100 dark:bg-surface-800 text-surface-800 dark:text-surface-100">
        <!-- Ligne 1 : Groupement Étudiant + UE + Bilan Semestre -->
        <tr class="border-b border-surface-200 dark:border-surface-700 header-row-1">
          <th
            scope="col"
            rowspan="2"
            class="px-4 py-3 font-bold uppercase tracking-wider min-w-[220px] border-r border-surface-200 dark:border-surface-700 bg-surface-100 dark:bg-surface-800 col-sticky-etudiant"
          >
            Étudiant ({{ rows.length }})
          </th>
          <th
            scope="col"
            rowspan="2"
            class="px-3 py-3 text-center font-bold uppercase tracking-wider min-w-[60px] border-r border-surface-200 dark:border-surface-700 bg-surface-100 dark:bg-surface-800"
          >
            Rang
          </th>

          <!-- Entête pour chaque UE avec couleur et nom court de la compétence -->
          <template v-for="ue in ueColumns" :key="ue.key">
            <th
              scope="col"
              :colspan="showDetails ? getEnseignementsForUe(ue.key).length + 2 : 2"
              class="px-3 py-2 text-center font-bold border-r-2"
              :class="[getCompetenceStyle(ue.color).headerBg, getCompetenceStyle(ue.color).headerText]"
            >
              <div class="flex items-center justify-center gap-1.5 flex-wrap">
                <span class="text-sm font-extrabold tracking-tight">{{ ue.label }}</span>
                <!-- Nom court de la compétence associée -->
                <span
                  v-if="ue.competenceNomCourt"
                  class="text-[11px] font-black px-2 py-0.5 rounded shadow-2xs"
                  :class="getCompetenceStyle(ue.color).badgeBg"
                  :title="'Compétence : ' + (ue.sublabel || ue.competenceNomCourt)"
                >
                  {{ ue.competenceNomCourt }}
                </span>
                <span
                  v-if="ue.ects"
                  class="text-[10px] px-1.5 py-0.5 rounded-full bg-surface-0/80 dark:bg-surface-800/80 font-bold border border-surface-300/40"
                >
                  {{ ue.ects }} ECTS
                </span>
              </div>
              <div
                v-if="ue.sublabel && ue.sublabel !== ue.competenceNomCourt"
                class="text-[10px] font-normal truncate max-w-[200px] mx-auto opacity-80 mt-0.5"
                :title="ue.sublabel"
              >
                {{ ue.sublabel }}
              </div>
            </th>
          </template>

          <!-- Synthèse Semestre -->
          <th scope="col" colspan="3" class="px-4 py-2 text-center font-bold bg-surface-200/70 dark:bg-surface-700/60">
            Bilan Semestre
          </th>
        </tr>

        <!-- Ligne 2 : Détails Enseignements + Moyenne UE + Statut -->
        <tr class="border-b border-surface-200 dark:border-surface-700 text-[11px] header-row-2">
          <template v-for="ue in ueColumns" :key="ue.key + '_sub'">
            <!-- Matières détaillées si activé -->
            <template v-if="showDetails">
              <th
                v-for="ens in getEnseignementsForUe(ue.key)"
                :key="ens.key"
                scope="col"
                class="px-2 py-1.5 text-center font-medium bg-surface-50 dark:bg-surface-900/60 border-r border-surface-200 dark:border-surface-700 min-w-[75px]"
                :title="`${ens.label} - ${ens.sublabel || ''} (Coeff: ${ens.coefficient ?? 1}, ECTS: ${ens.ects ?? '-'})`"
              >
                <div class="flex items-center justify-center gap-1">
                  <span
                    v-if="ens.nature"
                    class="px-1 py-0.1 rounded text-[8px] font-black uppercase"
                    :class="ens.nature === 'sae' ? 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300 border border-amber-300/50' : 'bg-sky-100 text-sky-800 dark:bg-sky-950 dark:text-sky-300 border border-sky-300/50'"
                  >
                    {{ ens.nature === 'sae' ? 'SAÉ' : 'Ress' }}
                  </span>
                  <span class="truncate max-w-[85px] font-mono text-[10px] font-bold">{{ ens.label }}</span>
                </div>
                <div class="text-[9px] text-surface-400 font-normal mt-0.5">coef {{ ens.coefficient ?? 1 }}</div>
              </th>
            </template>

            <!-- Moyenne et Statut de l'UE avec la couleur de la compétence -->
            <th
              scope="col"
              class="px-2.5 py-1.5 text-center font-extrabold border-r border-surface-200 dark:border-surface-700 min-w-[70px]"
              :class="getCompetenceStyle(ue.color).subHeaderBg"
            >
              Moyenne
            </th>
            <th
              scope="col"
              class="px-2 py-1.5 text-center font-extrabold border-r-2 border-surface-300 dark:border-surface-600 min-w-[55px]"
              :class="getCompetenceStyle(ue.color).subHeaderBg"
            >
              Statut
            </th>
          </template>

          <!-- Sous-colonnes Bilan -->
          <th scope="col" class="px-3 py-1.5 text-center font-bold bg-surface-100 dark:bg-surface-800 border-r border-surface-200 dark:border-surface-700 min-w-[80px]">
            Moy. Gén.
          </th>
          <th scope="col" class="px-3 py-1.5 text-center font-bold bg-surface-100 dark:bg-surface-800 border-r border-surface-200 dark:border-surface-700 min-w-[85px]">
            UE Validées
          </th>
          <th scope="col" class="px-4 py-1.5 text-center font-bold bg-surface-100 dark:bg-surface-800 min-w-[130px]">
            Décision
          </th>
        </tr>
      </thead>

      <!-- Corps du tableau mode UE -->
      <tbody class="divide-y divide-surface-200 dark:divide-surface-800">
        <tr
          v-for="(row, idx) in rows"
          :key="row.scolariteSemestreId"
          class="hover:bg-surface-50 dark:hover:bg-surface-800/60 transition-colors"
          :class="idx % 2 === 0 ? 'bg-surface-0 dark:bg-surface-900' : 'bg-surface-50/40 dark:bg-surface-900/40'"
        >
          <!-- Nom & Prénom étudiant -->
          <td
            class="px-3.5 py-2 text-surface-900 dark:text-surface-100 border-r border-surface-200 dark:border-surface-700 bg-inherit col-sticky-etudiant"
          >
            <div class="flex items-center gap-2.5">
              <span class="w-7 h-7 rounded-full bg-primary-100 dark:bg-primary-900/60 text-primary-700 dark:text-primary-300 font-bold flex items-center justify-center text-[10px] shrink-0">
                {{ row.nom.charAt(0) }}{{ row.prenom.charAt(0) }}
              </span>
              <div class="min-w-0 flex-1">
                <div class="truncate text-xs">
                  <span class="font-bold uppercase">{{ row.nom }}</span> {{ row.prenom }}
                </div>
                <div class="flex items-center gap-1.5 flex-wrap text-[10px] text-surface-500 dark:text-surface-400 mt-0.5">
                  <span v-if="row.numeroEtudiant" class="font-mono bg-surface-200/70 dark:bg-surface-800 text-surface-700 dark:text-surface-300 px-1 py-0.2 rounded text-[9px] font-semibold" :title="'N° étudiant : ' + row.numeroEtudiant">
                    {{ row.numeroEtudiant }}
                  </span>
                  <span v-if="row.dateNaissance" class="text-[9px] text-surface-500 whitespace-nowrap" :title="'Date de naissance : ' + row.dateNaissance">
                    {{ row.dateNaissance }}
                  </span>
                  <span v-if="row.bac" class="px-1 py-0.2 rounded bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-300 font-semibold text-[9px] border border-amber-200/60 dark:border-amber-800/40 whitespace-nowrap" :title="'Baccalauréat : ' + row.bac">
                    {{ row.bac }}
                  </span>
                </div>
              </div>
            </div>
          </td>

          <!-- Rang -->
          <td class="px-2 py-2 text-center font-mono text-surface-500 border-r border-surface-200 dark:border-surface-700">
            {{ row.rang ?? '-' }}
          </td>

          <!-- Cellules par UE -->
          <template v-for="ue in ueColumns" :key="'val_' + ue.key">
            <template v-if="showDetails">
              <td
                v-for="ens in getEnseignementsForUe(ue.key)"
                :key="ens.key"
                class="px-2 py-2 text-center font-mono border-r border-surface-200 dark:border-surface-700"
                :class="getNoteClass(row.cells[ens.key])"
              >
                {{ formatNote(row.cells[ens.key]) }}
              </td>
            </template>

            <!-- Moyenne de l'UE avec teinte compétence -->
            <td
              class="px-2.5 py-2 text-center font-mono font-bold border-r border-surface-200 dark:border-surface-700"
              :class="[getCompetenceStyle(ue.color).cellBg, getNoteClass(row.cells[ue.key])]"
            >
              {{ formatNote(row.cells[ue.key]) }}
            </td>

            <!-- Statut UE -->
            <td
              class="px-2 py-2 text-center border-r-2 border-surface-300 dark:border-surface-600"
              :class="getCompetenceStyle(ue.color).cellBg"
            >
              <Tag
                :value="getUeStatut(row.cells[ue.key]).label"
                :severity="getUeStatut(row.cells[ue.key]).severity"
                class="text-[10px] font-bold px-1.5 py-0"
              />
            </td>
          </template>

          <!-- Bilan : Moyenne Générale -->
          <td class="px-3 py-2 text-center font-mono font-extrabold text-sm border-r border-surface-200 dark:border-surface-700" :class="getNoteClass(row.moyenne)">
            {{ formatNote(row.moyenne) }}
          </td>

          <!-- Bilan : UE validées -->
          <td class="px-3 py-2 text-center font-semibold border-r border-surface-200 dark:border-surface-700">
            <span :class="row.uesValidees === ueColumns.length ? 'text-emerald-600 dark:text-emerald-400 font-bold' : 'text-surface-700 dark:text-surface-300'">
              {{ row.uesValidees }} / {{ ueColumns.length }}
            </span>
          </td>

          <!-- Bilan : Décision -->
          <td class="px-4 py-2 text-center">
            <Tag
              :value="getDecisionBadge(row.decision, row.decisionLibelle).label"
              :severity="getDecisionBadge(row.decision, row.decisionLibelle).severity"
              class="text-[11px] font-semibold whitespace-nowrap"
            />
          </td>
        </tr>
      </tbody>

      <!-- Pied de tableau mode UE -->
      <tfoot class="bg-surface-100 dark:bg-surface-800 text-surface-800 dark:text-surface-100 font-bold border-t-2 border-surface-300 dark:border-surface-600">
        <tr class="footer-row">
          <td
            class="px-3.5 py-2.5 font-extrabold uppercase text-surface-900 dark:text-surface-100 border-r border-surface-200 dark:border-surface-700 bg-surface-100 dark:bg-surface-800 col-sticky-etudiant"
          >
            <div class="flex items-center gap-2">
              <i class="pi pi-chart-bar text-primary-600 dark:text-primary-400"></i>
              <span class="text-xs">Moyenne promo</span>
            </div>
          </td>

          <td class="px-2 py-2 text-center text-surface-400 border-r border-surface-200 dark:border-surface-700">-</td>

          <template v-for="ue in ueColumns" :key="'foot_' + ue.key">
            <template v-if="showDetails">
              <td
                v-for="ens in getEnseignementsForUe(ue.key)"
                :key="'foot_' + ens.key"
                class="px-2 py-2 text-center font-mono border-r border-surface-200 dark:border-surface-700 font-bold"
                :class="getNoteClass(getColumnAverage(ens.key))"
              >
                {{ formatNote(getColumnAverage(ens.key)) }}
              </td>
            </template>

            <!-- Moyenne promo UE avec teinte de la compétence -->
            <td
              class="px-2.5 py-2 text-center font-mono font-black border-r border-surface-200 dark:border-surface-700"
              :class="[getCompetenceStyle(ue.color).footBg, getNoteClass(getColumnAverage(ue.key))]"
            >
              {{ formatNote(getColumnAverage(ue.key)) }}
            </td>

            <td
              class="px-2 py-2 text-center border-r-2 border-surface-300 dark:border-surface-600 text-[10px]"
              :class="getCompetenceStyle(ue.color).footBg"
            >
              <span v-if="getUeValidationRate(ue.key) !== null" class="font-semibold text-surface-600 dark:text-surface-300" :title="'Taux de réussite UE : ' + getUeValidationRate(ue.key) + '%'">
                {{ getUeValidationRate(ue.key) }}%
              </span>
              <span v-else>-</span>
            </td>
          </template>

          <td class="px-3 py-2 text-center font-mono font-black text-sm border-r border-surface-200 dark:border-surface-700" :class="getNoteClass(getMoyenneGeneraleAverage())">
            {{ formatNote(getMoyenneGeneraleAverage()) }}
          </td>

          <td class="px-3 py-2 text-center font-bold text-xs border-r border-surface-200 dark:border-surface-700">
            <span v-if="getUesValideesAverage() !== null">
              {{ getUesValideesAverage() }} / {{ ueColumns.length }}
            </span>
            <span v-else>-</span>
          </td>

          <td class="px-4 py-2 text-center text-xs font-black text-emerald-600 dark:text-emerald-400">
            <span v-if="getTauxReussiteSemestre() !== null">
              {{ getTauxReussiteSemestre() }}% admis
            </span>
            <span v-else>-</span>
          </td>
        </tr>
      </tfoot>
    </table>

    <!-- ======================================================== -->
    <!-- MODE 2 : AFFICHAGE PAR RESSOURCE / SAÉ (À PLAT)          -->
    <!-- ======================================================== -->
    <table v-else class="w-full text-xs text-left text-surface-600 dark:text-surface-300 border-collapse">
      <thead class="bg-surface-100 dark:bg-surface-800 text-surface-800 dark:text-surface-100">
        <!-- Ligne 1 : Étudiant + Groupe Ressources/SAÉ + Synthèse UEs + Bilan -->
        <tr class="border-b border-surface-200 dark:border-surface-700 header-row-1">
          <th
            scope="col"
            rowspan="2"
            class="px-4 py-3 font-bold uppercase tracking-wider min-w-[220px] border-r border-surface-200 dark:border-surface-700 bg-surface-100 dark:bg-surface-800 col-sticky-etudiant"
          >
            Étudiant ({{ rows.length }})
          </th>
          <th
            scope="col"
            rowspan="2"
            class="px-3 py-3 text-center font-bold uppercase tracking-wider min-w-[60px] border-r border-surface-200 dark:border-surface-700 bg-surface-100 dark:bg-surface-800"
          >
            Rang
          </th>

          <!-- Groupe Enseignements (Ressources & SAÉ) -->
          <th
            scope="col"
            :colspan="singleEnseignementColumns.length"
            class="px-3 py-2 text-center font-extrabold border-r-2 border-surface-300 dark:border-surface-600 bg-slate-100 dark:bg-slate-800 text-slate-800 dark:text-slate-100"
          >
            <div class="flex items-center justify-center gap-3">
              <span>Ressources & SAÉ ({{ singleEnseignementColumns.length }})</span>
              <div class="flex items-center gap-2 text-[10px] font-normal">
                <span class="inline-flex items-center gap-1">
                  <span class="w-2.5 h-2.5 rounded-full bg-sky-500"></span> Ressource
                </span>
                <span class="inline-flex items-center gap-1">
                  <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span> SAÉ
                </span>
              </div>
            </div>
          </th>

          <!-- Groupe UEs / Compétences -->
          <th
            scope="col"
            :colspan="singleUeColumns.length * 2"
            class="px-3 py-2 text-center font-extrabold border-r-2 border-surface-300 dark:border-surface-600 bg-primary-50/80 dark:bg-primary-950/50 text-primary-800 dark:text-primary-200"
          >
            Moyennes par Compétence / UE ({{ singleUeColumns.length }})
          </th>

          <!-- Bilan Semestre -->
          <th scope="col" colspan="3" class="px-4 py-2 text-center font-bold bg-surface-200/70 dark:bg-surface-700/60">
            Bilan Semestre
          </th>
        </tr>

        <!-- Ligne 2 : Détail des colonnes Enseignements + Colonnes UE + Bilan -->
        <tr class="border-b border-surface-200 dark:border-surface-700 text-[11px] header-row-2">
          <!-- Colonnes de chaque Ressource / SAÉ avec petits carrés d'UE colorés par compétence -->
          <th
            v-for="col in singleEnseignementColumns"
            :key="col.key"
            scope="col"
            class="px-2 py-2 text-center font-medium bg-surface-50 dark:bg-surface-900/60 border-r border-surface-200 dark:border-surface-700 min-w-[85px]"
            :title="`${col.label} : ${col.sublabel || ''}`"
          >
            <!-- Badge Ressource / SAÉ + Code -->
            <div class="flex items-center justify-center gap-1 mb-1">
              <span
                class="px-1 py-0.1 rounded text-[8px] font-black uppercase"
                :class="col.nature === 'sae' ? 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300 border border-amber-300/50' : 'bg-sky-100 text-sky-800 dark:bg-sky-950 dark:text-sky-300 border border-sky-300/50'"
              >
                {{ col.nature === 'sae' ? 'SAÉ' : 'Ress.' }}
              </span>
              <span class="font-mono text-[11px] font-extrabold tracking-tight">{{ col.label }}</span>
            </div>

            <!-- Petits carrés visuels aux couleurs de chaque compétence associée -->
            <div
              v-if="col.ues && col.ues.length"
              class="flex items-center justify-center gap-1 flex-wrap"
            >
              <span
                v-for="ue in col.ues"
                :key="ue.id"
                class="w-4 h-4 rounded text-[9px] font-black flex items-center justify-center shadow-2xs transition-transform hover:scale-125 cursor-help"
                :class="getCompetenceStyle(ue.color).squareBg"
                :title="`${ue.label}${ue.competenceNomCourt ? ' · ' + ue.competenceNomCourt : ''} (${ue.libelle}) : coef ${ue.coefficient}${ue.ects ? ' / ' + ue.ects + ' ECTS' : ''}`"
              >
                {{ ue.numero }}
              </span>
            </div>
          </th>

          <!-- Colonnes UE : Moyenne + Statut avec la couleur de la compétence -->
          <template v-for="ue in singleUeColumns" :key="ue.key + '_header_summary'">
            <th
              scope="col"
              class="px-2.5 py-1.5 text-center font-bold border-r border-surface-200 dark:border-surface-700 min-w-[80px]"
              :class="[getCompetenceStyle(ue.color).headerBg, getCompetenceStyle(ue.color).headerText]"
              :title="ue.sublabel || ue.label"
            >
              <div class="flex items-center justify-center gap-1 flex-wrap">
                <span class="font-extrabold">{{ ue.label }}</span>
                <span
                  v-if="ue.competenceNomCourt"
                  class="text-[9px] font-black px-1.5 py-0.2 rounded shadow-2xs"
                  :class="getCompetenceStyle(ue.color).badgeBg"
                >
                  {{ ue.competenceNomCourt }}
                </span>
              </div>
              <div v-if="ue.ects" class="text-[9px] font-normal opacity-80 mt-0.5">{{ ue.ects }} ECTS</div>
            </th>
            <th
              scope="col"
              class="px-2 py-1.5 text-center font-bold border-r-2 border-surface-300 dark:border-surface-600 min-w-[55px]"
              :class="[getCompetenceStyle(ue.color).headerBg, getCompetenceStyle(ue.color).headerText]"
            >
              Statut
            </th>
          </template>

          <!-- Colonnes Bilan -->
          <th scope="col" class="px-3 py-1.5 text-center font-bold bg-surface-100 dark:bg-surface-800 border-r border-surface-200 dark:border-surface-700 min-w-[80px]">
            Moy. Gén.
          </th>
          <th scope="col" class="px-3 py-1.5 text-center font-bold bg-surface-100 dark:bg-surface-800 border-r border-surface-200 dark:border-surface-700 min-w-[85px]">
            UE Validées
          </th>
          <th scope="col" class="px-4 py-1.5 text-center font-bold bg-surface-100 dark:bg-surface-800 min-w-[130px]">
            Décision
          </th>
        </tr>
      </thead>

      <!-- Corps du tableau mode Enseignement (Ressource / SAÉ) -->
      <tbody class="divide-y divide-surface-200 dark:divide-surface-800">
        <tr
          v-for="(row, idx) in rows"
          :key="row.scolariteSemestreId"
          class="hover:bg-surface-50 dark:hover:bg-surface-800/60 transition-colors"
          :class="idx % 2 === 0 ? 'bg-surface-0 dark:bg-surface-900' : 'bg-surface-50/40 dark:bg-surface-900/40'"
        >
          <!-- Nom & Prénom étudiant -->
          <td
            class="px-3.5 py-2 text-surface-900 dark:text-surface-100 border-r border-surface-200 dark:border-surface-700 bg-inherit col-sticky-etudiant"
          >
            <div class="flex items-center gap-2.5">
              <span class="w-7 h-7 rounded-full bg-primary-100 dark:bg-primary-900/60 text-primary-700 dark:text-primary-300 font-bold flex items-center justify-center text-[10px] shrink-0">
                {{ row.nom.charAt(0) }}{{ row.prenom.charAt(0) }}
              </span>
              <div class="min-w-0 flex-1">
                <div class="truncate text-xs">
                  <span class="font-bold uppercase">{{ row.nom }}</span> {{ row.prenom }}
                </div>
                <div class="flex items-center gap-1.5 flex-wrap text-[10px] text-surface-500 dark:text-surface-400 mt-0.5">
                  <span v-if="row.numeroEtudiant" class="font-mono bg-surface-200/70 dark:bg-surface-800 text-surface-700 dark:text-surface-300 px-1 py-0.2 rounded text-[9px] font-semibold" :title="'N° étudiant : ' + row.numeroEtudiant">
                    {{ row.numeroEtudiant }}
                  </span>
                  <span v-if="row.dateNaissance" class="text-[9px] text-surface-500 whitespace-nowrap" :title="'Date de naissance : ' + row.dateNaissance">
                    {{ row.dateNaissance }}
                  </span>
                  <span v-if="row.bac" class="px-1 py-0.2 rounded bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-300 font-semibold text-[9px] border border-amber-200/60 dark:border-amber-800/40 whitespace-nowrap" :title="'Baccalauréat : ' + row.bac">
                    {{ row.bac }}
                  </span>
                </div>
              </div>
            </div>
          </td>

          <!-- Rang -->
          <td class="px-2 py-2 text-center font-mono text-surface-500 border-r border-surface-200 dark:border-surface-700">
            {{ row.rang ?? '-' }}
          </td>

          <!-- Notes de chaque Ressource & SAÉ unique -->
          <td
            v-for="col in singleEnseignementColumns"
            :key="'row_' + col.key"
            class="px-2 py-2 text-center font-mono border-r border-surface-200 dark:border-surface-700"
            :class="getNoteClass(row.cells[col.key])"
          >
            {{ formatNote(row.cells[col.key]) }}
          </td>

          <!-- Moyennes et Statuts des UEs avec teinte compétence -->
          <template v-for="ue in singleUeColumns" :key="'row_ue_' + ue.key">
            <td
              class="px-2.5 py-2 text-center font-mono font-bold border-r border-surface-200 dark:border-surface-700"
              :class="[getCompetenceStyle(ue.color).cellBg, getNoteClass(row.cells[ue.key])]"
            >
              {{ formatNote(row.cells[ue.key]) }}
            </td>
            <td
              class="px-2 py-2 text-center border-r-2 border-surface-300 dark:border-surface-600"
              :class="getCompetenceStyle(ue.color).cellBg"
            >
              <Tag
                :value="getUeStatut(row.cells[ue.key]).label"
                :severity="getUeStatut(row.cells[ue.key]).severity"
                class="text-[10px] font-bold px-1.5 py-0"
              />
            </td>
          </template>

          <!-- Bilan : Moyenne Générale -->
          <td class="px-3 py-2 text-center font-mono font-extrabold text-sm border-r border-surface-200 dark:border-surface-700" :class="getNoteClass(row.moyenne)">
            {{ formatNote(row.moyenne) }}
          </td>

          <!-- Bilan : UE Validées -->
          <td class="px-3 py-2 text-center font-semibold border-r border-surface-200 dark:border-surface-700">
            <span :class="row.uesValidees === singleUeColumns.length ? 'text-emerald-600 dark:text-emerald-400 font-bold' : 'text-surface-700 dark:text-surface-300'">
              {{ row.uesValidees }} / {{ singleUeColumns.length }}
            </span>
          </td>

          <!-- Bilan : Décision -->
          <td class="px-4 py-2 text-center">
            <Tag
              :value="getDecisionBadge(row.decision, row.decisionLibelle).label"
              :severity="getDecisionBadge(row.decision, row.decisionLibelle).severity"
              class="text-[11px] font-semibold whitespace-nowrap"
            />
          </td>
        </tr>
      </tbody>

      <!-- Pied de tableau mode Enseignement -->
      <tfoot class="bg-surface-100 dark:bg-surface-800 text-surface-800 dark:text-surface-100 font-bold border-t-2 border-surface-300 dark:border-surface-600">
        <tr class="footer-row">
          <td
            class="px-3.5 py-2.5 font-extrabold uppercase text-surface-900 dark:text-surface-100 border-r border-surface-200 dark:border-surface-700 bg-surface-100 dark:bg-surface-800 col-sticky-etudiant"
          >
            <div class="flex items-center gap-2">
              <i class="pi pi-chart-bar text-primary-600 dark:text-primary-400"></i>
              <span class="text-xs">Moyenne promo</span>
            </div>
          </td>

          <td class="px-2 py-2 text-center text-surface-400 border-r border-surface-200 dark:border-surface-700">-</td>

          <!-- Moyennes des Ressources & SAÉ -->
          <td
            v-for="col in singleEnseignementColumns"
            :key="'foot_' + col.key"
            class="px-2 py-2 text-center font-mono border-r border-surface-200 dark:border-surface-700 font-bold"
            :class="getNoteClass(getColumnAverage(col.key))"
          >
            {{ formatNote(getColumnAverage(col.key)) }}
          </td>

          <!-- Moyennes et Taux UEs avec teinte compétence -->
          <template v-for="ue in singleUeColumns" :key="'foot_ue_' + ue.key">
            <td
              class="px-2.5 py-2 text-center font-mono font-black border-r border-surface-200 dark:border-surface-700"
              :class="[getCompetenceStyle(ue.color).footBg, getNoteClass(getColumnAverage(ue.key))]"
            >
              {{ formatNote(getColumnAverage(ue.key)) }}
            </td>
            <td
              class="px-2 py-2 text-center border-r-2 border-surface-300 dark:border-surface-600 text-[10px]"
              :class="getCompetenceStyle(ue.color).footBg"
            >
              <span v-if="getUeValidationRate(ue.key) !== null" class="font-semibold text-surface-600 dark:text-surface-300" :title="'Taux de réussite UE : ' + getUeValidationRate(ue.key) + '%'">
                {{ getUeValidationRate(ue.key) }}%
              </span>
              <span v-else>-</span>
            </td>
          </template>

          <!-- Bilan promo -->
          <td class="px-3 py-2 text-center font-mono font-black text-sm border-r border-surface-200 dark:border-surface-700" :class="getNoteClass(getMoyenneGeneraleAverage())">
            {{ formatNote(getMoyenneGeneraleAverage()) }}
          </td>

          <td class="px-3 py-2 text-center font-bold text-xs border-r border-surface-200 dark:border-surface-700">
            <span v-if="getUesValideesAverage() !== null">
              {{ getUesValideesAverage() }} / {{ singleUeColumns.length }}
            </span>
            <span v-else>-</span>
          </td>

          <td class="px-4 py-2 text-center text-xs font-black text-emerald-600 dark:text-emerald-400">
            <span v-if="getTauxReussiteSemestre() !== null">
              {{ getTauxReussiteSemestre() }}% admis
            </span>
            <span v-else>-</span>
          </td>
        </tr>
      </tfoot>
    </table>
  </div>
</template>

<style scoped>
/* Mode Header Figé */
.has-sticky-header {
  max-height: calc(100vh - 290px);
  overflow-y: auto;
}

.has-sticky-header thead .header-row-1 th {
  position: sticky;
  top: 0;
  z-index: 20;
}

.has-sticky-header thead .header-row-2 th {
  position: sticky;
  top: 48px;
  z-index: 20;
}

/* Mode Colonne Étudiant Figée */
.has-sticky-col tbody td.col-sticky-etudiant {
  position: sticky;
  left: 0;
  z-index: 10;
  box-shadow: 2px 0 6px -2px rgba(0, 0, 0, 0.12);
}

/* Coin supérieur gauche (Header + Colonne Étudiant combinés) */
.has-sticky-header.has-sticky-col thead th.col-sticky-etudiant {
  position: sticky;
  top: 0;
  left: 0;
  z-index: 40;
  box-shadow: 2px 0 6px -2px rgba(0, 0, 0, 0.12);
}

/* Mode Footer Sticky en bas */
.has-sticky-header tfoot .footer-row td {
  position: sticky;
  bottom: 0;
  z-index: 20;
}

.has-sticky-header.has-sticky-col tfoot td.col-sticky-etudiant {
  position: sticky;
  bottom: 0;
  left: 0;
  z-index: 40;
  box-shadow: 2px -2px 6px -2px rgba(0, 0, 0, 0.12);
}
</style>
