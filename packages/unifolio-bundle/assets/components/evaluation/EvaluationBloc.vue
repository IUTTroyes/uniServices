<script setup>
import { computed, ref } from 'vue';
import Button from 'primevue/button';
import Textarea from 'primevue/textarea';
import Tag from 'primevue/tag';
import GrilleSaisie from '../autoeval/GrilleSaisie.vue';
import ComparaisonAutoEval from './ComparaisonAutoEval.vue';
import { pourcentage } from '../../requests/autoEvaluationService';
import { couleurCompetence, texteSurCompetence } from '../traces/options';

/**
 * Un point d'une évaluation en lot (un AC, un niveau, une compétence…) : en-tête repliable,
 * critères de la grille, rappel de l'auto-évaluation et commentaire facultatif.
 */
const props = defineProps({
  item: { type: Object, required: true },
  grille: { type: Object, required: true },
  /** { reponses: { [critereId]: réponse }, appreciation: string, dirty: bool } */
  saisie: { type: Object, required: true },
  modifiable: { type: Boolean, default: true },
  ouvert: { type: Boolean, default: true },
  declarationIa: { type: Object, default: null },
  /** Le point est rendu et peut être rouvert pour modification. */
  peutRouvrir: { type: Boolean, default: false },
});
const emit = defineEmits(['update:saisie', 'basculer', 'rouvrir']);

const detailAuto = ref(false);
const commentaireOuvert = ref(!!props.saisie.appreciation);

const evaluation = computed(() => props.item.evaluation);
const auto = computed(() => props.item.autoEvaluation);
const aCritereTexte = computed(() => props.grille.criteres.some((c) => c.type === 'texte'));

const renseigne = computed(() => Object.values(props.saisie.reponses).some((r) =>
  (r.echelon ?? null) !== null || (r.valeur ?? null) !== null || (r.booleen ?? null) !== null || String(r.texte ?? '').trim() !== '')
  || props.saisie.appreciation.trim() !== '');

const etat = computed(() => {
  const e = evaluation.value;
  if (!e) return renseigne.value ? { libelle: 'À enregistrer', badge: 'warn' } : { libelle: 'Non évalué', badge: 'secondary' };
  if (e.statut.value === 'en_cours') return { libelle: renseigne.value ? 'En cours' : 'Ouvert', badge: 'warn' };
  return { libelle: e.statut.libelle, badge: e.statut.badge };
});

const majReponses = (reponses) => emit('update:saisie', { ...props.saisie, reponses, dirty: true });
const majAppreciation = (appreciation) => emit('update:saisie', { ...props.saisie, appreciation, dirty: true });
</script>

<template>
  <article class="rounded-lg border border-surface-200 overflow-hidden" :class="{ 'opacity-90': !modifiable }">
    <button
      type="button"
      class="w-full flex flex-wrap items-center gap-2 p-3 text-left cursor-pointer border-0"
      :style="{ background: couleurCompetence(item.competence?.couleur, 0.1) }"
      :aria-expanded="ouvert"
      @click="emit('basculer')"
    >
      <i :class="ouvert ? 'pi pi-chevron-down' : 'pi pi-chevron-right'" class="text-muted-color" />
      <span
        v-if="item.cible.code"
        class="text-xs font-semibold px-1.5 py-0.5 rounded"
        :style="{ background: couleurCompetence(item.competence?.couleur, 0.2), color: couleurCompetence(item.competence?.couleur) }"
      >{{ item.cible.code }}</span>
      <strong v-else-if="item.cible.ordre" class="text-sm">Niveau {{ item.cible.ordre }}</strong>
      <span class="flex-1 min-w-48 text-sm" :class="{ 'line-clamp-1': !ouvert }">
        {{ item.cible.type === 'competence' ? (item.competence?.libelle ?? item.cible.libelle) : item.cible.libelle }}
      </span>
      <Tag v-if="auto && pourcentage(auto.score) !== null" :value="`Auto ${pourcentage(auto.score)} %`" severity="info" icon="pi pi-user" />
      <Tag v-if="evaluation && pourcentage(evaluation.score) !== null" :value="`${pourcentage(evaluation.score)} %`" severity="success" />
      <Tag :value="etat.libelle" :severity="etat.badge" />
      <Tag v-if="saisie.dirty" value="Modifié" severity="warn" icon="pi pi-pencil" />
    </button>

    <div v-show="ouvert" class="p-3 flex flex-col gap-4">
      <p v-if="item.argumentaire" class="m-0 text-sm text-muted-color italic whitespace-pre-line">« {{ item.argumentaire }} »</p>

      <div v-if="peutRouvrir" class="flex items-center gap-2 text-sm">
        <i class="pi pi-lock text-muted-color" /> Ce point est rendu.
        <Button label="Rouvrir" icon="pi pi-undo" size="small" text @click="emit('rouvrir')" />
      </div>

      <GrilleSaisie
        :grille="grille"
        :model-value="saisie.reponses"
        :disabled="!modifiable"
        :declaration-ia="declarationIa"
        :reference="auto?.reponses ?? []"
        compact
        @update:model-value="majReponses"
      />

      <div v-if="!aCritereTexte" class="flex flex-col gap-2">
        <Button
          v-if="!commentaireOuvert && !saisie.appreciation"
          label="Ajouter un commentaire"
          icon="pi pi-comment"
          size="small"
          text
          class="self-start !px-0"
          :disabled="!modifiable"
          @click="commentaireOuvert = true"
        />
        <template v-else>
          <label class="text-sm font-medium" :for="`comm-${item.cle}`">Commentaire <span class="text-muted-color font-normal">(facultatif)</span></label>
          <Textarea
            :id="`comm-${item.cle}`"
            :model-value="saisie.appreciation"
            rows="2"
            auto-resize
            :disabled="!modifiable"
            placeholder="Points forts, axes de progrès…"
            @update:model-value="majAppreciation"
          />
        </template>
      </div>

      <div v-if="auto" class="flex flex-col gap-2">
        <Button
          :label="detailAuto ? 'Masquer l\'auto-évaluation' : 'Voir l\'auto-évaluation de l\'étudiant'"
          :icon="detailAuto ? 'pi pi-eye-slash' : 'pi pi-user'"
          size="small"
          text
          class="self-start !px-0"
          @click="detailAuto = !detailAuto"
        />
        <ComparaisonAutoEval
          v-if="detailAuto"
          :auto="auto"
          :reponses-enseignant="evaluation?.reponsesDetaillees ?? []"
          :score-enseignant="evaluation?.score ?? null"
        />
      </div>
      <p v-else class="m-0 text-xs text-muted-color">Pas d'auto-évaluation soumise sur ce point.</p>

      <div v-if="item.autres.length" class="text-xs text-muted-color flex flex-wrap gap-x-4 gap-y-1">
        <span v-for="a in item.autres" :key="a.uuid"><i class="pi pi-user" /> {{ a.evaluateur.nom }} : {{ pourcentage(a.score) ?? '—' }} %</span>
      </div>
    </div>
  </article>
</template>
