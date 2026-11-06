<script setup>
import {useRouter} from 'vue-router';
import {useEnseignementPage} from './useEnseignementPage';
import {computed, ref, watch} from 'vue';
import AbsencesPeriodesTable from '@/components/Absence/AbsencesPeriodesTable.vue';
import {getEtudiantAbsencesService} from '@requests';
import {ListSkeleton, ErrorView} from '@components';

const props = defineProps({
  visible: Boolean,
  enseignement: {type: Object, default: null},
  anneeUniversitaire: {type: [Number, String], default: null},
});
const router = useRouter();
const emit = defineEmits(['update:visible']);
const absences = ref([]);
const totalAbsences = ref(0);
const isLoadingAbsences = ref(false);
const hasError = ref(false);
const page = ref(0);
const rowOptions = [5, 10, 20, 50];
const limit = ref(5);
const offset = computed(() => page.value * limit.value);

const getAbsences = async () => {
  isLoadingAbsences.value = true;
  hasError.value = false;
  try {
    const params = {
      enseignement: props.enseignement?.id,
      anneeUniversitaire: props.anneeUniversitaire,
      itemsPerPage: limit.value,
      page: page.value + 1,
    };
    const response = await getEtudiantAbsencesService(params, '/administration');
    absences.value = Array.isArray(response) ? response : [];
    totalAbsences.value = response?.totalRecords ?? response?.totalItems ?? absences.value.length;
  } catch (error) {
    hasError.value = true;
    absences.value = [];
    console.error('Erreur lors de la récupération des absences :', error);
  } finally {
    isLoadingAbsences.value = false;
  }
};

const onPageChange = async event => {
  limit.value = event.rows;
  page.value = event.page;
  await getAbsences();
};

watch(() => props.visible, () => {
  absences.value = [];
  totalAbsences.value = 0;
  page.value = 0;
  if (props.visible && props.enseignement?.id) getAbsences();
}, {immediate: true});
const {destination, accessible} = useEnseignementPage('absences', () => props.enseignement);
</script>

<template>
  <Dialog :visible="visible" @update:visible="emit('update:visible', $event)" modal
          :header="`Absences · ${enseignement?.codeEnseignement || ''} - ${enseignement?.libelle || ''}`"
          class="w-[90vw] md:w-[85vw]">
    <ListSkeleton v-if="isLoadingAbsences" />
    <ErrorView v-else-if="hasError" />
    <Message v-else-if="!absences.length" severity="warn" icon="pi pi-exclamation-triangle" class="mt-4">Aucune absence trouvée.</Message>
    <div v-else>
      <AbsencesPeriodesTable :absences="absences" :total-absences="totalAbsences"
        :offset="offset" :limit="limit" :row-options="rowOptions" @page="onPageChange" />
    </div>
    <template #footer>
      <div class="w-full border-t border-surface-200 dark:border-surface-700 bg-surface-50 dark:bg-surface-800/50 rounded-xl p-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-center gap-3 text-left">
          <span class="flex items-center justify-center shrink-0 w-10 h-10 rounded-lg bg-primary/10 text-primary" aria-hidden="true"><i class="pi pi-user-minus" /></span>
          <div>
            <p class="m-0! text-sm font-semibold text-color">Gestion des absences</p>
            <p class="m-0! mt-1! text-xs text-muted-color">Consultez les périodes et le détail des absences.</p>
          </div>
        </div>
        <Button v-if="accessible" label="Voir la liste des absences" icon="pi pi-arrow-right" icon-pos="right" class="w-full sm:w-auto shrink-0" severity="primary" @click="router.push(destination)" />
      </div>
    </template>
  </Dialog>
</template>
