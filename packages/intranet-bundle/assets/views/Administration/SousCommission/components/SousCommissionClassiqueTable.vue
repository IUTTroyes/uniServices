<script setup lang="ts">
import { computed } from 'vue';
import type { CommissionColumn, CommissionRow } from '@/service/commissionService';

const props = withDefaults(
  defineProps<{
    rows: CommissionRow[];
    columns: CommissionColumn[];
    showDetails: boolean;
    stickyHeader?: boolean;
    stickyColumn?: boolean;
  }>(),
  {
    stickyHeader: true,
    stickyColumn: true,
  }
);

const ueColumns = computed(() => props.columns.filter((col) => col.type === 'ue'));

const getEnseignementsForUe = (ueKey: string) => {
  return props.columns.filter((col) => col.type === 'enseignement' && col.parentKey === ueKey);
};

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

const getDecisionBadge = (decision: boolean | null | undefined, libelle: string) => {
  if (decision === true) {
    return { label: libelle || 'Semestre validé', severity: 'success' };
  }
  if (decision === false) {
    return { label: libelle || 'Non validé', severity: 'danger' };
  }
  return { label: libelle || 'En attente', severity: 'secondary' };
};

// Calcul des moyennes pour la ligne de pied de tableau
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
    <table class="w-full text-xs text-left text-surface-600 dark:text-surface-300 border-collapse">
      <!-- En-tête multi-lignes pour diplômes classiques -->
      <thead class="bg-surface-100 dark:bg-surface-800 text-surface-800 dark:text-surface-100">
        <tr class="border-b border-surface-200 dark:border-surface-700 header-row-1">
          <th
            scope="col"
            rowspan="2"
            class="px-4 py-3 font-bold uppercase tracking-wider min-w-[200px] border-r border-surface-200 dark:border-surface-700 bg-surface-100 dark:bg-surface-800 col-sticky-etudiant"
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

          <!-- Moyenne générale mise en avant pour le mode classique -->
          <th
            scope="col"
            rowspan="2"
            class="px-3 py-3 text-center font-bold uppercase tracking-wider min-w-[90px] border-r-2 border-surface-300 dark:border-surface-600 bg-emerald-50/70 dark:bg-emerald-950/30 text-emerald-800 dark:text-emerald-300"
          >
            Moy. Gén.
          </th>

          <!-- Entête des UE -->
          <template v-for="ue in ueColumns" :key="ue.key">
            <th
              scope="col"
              :colspan="showDetails ? getEnseignementsForUe(ue.key).length + 1 : 1"
              class="px-3 py-2 text-center font-bold border-r-2 border-surface-300 dark:border-surface-600 bg-surface-200/50 dark:bg-surface-700/50"
            >
              <div class="flex items-center justify-center gap-1.5">
                <span class="text-sm font-extrabold">{{ ue.label }}</span>
                <span v-if="ue.coefficient" class="text-[10px] px-1.5 py-0.5 rounded-full bg-surface-300/60 dark:bg-surface-600 font-semibold">
                  coef {{ ue.coefficient }}
                </span>
              </div>
              <div v-if="ue.sublabel" class="text-[10px] font-normal truncate max-w-[160px] mx-auto opacity-75" :title="ue.sublabel">
                {{ ue.sublabel }}
              </div>
            </th>
          </template>

          <!-- Décision globale -->
          <th
            scope="col"
            rowspan="2"
            class="px-4 py-3 text-center font-bold min-w-[130px] bg-surface-100 dark:bg-surface-800"
          >
            Décision
          </th>
        </tr>

        <!-- Ligne 2 : Matières détaillées + Moyenne UE -->
        <tr class="border-b border-surface-200 dark:border-surface-700 text-[11px] header-row-2">
          <template v-for="ue in ueColumns" :key="ue.key + '_sub'">
            <template v-if="showDetails">
              <th
                v-for="ens in getEnseignementsForUe(ue.key)"
                :key="ens.key"
                scope="col"
                class="px-2 py-1.5 text-center font-medium bg-surface-50 dark:bg-surface-900/40 border-r border-surface-200 dark:border-surface-700 min-w-[75px]"
                :title="`${ens.label} (coef: ${ens.coefficient ?? 1})`"
              >
                <div class="truncate max-w-[85px] mx-auto font-mono text-[10px]">{{ ens.label }}</div>
                <div class="text-[9px] text-surface-400 font-normal">coef {{ ens.coefficient ?? 1 }}</div>
              </th>
            </template>

            <!-- Moyenne de l'UE -->
            <th scope="col" class="px-3 py-1.5 text-center font-bold bg-surface-100 dark:bg-surface-800 border-r-2 border-surface-300 dark:border-surface-600 min-w-[75px]">
              Moy. UE
            </th>
          </template>
        </tr>
      </thead>

      <!-- Corps du tableau -->
      <tbody class="divide-y divide-surface-200 dark:divide-surface-800">
        <tr
          v-for="(row, idx) in rows"
          :key="row.scolariteSemestreId"
          class="hover:bg-surface-50 dark:hover:bg-surface-800/60 transition-colors"
          :class="idx % 2 === 0 ? 'bg-surface-0 dark:bg-surface-900' : 'bg-surface-50/40 dark:bg-surface-900/40'"
        >
          <!-- Nom & Prénom étudiant avec N° étudiant, date de naissance et bac -->
          <td
            class="px-3.5 py-2 text-surface-900 dark:text-surface-100 border-r border-surface-200 dark:border-surface-700 bg-inherit col-sticky-etudiant"
          >
            <div class="flex items-center gap-2.5">
              <span class="w-7 h-7 rounded-full bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-200 font-bold flex items-center justify-center text-[10px] shrink-0">
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

          <!-- Moyenne générale mise en valeur -->
          <td
            class="px-3 py-2 text-center font-mono font-extrabold text-sm border-r-2 border-surface-300 dark:border-surface-600 bg-emerald-50/30 dark:bg-emerald-950/20"
            :class="getNoteClass(row.moyenne)"
          >
            {{ formatNote(row.moyenne) }}
          </td>

          <!-- Notes par UE et Matières -->
          <template v-for="ue in ueColumns" :key="'val_' + ue.key">
            <!-- Matières détaillées si showDetails -->
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

            <!-- Moyenne de l'UE -->
            <td
              class="px-2.5 py-2 text-center font-mono font-bold border-r-2 border-surface-300 dark:border-surface-600"
              :class="getNoteClass(row.cells[ue.key])"
            >
              {{ formatNote(row.cells[ue.key]) }}
            </td>
          </template>

          <!-- Décision -->
          <td class="px-4 py-2 text-center">
            <Tag
              :value="getDecisionBadge(row.decision, row.decisionLibelle).label"
              :severity="getDecisionBadge(row.decision, row.decisionLibelle).severity"
              class="text-[11px] font-semibold whitespace-nowrap"
            />
          </td>
        </tr>
      </tbody>

      <!-- Pied de tableau : Moyennes de la promotion -->
      <tfoot class="bg-surface-100 dark:bg-surface-800 text-surface-800 dark:text-surface-100 font-bold border-t-2 border-surface-300 dark:border-surface-600">
        <tr class="footer-row">
          <!-- Titre Moyenne Promo sticky -->
          <td
            class="px-3.5 py-2.5 font-extrabold uppercase text-surface-900 dark:text-surface-100 border-r border-surface-200 dark:border-surface-700 bg-surface-100 dark:bg-surface-800 col-sticky-etudiant"
          >
            <div class="flex items-center gap-2">
              <i class="pi pi-chart-bar text-primary-600 dark:text-primary-400"></i>
              <span class="text-xs">Moyenne promo</span>
            </div>
          </td>

          <!-- Rang -->
          <td class="px-2 py-2 text-center text-surface-400 border-r border-surface-200 dark:border-surface-700">-</td>

          <!-- Moyenne générale promo -->
          <td
            class="px-3 py-2 text-center font-mono font-black text-sm border-r-2 border-surface-300 dark:border-surface-600 bg-emerald-100/50 dark:bg-emerald-950/40"
            :class="getNoteClass(getMoyenneGeneraleAverage())"
          >
            {{ formatNote(getMoyenneGeneraleAverage()) }}
          </td>

          <!-- Moyennes par UE et matières -->
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

            <!-- Moyenne de l'UE -->
            <td
              class="px-2.5 py-2 text-center font-mono font-black border-r-2 border-surface-300 dark:border-surface-600"
              :class="getNoteClass(getColumnAverage(ue.key))"
            >
              {{ formatNote(getColumnAverage(ue.key)) }}
            </td>
          </template>

          <!-- Taux de réussite semestre -->
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
  top: 45px;
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
