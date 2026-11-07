<script setup>
import { Alert } from '@components';
import { showSuccess } from '@helpers/toast';
import { ref, watch } from 'vue';
import { useRouter } from 'vue-router';
import Dialog from 'primevue/dialog';
import Button from 'primevue/button';
import Checkbox from 'primevue/checkbox';
import { portfolioService, messagesErreur } from '../../requests/portfolioService';

const props = defineProps({
  visible: { type: Boolean, default: false },
  portfolio: { type: Object, required: true },
});
const emit = defineEmits(['update:visible']);

const router = useRouter();
const choisies = ref([]);
const envoi = ref(false);
const erreurs = ref([]);

watch(() => props.visible, (v) => {
  if (v) {
    choisies.value = props.portfolio.pages.map((p) => p.uuid);
    erreurs.value = [];
  }
}, { immediate: true });

const copier = async () => {
  envoi.value = true;
  erreurs.value = [];
  try {
    const { data } = await portfolioService.copierVersVitrine(props.portfolio.uuid, choisies.value);
    showSuccess(undefined, `${data.copiees} page(s) copiée(s) dans votre vitrine`, 4000);
    emit('update:visible', false);
    router.push(`/unifolio/portfolios/${data.vitrine.uuid}`);
  } catch (e) {
    erreurs.value = messagesErreur(e, 'Copie impossible.');
  } finally {
    envoi.value = false;
  }
};
</script>

<template>
  <Dialog :visible="visible" header="Copier dans ma vitrine" modal :style="{ width: '30rem' }" :breakpoints="{ '640px': '95vw' }" @update:visible="emit('update:visible', $event)">
    <div class="flex flex-col gap-3">
      <p class="m-0 text-sm text-muted-color">
        Les pages choisies sont copiées dans votre portfolio vitrine, sans lien avec l'original : vous pourrez les modifier librement, votre portfolio académique reste intact.
      </p>
      <Alert icon-native v-if="erreurs.length" severity="error" :closable="false">
        <ul class="m-0 pl-4"><li v-for="e in erreurs" :key="e">{{ e }}</li></ul>
      </Alert>
      <label v-for="p in portfolio.pages" :key="p.uuid" class="flex items-center gap-2 cursor-pointer">
        <Checkbox v-model="choisies" :value="p.uuid" />
        <span>{{ p.titre }}</span>
      </label>
    </div>
    <template #footer>
      <Button label="Annuler" severity="secondary" outlined @click="emit('update:visible', false)" />
      <Button label="Copier" icon="pi pi-copy" :disabled="!choisies.length" :loading="envoi" @click="copier" />
    </template>
  </Dialog>
</template>
