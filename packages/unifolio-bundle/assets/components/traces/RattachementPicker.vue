<script setup>
import { computed, ref, watch } from 'vue';
import Checkbox from 'primevue/checkbox';
import Textarea from 'primevue/textarea';
import Tag from 'primevue/tag';
import { couleurCompetence, texteSurCompetence } from './options';

/**
 * Sélection des éléments du référentiel de l'année (compétence, niveau ou AC).
 * v-model : [{ type, id, argumentaire }]
 */
const props = defineProps({
  competences: { type: Array, required: true },
  modelValue: { type: Array, required: true },
  disabled: { type: Boolean, default: false },
  /** Niveau imposé par la formation ('competence' | 'niveau' | 'ac') ; null = tous. */
  granularite: { type: String, default: null },
});

const permis = (type) => !props.granularite || props.granularite === type;
const LIBELLES = { competence: 'la compétence', niveau: 'le niveau de compétence', ac: 'les apprentissages critiques' };
const emit = defineEmits(['update:modelValue']);

const cle = (type, id) => `${type}:${id}`;
const selection = ref(new Map());

watch(
  () => props.modelValue,
  (valeur) => {
    selection.value = new Map(valeur.map((r) => [cle(r.type, r.id), { ...r }]));
  },
  { immediate: true, deep: true },
);

const emettre = () => emit('update:modelValue', [...selection.value.values()]);

const estChoisi = (type, id) => selection.value.has(cle(type, id));

const basculer = (type, id, libelle) => {
  const k = cle(type, id);
  if (selection.value.has(k)) selection.value.delete(k);
  else selection.value.set(k, { type, id, argumentaire: '', libelle });
  emettre();
};

const majArgumentaire = (type, id, texte) => {
  const r = selection.value.get(cle(type, id));
  if (r) {
    r.argumentaire = texte;
    emettre();
  }
};

const nbChoisisDans = (competence) =>
  [...selection.value.values()].filter((r) =>
    (r.type === 'competence' && r.id === competence.id)
    || competence.niveaux.some((n) => (r.type === 'niveau' && r.id === n.id) || (r.type === 'ac' && n.apprentissagesCritiques.some((a) => a.id === r.id))),
  ).length;

// Compétences dépliées d'office (un BUT en compte au plus 6 par année), sinon celles déjà choisies.
const ouvertes = ref(new Set());
watch(
  () => props.competences,
  (competences) => {
    ouvertes.value = new Set(
      competences.length <= 6
        ? competences.map((c) => c.id)
        : competences.filter((c) => nbChoisisDans(c) > 0).map((c) => c.id),
    );
  },
  { immediate: true },
);
const basculerCompetence = (id) => {
  if (ouvertes.value.has(id)) ouvertes.value.delete(id);
  else ouvertes.value.add(id);
};
const vide = computed(() => props.competences.length === 0);
</script>

