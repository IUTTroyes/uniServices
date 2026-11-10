<script setup>
import { computed, ref } from 'vue';
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import Textarea from 'primevue/textarea';
import Select from 'primevue/select';
import ToggleSwitch from 'primevue/toggleswitch';
import Button from 'primevue/button';
import MediaPicker from './MediaPicker.vue';
import { resoudreSource } from '../../blocs/donnees';

/**
 * Un champ de l'inspecteur, construit d'après la définition de l'attribut dans blocs.json.
 * Les valeurs saisies ne sont jamais interprétées comme du HTML (cf. markdown.js et BlocValidator).
 */
const props = defineProps({
  nom: { type: String, required: true },
  def: { type: Object, required: true },
  modelValue: { default: null },
  donnees: { type: Object, default: null },
  id: { type: String, required: true },
});
const emit = defineEmits(['update:modelValue']);

const maj = (v) => emit('update:modelValue', v);

// ---- énumérations (valeurs = clé → libellé)
const options = computed(() => Object.entries(props.def.valeurs ?? {}).map(([value, label]) => ({ value, label })));

// ---- markdown : barre d'outils
const zone = ref(null);
const enrober = (avant, apres = avant, defaut = 'texte') => {
  const el = zone.value?.$el;
  const texte = String(props.modelValue ?? '');
  const debut = el?.selectionStart ?? texte.length;
  const fin = el?.selectionEnd ?? texte.length;
  const choix = texte.slice(debut, fin) || defaut;
  maj(`${texte.slice(0, debut)}${avant}${choix}${apres}${texte.slice(fin)}`);
};
const prefixer = (prefixe) => {
  const texte = String(props.modelValue ?? '');
  maj(texte === '' ? `${prefixe}élément` : `${texte}\n${prefixe}élément`);
};

// ---- listes de textes
const elements = computed(() => (Array.isArray(props.modelValue) ? props.modelValue : []));
const majElement = (i, v) => maj(elements.value.map((e, j) => (j === i ? v : e)));
const ajouterElement = () => elements.value.length < (props.def.max_items ?? 50) && maj([...elements.value, '']);
const retirerElement = (i) => maj(elements.value.filter((_, j) => j !== i));

// ---- médias
const selecteur = ref(false);
const indexRemplace = ref(null);
const aperçu = (source) => resoudreSource(props.donnees, source);
const choisir = (source) => {
  if (props.def.type === 'liste_media') {
    const liste = Array.isArray(props.modelValue) ? [...props.modelValue] : [];
    if (indexRemplace.value !== null) liste[indexRemplace.value] = source; else liste.push(source);
    maj(liste);
  } else {
    maj(source);
  }
  indexRemplace.value = null;
};
const ouvrirSelecteur = (i = null) => { indexRemplace.value = i; selecteur.value = true; };

// ---- traces et compétences
const traces = computed(() => (props.donnees?.traces ?? []).map((t) => ({ value: t.uuid, label: `${t.titre} (${t.statut.libelle})` })));
const competences = computed(() => (props.donnees?.progression ?? []).map((p) => ({ value: p.competence.id, label: p.competence.nomCourt })));
</script>

