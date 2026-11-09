<script setup>
import { computed, ref, watch } from 'vue';
import Dialog from 'primevue/dialog';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import { mediasProposes } from '../../blocs/donnees';

/** Choix d'un média : une preuve déposée dans une trace, ou une adresse externe (https). */
const props = defineProps({
  visible: { type: Boolean, default: false },
  donnees: { type: Object, default: null },
  filtre: { type: String, default: 'image' },
});
const emit = defineEmits(['update:visible', 'choisir']);

const adresse = ref('');
const proposes = computed(() => mediasProposes(props.donnees, props.filtre));
watch(() => props.visible, (v) => { if (v) adresse.value = ''; });

const choisirMedia = (m) => { emit('choisir', { trace: m.trace, media: m.media }); emit('update:visible', false); };
const choisirAdresse = () => {
  if (!/^https?:\/\//i.test(adresse.value.trim())) return;
  emit('choisir', { url: adresse.value.trim() });
  emit('update:visible', false);
};
</script>

<template>
  <Dialog :visible="visible" header="Choisir un média" modal :style="{ width: '44rem' }" :breakpoints="{ '768px': '95vw' }" @update:visible="(v) => emit('update:visible', v)">
    <div class="flex flex-col gap-4">
      <section class="flex flex-col gap-2">
        <h4 class="m-0 text-sm font-semibold">Mes preuves</h4>
        <p v-if="!proposes.length" class="m-0 text-sm text-muted-color">
          {{ donnees ? 'Aucune preuve de ce type dans vos traces : déposez-en une dans « Mes traces ».' : 'Les preuves des étudiants ne sont pas disponibles dans un modèle : utilisez une adresse externe.' }}
        </p>
        <div v-else class="grid gap-2 grid-cols-2 sm:grid-cols-3 max-h-80 overflow-auto">
          <button
            v-for="m in proposes"
            :key="`${m.trace}-${m.media}`"
            type="button"
            class="rounded-lg border border-surface-200 p-1 bg-transparent cursor-pointer text-left hover:border-emerald-400"
            @click="choisirMedia(m)"
          >
            <img v-if="m.type === 'image'" :src="m.src" :alt="m.nom" class="w-full h-24 object-cover rounded" loading="lazy" />
            <div v-else class="w-full h-24 flex items-center justify-center bg-surface-100 rounded"><i class="pi pi-file text-3xl text-muted-color" /></div>
            <div class="text-xs mt-1 truncate font-medium">{{ m.nom }}</div>
            <div class="text-xs text-muted-color truncate">{{ m.traceTitre }}</div>
          </button>
        </div>
      </section>

      <section class="flex flex-col gap-2">
        <h4 class="m-0 text-sm font-semibold">Ou une adresse externe</h4>
        <div class="flex gap-2">
          <InputText v-model="adresse" placeholder="https://…" class="flex-1" aria-label="Adresse du média" @keydown.enter.prevent="choisirAdresse" />
          <Button label="Utiliser" icon="pi pi-link" :disabled="!/^https?:\/\//i.test(adresse.trim())" @click="choisirAdresse" />
        </div>
      </section>
    </div>
  </Dialog>
</template>
