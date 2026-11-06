<script setup>
import {useRouter} from 'vue-router';
import {useEnseignementPage} from './useEnseignementPage';
import {ref, computed, watch} from 'vue';
import {getSemestreEnseignementPreviService} from '@requests';
import {useUsersStore} from '@stores';
import {ListSkeleton} from '@components';
import PrevisionnelTable from '@/components/Previsionnel/PrevisionnelTable.vue';
const props = defineProps({ visible: Boolean, enseignement: {type: Object, default: null}, anneeUniversitaire: {type: [Number, String], default: null} });
const usersStore = useUsersStore();
const router = useRouter();
const emit = defineEmits(['update:visible']);
const loading = ref(false);
const error = ref(false);
const data = ref(null);
const columns = ref([
  { header: 'Intervenant', field: 'personnel.display', sortable: true, colspan: 1 },
  { header: 'Nb H/Gr.', field: 'heures.CM.NbHrGrp', colspan: 1, class: '!bg-purple-400/20 !text-nowrap', unit: ' h'},
  { header: 'Nb Gr.', field: 'heures.CM.NbGrp', colspan: 1, class: '!bg-purple-400/20 !text-nowrap'},
  { header: 'Nb Seance/Gr.', field: 'heures.CM.NbSeanceGrp', colspan: 1, class: '!bg-purple-400/20 !text-nowrap'},
  { header: 'Nb H/Gr.', field: 'heures.TD.NbHrGrp', colspan: 1, class: '!bg-green-400/20 !text-nowrap', unit: ' h'},
  { header: 'Nb Gr.', field: 'heures.TD.NbGrp', colspan: 1, class: '!bg-green-400/20 !text-nowrap'},
  { header: 'Nb Seance/Gr.', field: 'heures.TD.NbSeanceGrp', colspan: 1, class: '!bg-green-400/20 !text-nowrap'},
  { header: 'Nb H/Gr.', field: 'heures.TP.NbHrGrp', colspan: 1, class: '!bg-amber-400/20 !text-nowrap', unit: ' h'},
  { header: 'Nb Gr.', field: 'heures.TP.NbGrp', colspan: 1, class: '!bg-amber-400/20 !text-nowrap'},
  { header: 'Nb Seance/Gr.', field: 'heures.TP.NbSeanceGrp', colspan: 1, class: '!bg-amber-400/20 !text-nowrap'},
]);

const topHeaderCols = ref([
  { header: 'CM', colspan: 3, class: '!bg-purple-400/20' },
  { header: 'TD', colspan: 3, class: '!bg-green-400/20' },
  { header: 'TP', colspan: 3, class: '!bg-amber-400/20' }
]);

const additionalRows = computed(() => []);

const footerCols = computed(() => [
]);
watch(() => [props.visible, props.enseignement?.id, props.enseignement?.semestre?.id, props.anneeUniversitaire, usersStore.userId], async () => {

  data.value = null;
  error.value = false;
  loading.value = false;
  if (!props.visible || !props.enseignement?.id || !props.anneeUniversitaire || !usersStore.userId) return;
  loading.value = true;
  try {
    const semestreId = props.enseignement.semestre?.id;
    if (!semestreId) throw new Error('Semestre manquant');
    const result = await getSemestreEnseignementPreviService(semestreId, props.enseignement.id, props.anneeUniversitaire, false, usersStore.userId);
    data.value = result;
  } catch (err) {
    error.value = true;
    console.error(err);
  } finally {
    loading.value = false;
  }
}, {immediate: true});
const {destination, accessible} = useEnseignementPage('previsionnel', () => props.enseignement);
</script>
<template>
  <Dialog :visible="visible" @update:visible="emit('update:visible', $event)" modal
    :header="`Prévisionnel · ${enseignement?.codeEnseignement || ''} - ${enseignement?.libelle || ''}`" class="w-[90vw] md:w-[85vw]">
    <ListSkeleton v-if="loading" />
    <Message v-else-if="error" severity="error">Impossible de récupérer le prévisionnel.</Message>
    <PrevisionnelTable v-else-if="data?.[0]?.length" origin="previMatiereSynthese" :columns="columns" :topHeaderCols="topHeaderCols" :additionalRows="additionalRows" :footerCols="footerCols" :data="data[0]" :filters="{}" size="small" :headerTitle="enseignement?.libelle" :headerTitlecolspan="1" />
    <Message v-else severity="warn">Aucun prévisionnel trouvé pour vous dans cet enseignement et cette année universitaire.</Message>
    <template #footer>
      <div class="w-full border-t border-surface-200 dark:border-surface-700 bg-surface-50 dark:bg-surface-800/50 rounded-xl p-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-center gap-3 text-left">
          <span class="flex items-center justify-center shrink-0 w-10 h-10 rounded-lg bg-primary/10 text-primary" aria-hidden="true"><i class="pi pi-calendar" /></span>
          <div>
            <p class="m-0! text-sm font-semibold text-color">Mon service prévisionnel</p>
            <p class="m-0! mt-1! text-xs text-muted-color">Retrouvez le détail de vos heures d’enseignement.</p>
          </div>
        </div>
        <Button v-if="accessible" label="Voir mon prévisionnel" icon="pi pi-arrow-right" icon-pos="right" class="w-full sm:w-auto shrink-0" severity="primary" @click="router.push(destination)" />
      </div>
    </template>
  </Dialog>
</template>
