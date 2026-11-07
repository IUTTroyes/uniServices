<script setup>
import { computed } from 'vue';
import ChampAttribut from './ChampAttribut.vue';
import { TYPES } from '../../blocs/schema';

/** Réglages d'un bloc : un champ par attribut déclaré dans le schéma. */
const props = defineProps({
  bloc: { type: Object, required: true },
  donnees: { type: Object, default: null },
});
const emit = defineEmits(['update:attrs']);

const attributs = computed(() => Object.entries(TYPES[props.bloc.type]?.attrs ?? {}));
const maj = (nom, valeur) => emit('update:attrs', { ...props.bloc.attrs, [nom]: valeur });
</script>

<template>
  <div v-if="attributs.length" class="grid gap-3 sm:grid-cols-2 p-3 rounded-lg bg-surface-50 border border-surface-200">
    <div v-for="[nom, def] in attributs" :key="nom" :class="['markdown', 'liste_texte', 'liste_media', 'media'].includes(def.type) ? 'sm:col-span-2' : ''">
      <ChampAttribut :id="`champ-${bloc.id}-${nom}`" :nom="nom" :def="def" :model-value="bloc.attrs[nom]" :donnees="donnees" @update:model-value="(v) => maj(nom, v)" />
    </div>
  </div>
</template>
