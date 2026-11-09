<script setup>
import SelectButton from 'primevue/selectbutton';
import InputNumber from 'primevue/inputnumber';
import Textarea from 'primevue/textarea';
import Tag from 'primevue/tag';

/**
 * Saisie des réponses à une grille (auto-évaluation, puis évaluation au lot 4).
 * v-model : { [critereId]: { critere, echelon?, valeur?, booleen?, texte? } }
 */
const props = defineProps({
  grille: { type: Object, required: true },
  modelValue: { type: Object, required: true },
  disabled: { type: Boolean, default: false },
  /** Déclaration IA de la trace, affichée à côté du critère « Usage de l'IA ». */
  declarationIa: { type: Object, default: null },
  /** Réponses de l'auto-évaluation de l'étudiant (rapprochées par libellé de critère), affichées en rappel. */
  reference: { type: Array, default: () => [] },
  /** Présentation condensée pour afficher plusieurs grilles à la suite. */
  compact: { type: Boolean, default: false },
});

const cle = (libelle) => String(libelle ?? '').trim().toLowerCase();
const referenceDe = (critere) => props.reference.find((r) => cle(r.libelle) === cle(critere.libelle)) ?? null;
const emit = defineEmits(['update:modelValue']);

const reponse = (critere) => props.modelValue[critere.id] ?? { critere: critere.id };

const majReponse = (critere, champ, valeur) => {
  emit('update:modelValue', { ...props.modelValue, [critere.id]: { ...reponse(critere), [champ]: valeur } });
};

const OUI_NON = [{ label: 'Oui', value: true }, { label: 'Non', value: false }];
</script>

<template>
  <div :class="compact ? 'grid gap-x-6 gap-y-4 lg:grid-cols-2' : 'flex flex-col gap-5'">
    <div v-for="critere in grille.criteres" :key="critere.id" class="flex flex-col gap-2" :class="{ 'lg:col-span-2': compact && critere.type === 'texte' }">
      <div class="flex items-start gap-2">
        <Tag v-if="critere.source === 'declaration_ia'" value="IA" severity="contrast" icon="pi pi-sparkles" />
        <div class="flex-1">
          <div class="font-medium">
            {{ critere.libelle }}
            <span v-if="critere.obligatoire" class="text-red-600" aria-label="obligatoire">*</span>
            <span v-else-if="critere.type === 'texte'" class="text-muted-color text-sm font-normal">(facultatif)</span>
          </div>
          <p v-if="critere.description" class="text-sm text-muted-color m-0">{{ critere.description }}</p>
        </div>
      </div>

      <div v-if="critere.source === 'declaration_ia' && declarationIa" class="text-sm rounded-lg p-2 bg-violet-50 dark:bg-violet-950/30">
        Votre déclaration — conception : {{ declarationIa.conception }} · réalisation : {{ declarationIa.realisation }}
        <span v-if="declarationIa.detail"> · {{ declarationIa.detail }}</span>
      </div>

      <div v-if="critere.type === 'echelle'" class="flex flex-wrap gap-2" role="radiogroup" :aria-label="critere.libelle">
        <button
          v-for="e in grille.echelons"
          :key="e.id"
          type="button"
          role="radio"
          :aria-checked="reponse(critere).echelon === e.id"
          :disabled="disabled"
          :title="e.descripteur || e.libelle"
          class="px-3 py-2 rounded-lg border-2 text-sm font-medium transition-colors cursor-pointer disabled:cursor-default"
          :style="reponse(critere).echelon === e.id
            ? { background: e.couleur || 'var(--p-primary-color)', borderColor: e.couleur || 'var(--p-primary-color)', color: '#fff' }
            : { borderColor: e.couleur || 'var(--p-content-border-color)', color: 'inherit', background: 'transparent' }"
          @click="majReponse(critere, 'echelon', reponse(critere).echelon === e.id ? null : e.id)"
        >
          {{ e.libelle }}
        </button>
      </div>
      <p v-if="critere.type === 'echelle' && referenceDe(critere)?.echelon" class="text-xs text-muted-color m-0 flex items-center gap-1">
        <i class="pi pi-user" /> Auto-évaluation :
        <span class="px-1.5 py-0.5 rounded font-semibold text-white" :style="{ background: referenceDe(critere).echelon.couleur || 'var(--p-primary-color)' }">{{ referenceDe(critere).echelon.libelle }}</span>
      </p>
      <p
        v-if="critere.type === 'echelle' && reponse(critere).echelon"
        class="text-xs text-muted-color m-0"
      >{{ grille.echelons.find((e) => e.id === reponse(critere).echelon)?.descripteur }}</p>

      <SelectButton
        v-else-if="critere.type === 'booleen'"
        :model-value="reponse(critere).booleen"
        :options="OUI_NON"
        option-label="label"
        option-value="value"
        :disabled="disabled"
        @update:model-value="(v) => majReponse(critere, 'booleen', v)"
      />

      <div v-else-if="critere.type === 'note'" class="flex items-center gap-2">
        <InputNumber
          :model-value="reponse(critere).valeur"
          :min="0"
          :max="critere.noteMax"
          :max-fraction-digits="2"
          :disabled="disabled"
          input-class="w-24"
          @update:model-value="(v) => majReponse(critere, 'valeur', v)"
        />
        <span class="text-muted-color">/ {{ critere.noteMax }}</span>
      </div>

      <Textarea
        v-else-if="critere.type === 'texte'"
        :model-value="reponse(critere).texte ?? ''"
        :rows="compact ? 2 : 3"
        auto-resize
        :disabled="disabled"
        @update:model-value="(v) => majReponse(critere, 'texte', v)"
      />
    </div>
  </div>
</template>
