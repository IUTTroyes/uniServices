<script setup>
import { computed } from 'vue'

const props = defineProps({
  visible: {
    type: Boolean,
    default: false
  },
  planCours: {
    type: Object,
    default: null
  },
  enseignement: {
    type: Object,
    default: null
  }
})

const emit = defineEmits(['update:visible', 'edit'])

const isVisible = computed({
  get: () => props.visible,
  set: (val) => emit('update:visible', val)
})

const getBadgeSeverity = (etat) => {
  switch (etat) {
    case 'COMPLET': return 'success'
    case 'EN_COURS': return 'warn'
    default: return 'danger'
  }
}

const getEtatLabel = (etat) => {
  switch (etat) {
    case 'COMPLET': return 'Plan de cours complet'
    case 'EN_COURS': return 'En cours de rédaction'
    default: return 'Non commencé'
  }
}

const printPlan = () => {
  window.print()
}
</script>

<template>
  <Dialog
    v-model:visible="isVisible"
    modal
    maximizable
    :style="{ width: '80vw', maxWidth: '1100px' }"
    :header="`Plan de cours - ${enseignement?.codeEnseignement || ''} ${enseignement?.libelle || ''}`"
    class="p-fluid plan-cours-dialog"
  >
    <div v-if="planCours" class="flex flex-col gap-6 printable-area">
      <!-- En-tête statut & méta -->
      <div class="flex flex-wrap items-center justify-between gap-4 p-4 rounded-xl bg-surface-50 dark:bg-surface-800 border border-surface-200 dark:border-surface-700">
        <div class="flex items-center gap-3">
          <Tag :value="enseignement?.typeLibelle || 'Enseignement'" severity="info" class="text-sm font-semibold uppercase" />
          <Tag :value="getEtatLabel(planCours?.etatPlanCours)" :severity="getBadgeSeverity(planCours?.etatPlanCours)" class="text-sm font-semibold" />
        </div>
        <div class="flex items-center gap-2 text-sm text-muted-color">
          <i class="pi pi-user text-primary" />
          <span>Responsable : <strong>{{ planCours?.responsable?.display || 'Non assigné' }}</strong></span>
        </div>
      </div>

      <!-- Heures et intervenants -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <!-- Volumes horaires -->
        <div class="card p-4 rounded-xl border border-surface-200 dark:border-surface-700">
          <h4 class="text-base font-bold text-surface-900 dark:text-surface-0 mb-3 flex items-center gap-2">
            <i class="pi pi-clock text-primary" /> Volumes horaires
          </h4>
          <div class="grid grid-cols-4 gap-2 text-center text-xs">
            <div class="p-2 rounded bg-surface-100 dark:bg-surface-800">
              <span class="text-muted-color block">CM prévus</span>
              <span class="text-base font-bold text-surface-800 dark:text-surface-100">{{ planCours.cmPrevu ?? '-' }}h</span>
            </div>
            <div class="p-2 rounded bg-surface-100 dark:bg-surface-800">
              <span class="text-muted-color block">TD prévus</span>
              <span class="text-base font-bold text-surface-800 dark:text-surface-100">{{ planCours.tdPrevu ?? '-' }}h</span>
            </div>
            <div class="p-2 rounded bg-surface-100 dark:bg-surface-800">
              <span class="text-muted-color block">TP prévus</span>
              <span class="text-base font-bold text-surface-800 dark:text-surface-100">{{ planCours.tpPrevu ?? '-' }}h</span>
            </div>
            <div class="p-2 rounded bg-surface-100 dark:bg-surface-800">
              <span class="text-muted-color block">Projet prévu</span>
              <span class="text-base font-bold text-surface-800 dark:text-surface-100">{{ planCours.projetPrevu ?? '-' }}h</span>
            </div>
          </div>
          <div v-if="planCours.cmRealise !== null || planCours.tdRealise !== null || planCours.tpRealise !== null" class="mt-3 pt-3 border-t border-surface-200 dark:border-surface-700">
            <span class="text-xs font-semibold text-muted-color block mb-2">Volumes réalisés :</span>
            <div class="grid grid-cols-4 gap-2 text-center text-xs">
              <div class="p-2 rounded bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300">
                <span class="block">CM : {{ planCours.cmRealise ?? 0 }}h</span>
              </div>
              <div class="p-2 rounded bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300">
                <span class="block">TD : {{ planCours.tdRealise ?? 0 }}h</span>
              </div>
              <div class="p-2 rounded bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300">
                <span class="block">TP : {{ planCours.tpRealise ?? 0 }}h</span>
              </div>
              <div class="p-2 rounded bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300">
                <span class="block">Projet : {{ planCours.projetRealise ?? 0 }}h</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Paramètres & Intervenants -->
        <div class="card p-4 rounded-xl border border-surface-200 dark:border-surface-700 flex flex-col justify-between">
          <div>
            <h4 class="text-base font-bold text-surface-900 dark:text-surface-0 mb-3 flex items-center gap-2">
              <i class="pi pi-users text-primary" /> Équipe pédagogique & Évaluations
            </h4>
            <div class="text-sm mb-2">
              <span class="text-muted-color">Nombre d'évaluations prévues :</span>
              <span class="font-bold ml-2">{{ planCours.nbNotes ?? 2 }} note(s)</span>
            </div>
            <div class="text-sm">
              <span class="text-muted-color block mb-1">Co-intervenants :</span>
              <div v-if="planCours.intervenants && planCours.intervenants.length > 0" class="flex flex-wrap gap-1">
                <Tag v-for="intervenant in planCours.intervenants" :key="intervenant.id" :value="intervenant.display || intervenant.nom" severity="secondary" />
              </div>
              <span v-else class="text-xs text-muted-color italic">Aucun co-intervenant renseigné.</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Description & Objectifs -->
      <div v-if="planCours.description || planCours.objectifs" class="card p-4 rounded-xl border border-surface-200 dark:border-surface-700">
        <h4 class="text-base font-bold text-surface-900 dark:text-surface-0 mb-3 flex items-center gap-2">
          <i class="pi pi-align-left text-primary" /> Objectifs pédagogiques & Description
        </h4>
        <div v-if="planCours.objectifs" class="mb-4">
          <span class="text-xs uppercase tracking-wide font-bold text-muted-color block mb-1">Objectifs</span>
          <div class="text-sm leading-relaxed whitespace-pre-line text-surface-700 dark:text-surface-300 bg-surface-50 dark:bg-surface-800/50 p-3 rounded-lg">{{ planCours.objectifs }}</div>
        </div>
        <div v-if="planCours.description">
          <span class="text-xs uppercase tracking-wide font-bold text-muted-color block mb-1">Description</span>
          <div class="text-sm leading-relaxed whitespace-pre-line text-surface-700 dark:text-surface-300 bg-surface-50 dark:bg-surface-800/50 p-3 rounded-lg">{{ planCours.description }}</div>
        </div>
      </div>

      <!-- Modalités d'évaluation & Bibliographie -->
      <div v-if="planCours.modalitesEvaluations || planCours.bibliographie" class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div v-if="planCours.modalitesEvaluations" class="card p-4 rounded-xl border border-surface-200 dark:border-surface-700">
          <h4 class="text-base font-bold text-surface-900 dark:text-surface-0 mb-2 flex items-center gap-2">
            <i class="pi pi-check-square text-primary" /> Modalités d'évaluation
          </h4>
          <div class="text-sm leading-relaxed whitespace-pre-line text-surface-700 dark:text-surface-300">{{ planCours.modalitesEvaluations }}</div>
        </div>
        <div v-if="planCours.bibliographie" class="card p-4 rounded-xl border border-surface-200 dark:border-surface-700">
          <h4 class="text-base font-bold text-surface-900 dark:text-surface-0 mb-2 flex items-center gap-2">
            <i class="pi pi-book text-primary" /> Bibliographie / Sitographie
          </h4>
          <div class="text-sm leading-relaxed whitespace-pre-line text-surface-700 dark:text-surface-300">{{ planCours.bibliographie }}</div>
        </div>
      </div>

      <!-- Séquences pédagogiques -->
      <div class="card p-4 rounded-xl border border-surface-200 dark:border-surface-700">
        <h4 class="text-base font-bold text-surface-900 dark:text-surface-0 mb-3 flex items-center gap-2">
          <i class="pi pi-list text-primary" /> Déroulé des séquences d'apprentissage
        </h4>
        <div v-if="planCours.planCoursSequences && planCours.planCoursSequences.length > 0">
          <DataTable :value="planCours.planCoursSequences" size="small" stripedRows class="text-sm">
            <Column field="ordre" header="#" style="width: 60px">
              <template #body="{ data, index }">
                <span class="font-bold text-muted-color">{{ data.ordre || (index + 1) }}</span>
              </template>
            </Column>
            <Column field="programme" header="Programme / Contenu de la séance" />
            <Column field="nbHeures" header="Durée" style="width: 120px">
              <template #body="{ data }">
                <Tag :value="data.nbHeures ? `${data.nbHeures}` : '-'" severity="secondary" />
              </template>
            </Column>
            <Column field="commentaire" header="Commentaires" />
          </DataTable>
        </div>
        <div v-else-if="planCours.saisiePlanCours" class="p-4 bg-surface-50 dark:bg-surface-800 rounded-lg text-sm whitespace-pre-line">
          {{ planCours.saisiePlanCours }}
        </div>
        <div v-else class="text-sm text-muted-color italic p-4 text-center">
          Aucune séquence renseignée pour ce plan de cours.
        </div>
      </div>

      <!-- Bilan / Réalisé -->
      <div v-if="planCours.planSuivi !== null || planCours.objectifsAtteints !== null || planCours.competencesAcquises !== null" class="card p-4 rounded-xl border border-surface-200 dark:border-surface-700">
        <h4 class="text-base font-bold text-surface-900 dark:text-surface-0 mb-3 flex items-center gap-2">
          <i class="pi pi-chart-bar text-primary" /> Bilan pédagogique
        </h4>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-4">
          <div class="p-3 rounded-lg border border-surface-200 dark:border-surface-700 flex flex-col gap-1">
            <span class="text-xs text-muted-color">Plan suivi comme prévu ?</span>
            <Tag :value="planCours.planSuivi ? 'Oui' : 'Non'" :severity="planCours.planSuivi ? 'success' : 'warn'" class="w-fit" />
            <p v-if="planCours.planSuiviCommentaire" class="text-xs text-muted-color mt-1 italic">{{ planCours.planSuiviCommentaire }}</p>
          </div>
          <div class="p-3 rounded-lg border border-surface-200 dark:border-surface-700 flex flex-col gap-1">
            <span class="text-xs text-muted-color">Objectifs atteints ?</span>
            <Tag :value="planCours.objectifsAtteints ? 'Oui' : 'Non'" :severity="planCours.objectifsAtteints ? 'success' : 'warn'" class="w-fit" />
            <p v-if="planCours.objectifsAtteintsCommentaire" class="text-xs text-muted-color mt-1 italic">{{ planCours.objectifsAtteintsCommentaire }}</p>
          </div>
          <div class="p-3 rounded-lg border border-surface-200 dark:border-surface-700 flex flex-col gap-1">
            <span class="text-xs text-muted-color">Compétences acquises ?</span>
            <Tag :value="planCours.competencesAcquises ? 'Oui' : 'Non'" :severity="planCours.competencesAcquises ? 'success' : 'warn'" class="w-fit" />
            <p v-if="planCours.competencesAcquisesCommentaire" class="text-xs text-muted-color mt-1 italic">{{ planCours.competencesAcquisesCommentaire }}</p>
          </div>
        </div>
      </div>
    </div>
    <div v-else class="text-center p-8 text-muted-color">
      Aucune donnée disponible.
    </div>

    <template #footer>
      <div class="flex justify-between w-full">
        <Button label="Imprimer" icon="pi pi-print" severity="secondary" outlined @click="printPlan" />
        <div class="flex gap-2">
          <Button label="Fermer" severity="secondary" @click="isVisible = false" />
          <Button v-if="planCours?.id" label="Modifier" icon="pi pi-pencil" severity="primary" @click="$emit('edit', planCours.id)" />
        </div>
      </div>
    </template>
  </Dialog>
</template>

<style scoped>
@media print {
  body * {
    visibility: hidden;
  }
  .printable-area, .printable-area * {
    visibility: visible;
  }
  .printable-area {
    position: absolute;
    left: 0;
    top: 0;
    width: 100%;
  }
}
</style>