<template>
  <div class="flex flex-col gap-3">
    <p v-if="granularite && !vide" class="text-sm m-0 flex items-center gap-2">
      <i class="pi pi-info-circle text-primary" />
      Votre formation rattache les traces à {{ LIBELLES[granularite] }}.
    </p>
    <p v-if="vide" class="text-muted-color text-sm m-0">
      Aucun élément de référentiel n'est défini pour cette année de BUT. Contactez votre référent portfolio.
    </p>

    <div
      v-for="competence in competences"
      :key="competence.id"
      class="rounded-lg overflow-hidden border border-l-[6px]"
      :style="{ borderColor: couleurCompetence(competence.couleur, 0.35), borderLeftColor: couleurCompetence(competence.couleur) }"
    >
      <button
        type="button"
        class="w-full flex items-center gap-3 p-3 text-left cursor-pointer border-0"
        :style="{ background: couleurCompetence(competence.couleur, 0.12) }"
        :aria-expanded="ouvertes.has(competence.id)"
        @click="basculerCompetence(competence.id)"
      >
        <span
          class="text-xs font-semibold px-2 py-1 rounded shrink-0"
          :style="{ background: couleurCompetence(competence.couleur), color: texteSurCompetence(competence.couleur) }"
        >{{ competence.nomCourt || 'Compétence' }}</span>
        <span class="flex-1 font-medium text-sm">{{ competence.libelle }}</span>
        <Tag v-if="nbChoisisDans(competence)" :value="`${nbChoisisDans(competence)} choisi(s)`" severity="success" />
        <i :class="ouvertes.has(competence.id) ? 'pi pi-chevron-up' : 'pi pi-chevron-down'" />
      </button>

      <div v-if="ouvertes.has(competence.id)" class="p-3 flex flex-col gap-3">
        <div
          v-for="niveau in competence.niveaux"
          :key="niveau.id"
          class="flex flex-col gap-2 pl-3 border-l-2"
          :style="{ borderLeftColor: couleurCompetence(competence.couleur, 0.5) }"
        >
          <label v-if="permis('niveau')" class="flex items-start gap-2 cursor-pointer">
            <Checkbox :model-value="estChoisi('niveau', niveau.id)" binary :disabled="disabled" @update:model-value="basculer('niveau', niveau.id, niveau.libelle)" />
            <span><strong>Niveau {{ niveau.ordre }}</strong> — {{ niveau.libelle }}</span>
          </label>
          <span v-else class="text-sm"><strong>Niveau {{ niveau.ordre }}</strong> — {{ niveau.libelle }}</span>
          <Textarea
            v-if="estChoisi('niveau', niveau.id)"
            :model-value="selection.get(cle('niveau', niveau.id))?.argumentaire"
            rows="2"
            auto-resize
            placeholder="En quoi cette trace démontre ce niveau ?"
            :disabled="disabled"
            @update:model-value="(t) => majArgumentaire('niveau', niveau.id, t)"
          />

          <div v-for="ac in niveau.apprentissagesCritiques" :key="ac.id" class="flex flex-col gap-2 pl-6">
            <label v-if="permis('ac')" class="flex items-start gap-2 cursor-pointer">
              <Checkbox :model-value="estChoisi('ac', ac.id)" binary :disabled="disabled" @update:model-value="basculer('ac', ac.id, ac.libelle)" />
              <span>
                <span
                  class="text-xs font-semibold px-1.5 py-0.5 rounded mr-1"
                  :style="{ background: couleurCompetence(competence.couleur, 0.15), color: couleurCompetence(competence.couleur) }"
                >{{ ac.code }}</span>
                {{ ac.libelle }}
              </span>
            </label>
            <span v-else class="text-sm text-muted-color">
              <span
                class="text-xs font-semibold px-1.5 py-0.5 rounded mr-1"
                :style="{ background: couleurCompetence(competence.couleur, 0.15), color: couleurCompetence(competence.couleur) }"
              >{{ ac.code }}</span>
              {{ ac.libelle }}
            </span>
            <Textarea
              v-if="estChoisi('ac', ac.id)"
              :model-value="selection.get(cle('ac', ac.id))?.argumentaire"
              rows="2"
              auto-resize
              placeholder="En quoi cette trace démontre cet apprentissage critique ?"
              :disabled="disabled"
              @update:model-value="(t) => majArgumentaire('ac', ac.id, t)"
            />
          </div>
        </div>

        <label v-if="permis('competence')" class="flex items-start gap-2 cursor-pointer font-medium">
          <Checkbox :model-value="estChoisi('competence', competence.id)" binary :disabled="disabled" @update:model-value="basculer('competence', competence.id, competence.libelle)" />
          <span>Rattacher à la compétence dans son ensemble</span>
        </label>
        <Textarea
          v-if="estChoisi('competence', competence.id)"
          :model-value="selection.get(cle('competence', competence.id))?.argumentaire"
          rows="2"
          auto-resize
          placeholder="En quoi cette trace démontre cette compétence ?"
          :disabled="disabled"
          @update:model-value="(t) => majArgumentaire('competence', competence.id, t)"
        />
      </div>
    </div>
  </div>
</template>
