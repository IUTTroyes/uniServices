<script setup>
import { ref } from 'vue';
import InputText from 'primevue/inputtext';

/** Saisie d'une liste de mots-clés : Entrée (ou virgule) ajoute, la croix retire. */
const props = defineProps({
  modelValue: { type: Array, default: () => [] },
  id: { type: String, required: true },
  max: { type: Number, default: 30 },
  placeholder: { type: String, default: 'Ajouter…' },
});
const emit = defineEmits(['update:modelValue']);

const saisie = ref('');
const ajouter = () => {
  const v = saisie.value.replace(/,$/, '').trim();
  saisie.value = '';
  if (!v || props.modelValue.length >= props.max || props.modelValue.some((x) => x.toLowerCase() === v.toLowerCase())) return;
  emit('update:modelValue', [...props.modelValue, v.slice(0, 60)]);
};
const retirer = (i) => emit('update:modelValue', props.modelValue.filter((_, j) => j !== i));
</script>

<template>
  <div class="flex flex-col gap-2">
    <div class="flex flex-wrap gap-1" role="list">
      <span v-for="(t, i) in modelValue" :key="t" role="listitem" class="inline-flex items-center gap-1 text-sm px-2 py-0.5 rounded-full bg-surface-100">
        {{ t }}
        <button type="button" class="bg-transparent border-0 cursor-pointer p-0 text-muted-color" :aria-label="`Retirer ${t}`" @click="retirer(i)"><i class="pi pi-times text-xs" /></button>
      </span>
    </div>
    <InputText :id="id" v-model="saisie" :placeholder="placeholder" size="small" :disabled="modelValue.length >= max" @keydown.enter.prevent="ajouter" @keydown="(e) => e.key === ',' && (e.preventDefault(), ajouter())" @blur="ajouter" />
  </div>
</template>
