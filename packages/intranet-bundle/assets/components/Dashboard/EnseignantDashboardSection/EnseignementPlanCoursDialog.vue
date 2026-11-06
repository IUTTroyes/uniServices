<script setup>
import {useEnseignementPage} from './useEnseignementPage';
import {ref, watch} from 'vue';
import {useRouter} from 'vue-router';
import {getPlansCoursService, getPlanCoursService} from '@requests';
import {ListSkeleton} from '@components';
import PlanCoursShowDialog from '@/components/PlanCours/PlanCoursShowDialog.vue';
const router = useRouter();
const props = defineProps({ visible: Boolean, enseignement: {type: Object, default: null}, anneeUniversitaire: {type: [Number, String], default: null} });
const emit = defineEmits(['update:visible']);
const loading = ref(false);
const error = ref(false);
const data = ref(null);
watch(() => [props.visible, props.enseignement?.id, props.enseignement?.semestre?.id, props.anneeUniversitaire], async () => {

  data.value = null;
  error.value = false;
  loading.value = false;
  if (!props.visible || !props.enseignement?.id || !props.anneeUniversitaire) return;
  loading.value = true;
  try {
    const plans = await getPlansCoursService({enseignement: props.enseignement.id, anneeUniversitaire: props.anneeUniversitaire});
    const result = plans?.[0]?.id ? await getPlanCoursService(plans[0].id) : null;
    data.value = result;
  } catch (err) {
    error.value = true;
    console.error(err);
  } finally {
    loading.value = false;
  }
}, {immediate: true});
const {destination, accessible} = useEnseignementPage('plan', () => props.enseignement);
</script>
<template>
  <PlanCoursShowDialog v-if="data && !loading && !error" :visible="visible" :planCours="data" :enseignement="enseignement"
    @update:visible="emit('update:visible', $event)" @edit="id => router.push(`/intranet/pedagogie/plans-de-cours/edit/${id}`)">
    <template #footer>
      <div class="w-full border-t border-surface-200 dark:border-surface-700 bg-surface-50 dark:bg-surface-800/50 rounded-xl p-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-center gap-3 text-left">
          <span class="flex items-center justify-center shrink-0 w-10 h-10 rounded-lg bg-primary/10 text-primary" aria-hidden="true"><i class="pi pi-book" /></span>
          <div>
            <p class="m-0! text-sm font-semibold text-color">Mes plans de cours</p>
            <p class="m-0! mt-1! text-xs text-muted-color">Retrouvez et complétez vos plans de cours.</p>
          </div>
        </div>
        <div class="flex flex-wrap items-center gap-2">
          <Button label="Modifier" icon="pi pi-pencil" severity="secondary" outlined @click="router.push(`/intranet/pedagogie/plans-de-cours/edit/${data.id}`)" />
        <Button v-if="accessible" label="Voir mes plans de cours" icon="pi pi-arrow-right" icon-pos="right" class="w-full sm:w-auto shrink-0" severity="primary" @click="router.push(destination)" />
        </div>
      </div>
    </template>
  </PlanCoursShowDialog>
  <Dialog v-else :visible="visible" @update:visible="emit('update:visible', $event)" modal
    :header="`Plan de cours · ${enseignement?.codeEnseignement || ''} - ${enseignement?.libelle || ''}`" class="w-[90vw] md:w-[85vw]">
    <ListSkeleton v-if="loading" />
    <Message v-else-if="error" severity="error">Impossible de récupérer le plan de cours.</Message>
    <Message v-else severity="warn">Aucun plan de cours trouvé pour cette année universitaire.</Message>
    <template #footer>
      <div class="w-full border-t border-surface-200 dark:border-surface-700 bg-surface-50 dark:bg-surface-800/50 rounded-xl p-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-center gap-3 text-left">
          <span class="flex items-center justify-center shrink-0 w-10 h-10 rounded-lg bg-primary/10 text-primary" aria-hidden="true"><i class="pi pi-book" /></span>
          <div>
            <p class="m-0! text-sm font-semibold text-color">Mes plans de cours</p>
            <p class="m-0! mt-1! text-xs text-muted-color">Retrouvez et complétez vos plans de cours.</p>
          </div>
        </div>
        <Button v-if="accessible" label="Voir mes plans de cours" icon="pi pi-arrow-right" icon-pos="right" class="w-full sm:w-auto shrink-0" severity="primary" @click="router.push(destination)" />
      </div>
    </template>
  </Dialog>
</template>