<template>
  <div class="flex flex-col gap-1">
    <label :for="id" class="text-sm font-medium">{{ def.libelle }}<span v-if="def.requis" class="text-red-600" aria-label="obligatoire"> *</span></label>

    <InputText v-if="def.type === 'texte'" :id="id" :model-value="modelValue ?? ''" :maxlength="def.max" size="small" @update:model-value="maj" />

    <template v-else-if="def.type === 'markdown'">
      <div class="flex flex-wrap gap-1" role="toolbar" aria-label="Mise en forme">
        <Button icon="pi pi-bold" size="small" text severity="secondary" aria-label="Gras" @click="enrober('**')" />
        <Button icon="pi pi-italic" size="small" text severity="secondary" aria-label="Italique" @click="enrober('*')" />
        <Button icon="pi pi-link" size="small" text severity="secondary" aria-label="Lien" @click="enrober('[', '](https://)', 'texte du lien')" />
        <Button icon="pi pi-list" size="small" text severity="secondary" aria-label="Liste à puces" @click="prefixer('- ')" />
        <Button icon="pi pi-code" size="small" text severity="secondary" aria-label="Code" @click="enrober('`')" />
      </div>
      <Textarea :id="id" ref="zone" :model-value="modelValue ?? ''" :maxlength="def.max" rows="5" auto-resize @update:model-value="maj" />
      <small class="text-muted-color">{{ String(modelValue ?? '').length }} / {{ def.max }} · **gras**, *italique*, [lien](https://…), « - » pour une liste</small>
    </template>

    <InputNumber v-else-if="def.type === 'entier'" :input-id="id" :model-value="modelValue" :min="def.min" :max="def.max" show-buttons size="small" @update:model-value="maj" />

    <Select v-else-if="def.type === 'enum'" :input-id="id" :model-value="String(modelValue ?? '')" :options="options" option-label="label" option-value="value" size="small" @update:model-value="maj" />

    <label v-else-if="def.type === 'bool'" class="flex items-center gap-2 cursor-pointer text-sm"><ToggleSwitch :input-id="id" :model-value="!!modelValue" @update:model-value="maj" /> {{ modelValue ? 'Oui' : 'Non' }}</label>

    <InputText v-else-if="def.type === 'url'" :id="id" :model-value="modelValue ?? ''" placeholder="https://…" size="small" type="url" @update:model-value="maj" />

    <div v-else-if="def.type === 'liste_texte'" class="flex flex-col gap-1">
      <div v-for="(e, i) in elements" :key="i" class="flex gap-1">
        <InputText :model-value="e" :maxlength="def.max" size="small" class="flex-1" :aria-label="`${def.libelle} ${i + 1}`" @update:model-value="(v) => majElement(i, v)" />
        <Button icon="pi pi-times" size="small" text severity="danger" :aria-label="`Retirer l'élément ${i + 1}`" @click="retirerElement(i)" />
      </div>
      <Button label="Ajouter" icon="pi pi-plus" size="small" text class="self-start" :disabled="elements.length >= (def.max_items ?? 50)" @click="ajouterElement" />
    </div>

    <div v-else-if="def.type === 'media'" class="flex items-center gap-2">
      <img v-if="aperçu(modelValue).src && aperçu(modelValue).type !== 'fichier' && aperçu(modelValue).type !== 'pdf'" :src="aperçu(modelValue).src" alt="" class="h-12 w-12 object-cover rounded" />
      <span class="text-sm flex-1 truncate">{{ modelValue ? (aperçu(modelValue).nom ?? modelValue.url ?? 'Média choisi') : 'Aucun média' }}</span>
      <Button :label="modelValue ? 'Changer' : 'Choisir'" icon="pi pi-image" size="small" outlined @click="ouvrirSelecteur()" />
    </div>

    <div v-else-if="def.type === 'liste_media'" class="flex flex-col gap-2">
      <div class="flex flex-wrap gap-2">
        <div v-for="(m, i) in elements" :key="i" class="relative">
          <img v-if="aperçu(m).src" :src="aperçu(m).src" alt="" class="h-16 w-16 object-cover rounded" />
          <Button icon="pi pi-times" size="small" rounded severity="danger" class="!absolute -top-2 -right-2 !w-6 !h-6" :aria-label="`Retirer l'image ${i + 1}`" @click="retirerElement(i)" />
        </div>
      </div>
      <Button label="Ajouter une image" icon="pi pi-plus" size="small" outlined class="self-start" :disabled="elements.length >= (def.max_items ?? 24)" @click="ouvrirSelecteur()" />
    </div>

    <Select v-else-if="def.type === 'trace'" :input-id="id" :model-value="modelValue" :options="traces" option-label="label" option-value="value" filter placeholder="Choisir une trace" size="small" :disabled="!donnees" @update:model-value="maj" />

    <template v-else-if="def.type === 'competence'">
      <Select :input-id="id" :model-value="modelValue" :options="competences" option-label="label" option-value="value" show-clear placeholder="Toutes les compétences" size="small" :disabled="!competences.length" @update:model-value="(v) => maj(v ?? null)" />
      <small v-if="!competences.length" class="text-muted-color">Disponible dans le portfolio de l'étudiant.</small>
    </template>

    <MediaPicker v-model:visible="selecteur" :donnees="donnees" :filtre="def.filtre ?? 'image'" @choisir="choisir" />
  </div>
</template>
