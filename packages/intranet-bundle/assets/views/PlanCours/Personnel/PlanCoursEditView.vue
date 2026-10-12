<script setup>
import { onMounted, ref, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { HeaderComponent } from '@components'
import { getPlanCoursService, updatePlanCoursService, getPersonnelsService } from '@requests'
import { showSuccess, showDanger } from '@helpers/toast'
import PlanCoursShowDialog from '@/components/PlanCours/PlanCoursShowDialog.vue'

const route = useRoute()
const router = useRouter()

const planCoursId = ref(route.params.id)
const isLoading = ref(true)
const isSaving = ref(false)
const activeStep = ref(1)

const planCours = ref(null)
const enseignement = ref(null)
const personnels = ref([])

// Form model
const form = ref({
  cmPrevu: null,
  tdPrevu: null,
  tpPrevu: null,
  projetPrevu: null,
  cmRealise: null,
  tdRealise: null,
  tpRealise: null,
  projetRealise: null,
  nbNotes: 2,
  bibliographie: '',
  description: '',
  objectifs: '',
  modalitesEvaluations: '',
  livrables: '',
  miseEnOeuvre: '',
  modaliteEncadrementAutonomie: '',
  saisiePlanCours: '',
  planSuivi: null,
  planSuiviCommentaire: '',
  objectifsAtteints: null,
  objectifsAtteintsCommentaire: '',
  competencesAcquises: null,
  competencesAcquisesCommentaire: '',
  modeEvaluationCommentaire: '',
  commentairesStep1: '',
  commentairesStep2: '',
  commentairesStep3: '',
  responsableId: null,
  intervenantIds: [],
  sequences: []
})

const showPreviewDialog = ref(false)

const loadData = async () => {
  isLoading.value = true
  try {
    const pc = await getPlanCoursService(planCoursId.value)
    planCours.value = pc
    enseignement.value = pc.enseignement

    // Pré-remplir le formulaire
    form.value = {
      cmPrevu: pc.cmPrevu,
      tdPrevu: pc.tdPrevu,
      tpPrevu: pc.tpPrevu,
      projetPrevu: pc.projetPrevu,
      cmRealise: pc.cmRealise,
      tdRealise: pc.tdRealise,
      tpRealise: pc.tpRealise,
      projetRealise: pc.projetRealise,
      nbNotes: pc.nbNotes ?? 2,
      bibliographie: pc.bibliographie || '',
      description: pc.description || '',
      objectifs: pc.objectifs || '',
      modalitesEvaluations: pc.modalitesEvaluations || '',
      livrables: pc.livrables || '',
      miseEnOeuvre: pc.miseEnOeuvre || '',
      modaliteEncadrementAutonomie: pc.modaliteEncadrementAutonomie || '',
      saisiePlanCours: pc.saisiePlanCours || '',
      planSuivi: pc.planSuivi,
      planSuiviCommentaire: pc.planSuiviCommentaire || '',
      objectifsAtteints: pc.objectifsAtteints,
      objectifsAtteintsCommentaire: pc.objectifsAtteintsCommentaire || '',
      competencesAcquises: pc.competencesAcquises,
      competencesAcquisesCommentaire: pc.competencesAcquisesCommentaire || '',
      modeEvaluationCommentaire: pc.modeEvaluationCommentaire || '',
      commentairesStep1: pc.commentairesStep1 || '',
      commentairesStep2: pc.commentairesStep2 || '',
      commentairesStep3: pc.commentairesStep3 || '',
      responsableId: pc.responsable?.id || null,
      intervenantIds: (pc.intervenants || []).map(i => i.id),
      sequences: (pc.planCoursSequences || []).map(s => ({
        id: s.id,
        ordre: s.ordre,
        programme: s.programme || '',
        nbHeures: s.nbHeures || '',
        commentaire: s.commentaire || ''
      }))
    }

    // Charger les personnels pour les intervenants
    try {
      const pers = await getPersonnelsService()
      personnels.value = pers || []
    } catch (e) {
      console.warn('Impossible de charger la liste complète des personnels:', e)
    }
  } catch (error) {
    console.error('Erreur chargement plan cours:', error)
    showDanger('Erreur lors du chargement du plan de cours.')
  } finally {
    isLoading.value = false
  }
}

onMounted(async () => {
  await loadData()
})

const isSae = computed(() => {
  return enseignement.value?.type === 'sae'
})

// Séquences management
const addSequence = () => {
  const nextOrder = form.value.sequences.length + 1
  form.value.sequences.push({
    ordre: nextOrder,
    programme: '',
    nbHeures: '2h',
    commentaire: ''
  })
}

const removeSequence = (index) => {
  form.value.sequences.splice(index, 1)
  // Réordonner
  form.value.sequences.forEach((s, i) => {
    s.ordre = i + 1
  })
}

const savePlan = async (etat = 'EN_COURS', redirect = false) => {
  isSaving.value = true
  try {
    const payload = {
      cmPrevu: form.value.cmPrevu !== null ? Number(form.value.cmPrevu) : null,
      tdPrevu: form.value.tdPrevu !== null ? Number(form.value.tdPrevu) : null,
      tpPrevu: form.value.tpPrevu !== null ? Number(form.value.tpPrevu) : null,
      projetPrevu: form.value.projetPrevu !== null ? Number(form.value.projetPrevu) : null,
      cmRealise: form.value.cmRealise !== null ? Number(form.value.cmRealise) : null,
      tdRealise: form.value.tdRealise !== null ? Number(form.value.tdRealise) : null,
      tpRealise: form.value.tpRealise !== null ? Number(form.value.tpRealise) : null,
      projetRealise: form.value.projetRealise !== null ? Number(form.value.projetRealise) : null,
      nbNotes: Number(form.value.nbNotes) || 2,
      bibliographie: form.value.bibliographie,
      description: form.value.description,
      objectifs: form.value.objectifs,
      modalitesEvaluations: form.value.modalitesEvaluations,
      livrables: form.value.livrables,
      miseEnOeuvre: form.value.miseEnOeuvre,
      modaliteEncadrementAutonomie: form.value.modaliteEncadrementAutonomie,
      saisiePlanCours: form.value.saisiePlanCours,
      planSuivi: form.value.planSuivi,
      planSuiviCommentaire: form.value.planSuiviCommentaire,
      objectifsAtteints: form.value.objectifsAtteints,
      objectifsAtteintsCommentaire: form.value.objectifsAtteintsCommentaire,
      competencesAcquises: form.value.competencesAcquises,
      competencesAcquisesCommentaire: form.value.competencesAcquisesCommentaire,
      modeEvaluationCommentaire: form.value.modeEvaluationCommentaire,
      commentairesStep1: form.value.commentairesStep1,
      commentairesStep2: form.value.commentairesStep2,
      commentairesStep3: form.value.commentairesStep3,
      etatPlanCours: etat,
      planCoursSequences: form.value.sequences.map((s, idx) => ({
        ordre: idx + 1,
        programme: s.programme || `Séance ${idx + 1}`,
        nbHeures: s.nbHeures,
        commentaire: s.commentaire
      }))
    }

    if (form.value.responsableId) {
      payload.responsable = `/api/personnels/${form.value.responsableId}`
    }
    if (form.value.intervenantIds && form.value.intervenantIds.length > 0) {
      payload.intervenants = form.value.intervenantIds.map(id => `/api/personnels/${id}`)
    }

    const updated = await updatePlanCoursService(planCoursId.value, payload, false)
    planCours.value = updated
    showSuccess(etat === 'COMPLET' ? 'Plan de cours validé et transmis avec succès !' : 'Plan de cours enregistré.')

    if (redirect) {
      router.push('/intranet/plan-de-cours')
    }
  } catch (error) {
    console.error('Erreur sauvegarde:', error)
    showDanger('Erreur lors de la sauvegarde du plan de cours.')
  } finally {
    isSaving.value = false
  }
}
</script>

<template>
  <div>
    <HeaderComponent
      icon="pi pi-pencil"
      :titre="`Plan de cours - ${enseignement?.codeEnseignement || ''} ${enseignement?.libelle || ''}`"
      description="Renseignez le contenu pédagogique, les volumes horaires et le bilan du cours."
    />

    <!-- Loader -->
    <div v-if="isLoading" class="flex justify-center p-12">
      <ProgressSpinner />
    </div>

    <div v-else class="flex flex-col gap-6">
      <!-- Barre d'état & retour -->
      <div class="card p-4 rounded-xl border border-surface-200 dark:border-surface-700 flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-3">
          <Button icon="pi pi-arrow-left" severity="secondary" text @click="router.push('/intranet/plan-de-cours')" />
          <div>
            <h3 class="text-base font-bold text-surface-900 dark:text-surface-0">{{ enseignement?.libelle }}</h3>
            <span class="text-xs text-muted-color">Code : <strong>{{ enseignement?.codeEnseignement }}</strong></span>
          </div>
        </div>

        <div class="flex items-center gap-2">
          <Button
            label="Aperçu"
            icon="pi pi-eye"
            severity="secondary"
            outlined
            @click="showPreviewDialog = true"
          />
          <Button
            label="Enregistrer le brouillon"
            icon="pi pi-save"
            severity="secondary"
            :loading="isSaving"
            @click="savePlan('EN_COURS', false)"
          />
          <Button
            label="Valider et transmettre"
            icon="pi pi-check"
            severity="success"
            :loading="isSaving"
            @click="savePlan('COMPLET', true)"
          />
        </div>
      </div>

      <!-- Navigation par étapes -->
      <div class="card p-2 rounded-xl border border-surface-200 dark:border-surface-700">
        <div class="grid grid-cols-3 gap-2 text-center">
          <button
            type="button"
            class="p-3 rounded-lg text-sm font-semibold transition-all flex items-center justify-center gap-2"
            :class="activeStep === 1 ? 'bg-primary text-primary-contrast shadow' : 'text-muted-color hover:bg-surface-100 dark:hover:bg-surface-800'"
            @click="activeStep = 1"
          >
            <span class="w-6 h-6 rounded-full border flex items-center justify-center text-xs font-bold" :class="activeStep === 1 ? 'border-white' : 'border-current'">1</span>
            <span>Paramètres pédagogiques</span>
          </button>

          <button
            type="button"
            class="p-3 rounded-lg text-sm font-semibold transition-all flex items-center justify-center gap-2"
            :class="activeStep === 2 ? 'bg-primary text-primary-contrast shadow' : 'text-muted-color hover:bg-surface-100 dark:hover:bg-surface-800'"
            @click="activeStep = 2"
          >
            <span class="w-6 h-6 rounded-full border flex items-center justify-center text-xs font-bold" :class="activeStep === 2 ? 'border-white' : 'border-current'">2</span>
            <span>Déroulé & Séquences</span>
          </button>

          <button
            type="button"
            class="p-3 rounded-lg text-sm font-semibold transition-all flex items-center justify-center gap-2"
            :class="activeStep === 3 ? 'bg-primary text-primary-contrast shadow' : 'text-muted-color hover:bg-surface-100 dark:hover:bg-surface-800'"
            @click="activeStep = 3"
          >
            <span class="w-6 h-6 rounded-full border flex items-center justify-center text-xs font-bold" :class="activeStep === 3 ? 'border-white' : 'border-current'">3</span>
            <span>Bilan & Réalisé</span>
          </button>
        </div>
      </div>

      <!-- CONTENU ÉTAPE 1 -->
      <div v-show="activeStep === 1" class="flex flex-col gap-6">
        <!-- Volumes horaires prévus -->
        <div class="card p-5 rounded-2xl border border-surface-200 dark:border-surface-700">
          <h3 class="text-base font-bold mb-4 flex items-center gap-2">
            <i class="pi pi-clock text-primary" /> Volumes horaires prévus
          </h3>
          <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div>
              <label class="block text-xs font-semibold text-muted-color mb-1">CM prévus (heures)</label>
              <InputNumber v-model="form.cmPrevu" :min="0" :max="200" :minFractionDigits="0" :maxFractionDigits="1" class="w-full" placeholder="0" />
            </div>
            <div>
              <label class="block text-xs font-semibold text-muted-color mb-1">TD prévus (heures)</label>
              <InputNumber v-model="form.tdPrevu" :min="0" :max="200" :minFractionDigits="0" :maxFractionDigits="1" class="w-full" placeholder="0" />
            </div>
            <div>
              <label class="block text-xs font-semibold text-muted-color mb-1">TP prévus (heures)</label>
              <InputNumber v-model="form.tpPrevu" :min="0" :max="200" :minFractionDigits="0" :maxFractionDigits="1" class="w-full" placeholder="0" />
            </div>
            <div>
              <label class="block text-xs font-semibold text-muted-color mb-1">Projet / SAÉ prévu (heures)</label>
              <InputNumber v-model="form.projetPrevu" :min="0" :max="200" :minFractionDigits="0" :maxFractionDigits="1" class="w-full" placeholder="0" />
            </div>
          </div>
        </div>

        <!-- Équipe et évaluations -->
        <div class="card p-5 rounded-2xl border border-surface-200 dark:border-surface-700">
          <h3 class="text-base font-bold mb-4 flex items-center gap-2">
            <i class="pi pi-users text-primary" /> Équipe pédagogique & Évaluations
          </h3>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
              <label class="block text-xs font-semibold text-muted-color mb-1">Responsable / Auteur</label>
              <Select
                v-model="form.responsableId"
                :options="personnels"
                optionLabel="displayPr"
                optionValue="id"
                placeholder="Sélectionner le responsable"
                filter
                class="w-full"
              />
            </div>
            <div>
              <label class="block text-xs font-semibold text-muted-color mb-1">Co-intervenants</label>
              <MultiSelect
                v-model="form.intervenantIds"
                :options="personnels"
                optionLabel="displayPr"
                optionValue="id"
                placeholder="Sélectionner les intervenants"
                filter
                display="chip"
                class="w-full"
              />
            </div>
            <div>
              <label class="block text-xs font-semibold text-muted-color mb-1">Nombre d'évaluations prévues</label>
              <InputNumber v-model="form.nbNotes" :min="1" :max="10" class="w-full" />
            </div>
          </div>
        </div>

        <!-- Description & Objectifs -->
        <div class="card p-5 rounded-2xl border border-surface-200 dark:border-surface-700 flex flex-col gap-4">
          <h3 class="text-base font-bold flex items-center gap-2">
            <i class="pi pi-align-left text-primary" /> Contenu pédagogique
          </h3>
          <div>
            <label class="block text-xs font-semibold text-muted-color mb-1">Objectifs d'apprentissage</label>
            <Textarea v-model="form.objectifs" rows="3" autoResize class="w-full" placeholder="Objectifs pédagogiques poursuivis..." />
          </div>
          <div>
            <label class="block text-xs font-semibold text-muted-color mb-1">Description générale du module</label>
            <Textarea v-model="form.description" rows="3" autoResize class="w-full" placeholder="Description générale..." />
          </div>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-semibold text-muted-color mb-1">Modalités d'évaluation prévues</label>
              <Textarea v-model="form.modalitesEvaluations" rows="3" autoResize class="w-full" placeholder="Ex: 1 DS écrit coefficient 60%, 1 TP noté coefficient 40%..." />
            </div>
            <div>
              <label class="block text-xs font-semibold text-muted-color mb-1">Bibliographie / Sitographie</label>
              <Textarea v-model="form.bibliographie" rows="3" autoResize class="w-full" placeholder="Ouvrages, sites de référence recommandés..." />
            </div>
          </div>

          <!-- Spécifique SAÉ si applicable -->
          <div v-if="isSae" class="pt-4 border-t border-surface-100 dark:border-surface-800 flex flex-col gap-4">
            <Tag value="Spécifique SAÉ" severity="warn" class="w-fit" />
            <div>
              <label class="block text-xs font-semibold text-muted-color mb-1">Livrables attendus</label>
              <Textarea v-model="form.livrables" rows="2" class="w-full" placeholder="Rapports, code source, soutenance..." />
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-xs font-semibold text-muted-color mb-1">Mise en œuvre</label>
                <Textarea v-model="form.miseEnOeuvre" rows="2" class="w-full" placeholder="Modalités de mise en œuvre..." />
              </div>
              <div>
                <label class="block text-xs font-semibold text-muted-color mb-1">Modalités d'encadrement & autonomie</label>
                <Textarea v-model="form.modaliteEncadrementAutonomie" rows="2" class="w-full" placeholder="Temps encadré vs autonomie..." />
              </div>
            </div>
          </div>

          <div>
            <label class="block text-xs font-semibold text-muted-color mb-1">Commentaires libres (Étape 1)</label>
            <Textarea v-model="form.commentairesStep1" rows="2" class="w-full" placeholder="Commentaires éventuels sur les prévisions..." />
          </div>
        </div>

        <div class="flex justify-end gap-2">
          <Button label="Passer à l'étape 2 : Séquences" icon="pi pi-arrow-right" iconPos="right" @click="activeStep = 2" />
        </div>
      </div>

      <!-- CONTENU ÉTAPE 2 : SÉQUENCES -->
      <div v-show="activeStep === 2" class="flex flex-col gap-6">
        <div class="card p-5 rounded-2xl border border-surface-200 dark:border-surface-700">
          <div class="flex flex-wrap items-center justify-between gap-4 mb-4">
            <div>
              <h3 class="text-base font-bold flex items-center gap-2">
                <i class="pi pi-list text-primary" /> Déroulé des séances d'apprentissage
              </h3>
              <p class="text-xs text-muted-color mt-0.5">
                Découpez votre enseignement en séances ordonnées (CM, TD, TP ou étapes de projet).
              </p>
            </div>
            <Button label="Ajouter une séance" icon="pi pi-plus" size="small" @click="addSequence" />
          </div>

          <!-- Tableau dynamique des séquences -->
          <div v-if="form.sequences.length > 0" class="flex flex-col gap-3">
            <div
              v-for="(seq, index) in form.sequences"
              :key="index"
              class="p-4 rounded-xl bg-surface-50 dark:bg-surface-800/60 border border-surface-200 dark:border-surface-700 flex flex-col md:flex-row items-start md:items-center gap-3"
            >
              <div class="w-10 h-10 rounded-lg bg-surface-200 dark:bg-surface-700 flex items-center justify-center font-bold text-sm">
                {{ index + 1 }}
              </div>

              <div class="flex-1 w-full">
                <label class="block text-xs text-muted-color mb-0.5">Programme / Contenu de la séance</label>
                <InputText v-model="seq.programme" class="w-full" placeholder="Ex: Introduction aux composants Vue.js, TP mise en place..." />
              </div>

              <div class="w-full md:w-32">
                <label class="block text-xs text-muted-color mb-0.5">Durée</label>
                <InputText v-model="seq.nbHeures" class="w-full" placeholder="Ex: 2h, 4h" />
              </div>

              <div class="flex-1 w-full">
                <label class="block text-xs text-muted-color mb-0.5">Commentaires</label>
                <InputText v-model="seq.commentaire" class="w-full" placeholder="Supports, matériel nécessaire..." />
              </div>

              <Button icon="pi pi-trash" severity="danger" text @click="removeSequence(index)" title="Supprimer cette séance" class="mt-4 md:mt-0" />
            </div>
          </div>

          <div v-else class="p-8 text-center bg-surface-50 dark:bg-surface-800/40 rounded-xl border border-dashed border-surface-300 dark:border-surface-700">
            <p class="text-sm text-muted-color mb-3">Aucune séance détaillée pour le moment.</p>
            <Button label="Ajouter la première séance" icon="pi pi-plus" size="small" @click="addSequence" />
          </div>
        </div>

        <!-- Saisie libre alternative -->
        <div class="card p-5 rounded-2xl border border-surface-200 dark:border-surface-700">
          <h3 class="text-base font-bold mb-2 flex items-center gap-2">
            <i class="pi pi-file-edit text-primary" /> Saisie libre alternative du plan de cours
          </h3>
          <p class="text-xs text-muted-color mb-3">
            Si vous préférez rédiger votre plan de cours sous forme d'un texte continu ou d'un descriptif libre :
          </p>
          <Textarea v-model="form.saisiePlanCours" rows="6" autoResize class="w-full" placeholder="Rédigez ici le déroulé global de votre cours..." />

          <div class="mt-4">
            <label class="block text-xs font-semibold text-muted-color mb-1">Commentaires libres (Étape 2)</label>
            <Textarea v-model="form.commentairesStep2" rows="2" class="w-full" placeholder="Remarques sur le déroulement..." />
          </div>
        </div>

        <div class="flex justify-between">
          <Button label="Étape précédente" icon="pi pi-arrow-left" severity="secondary" @click="activeStep = 1" />
          <Button label="Passer à l'étape 3 : Bilan" icon="pi pi-arrow-right" iconPos="right" @click="activeStep = 3" />
        </div>
      </div>

      <!-- CONTENU ÉTAPE 3 : BILAN -->
      <div v-show="activeStep === 3" class="flex flex-col gap-6">
        <!-- Heures réalisées -->
        <div class="card p-5 rounded-2xl border border-surface-200 dark:border-surface-700">
          <h3 class="text-base font-bold mb-4 flex items-center gap-2">
            <i class="pi pi-chart-pie text-primary" /> Volumes horaires réellement réalisés
          </h3>
          <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div>
              <label class="block text-xs font-semibold text-muted-color mb-1">CM réalisés (heures)</label>
              <InputNumber v-model="form.cmRealise" :min="0" :max="200" :minFractionDigits="0" :maxFractionDigits="1" class="w-full" placeholder="0" />
            </div>
            <div>
              <label class="block text-xs font-semibold text-muted-color mb-1">TD réalisés (heures)</label>
              <InputNumber v-model="form.tdRealise" :min="0" :max="200" :minFractionDigits="0" :maxFractionDigits="1" class="w-full" placeholder="0" />
            </div>
            <div>
              <label class="block text-xs font-semibold text-muted-color mb-1">TP réalisés (heures)</label>
              <InputNumber v-model="form.tpRealise" :min="0" :max="200" :minFractionDigits="0" :maxFractionDigits="1" class="w-full" placeholder="0" />
            </div>
            <div>
              <label class="block text-xs font-semibold text-muted-color mb-1">Projet réalisé (heures)</label>
              <InputNumber v-model="form.projetRealise" :min="0" :max="200" :minFractionDigits="0" :maxFractionDigits="1" class="w-full" placeholder="0" />
            </div>
          </div>
        </div>

        <!-- Bilan qualitatif -->
        <div class="card p-5 rounded-2xl border border-surface-200 dark:border-surface-700 flex flex-col gap-6">
          <h3 class="text-base font-bold flex items-center gap-2">
            <i class="pi pi-check-circle text-primary" /> Bilan pédagogique et qualitatif
          </h3>

          <!-- Plan suivi -->
          <div class="p-4 rounded-xl bg-surface-50 dark:bg-surface-800/50 border border-surface-200 dark:border-surface-700 flex flex-col gap-3">
            <div class="flex items-center justify-between">
              <span class="font-semibold text-sm">Le plan de cours a-t-il été suivi comme prévu ?</span>
              <SelectButton
                v-model="form.planSuivi"
                :options="[{ label: 'Oui', value: true }, { label: 'Non', value: false }]"
                optionLabel="label"
                optionValue="value"
              />
            </div>
            <Textarea v-model="form.planSuiviCommentaire" rows="2" class="w-full text-sm" placeholder="Commentaire libre sur le suivi du plan de cours..." />
          </div>

          <!-- Objectifs atteints -->
          <div class="p-4 rounded-xl bg-surface-50 dark:bg-surface-800/50 border border-surface-200 dark:border-surface-700 flex flex-col gap-3">
            <div class="flex items-center justify-between">
              <span class="font-semibold text-sm">Les objectifs pédagogiques ont-ils été atteints ?</span>
              <SelectButton
                v-model="form.objectifsAtteints"
                :options="[{ label: 'Oui', value: true }, { label: 'Non', value: false }]"
                optionLabel="label"
                optionValue="value"
              />
            </div>
            <Textarea v-model="form.objectifsAtteintsCommentaire" rows="2" class="w-full text-sm" placeholder="Commentaire libre sur les objectifs atteints..." />
          </div>

          <!-- Compétences acquises -->
          <div class="p-4 rounded-xl bg-surface-50 dark:bg-surface-800/50 border border-surface-200 dark:border-surface-700 flex flex-col gap-3">
            <div class="flex items-center justify-between">
              <span class="font-semibold text-sm">Les compétences ciblées sont-elles acquises ?</span>
              <SelectButton
                v-model="form.competencesAcquises"
                :options="[{ label: 'Oui', value: true }, { label: 'Non', value: false }]"
                optionLabel="label"
                optionValue="value"
              />
            </div>
            <Textarea v-model="form.competencesAcquisesCommentaire" rows="2" class="w-full text-sm" placeholder="Commentaire libre sur les compétences acquises..." />
          </div>

          <div>
            <label class="block text-xs font-semibold text-muted-color mb-1">Commentaire sur les modalités d'évaluation réelles</label>
            <Textarea v-model="form.modeEvaluationCommentaire" rows="2" class="w-full" placeholder="Commentaires sur les devoirs surveillés, projets, coefficients réels..." />
          </div>

          <div>
            <label class="block text-xs font-semibold text-muted-color mb-1">Commentaires libres (Étape 3)</label>
            <Textarea v-model="form.commentairesStep3" rows="2" class="w-full" placeholder="Conclusion générale ou pistes d'amélioration..." />
          </div>
        </div>

        <div class="flex justify-between items-center">
          <Button label="Étape précédente" icon="pi pi-arrow-left" severity="secondary" @click="activeStep = 2" />
          <div class="flex gap-2">
            <Button
              label="Enregistrer le brouillon"
              icon="pi pi-save"
              severity="secondary"
              :loading="isSaving"
              @click="savePlan('EN_COURS', false)"
            />
            <Button
              label="Valider et transmettre"
              icon="pi pi-check"
              severity="success"
              :loading="isSaving"
              @click="savePlan('COMPLET', true)"
            />
          </div>
        </div>
      </div>
    </div>

    <!-- Dialog d'aperçu -->
    <PlanCoursShowDialog
      v-model:visible="showPreviewDialog"
      :planCours="planCours"
      :enseignement="enseignement"
      @edit="showPreviewDialog = false"
    />
  </div>
</template>
