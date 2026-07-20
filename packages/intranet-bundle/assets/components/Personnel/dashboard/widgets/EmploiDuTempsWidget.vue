<script setup>
import { useRouter } from 'vue-router';
import { EdtEventRow } from '@components';

const router = useRouter();

defineProps({
  data: {
    type: Object,
    default: () => ({items: []}),
  },
});
</script>

<template>
  <div class="flex flex-col gap-3">
    <template v-if="data.items && data.items.length > 0">
      <EdtEventRow
          v-for="item in data.items || []"
          :key="`${item.heure}-${item.cours}`"
          :item="item"
          show-action-button
          action-tooltip="Faire l'appel"
      />
      <div v-if="data.items?.length === 0" class="flex justify-center text-center py-4 text-muted-color flex flex-col items-center min-h-36 max-h-36 overflow-y-hidden relative">
        <img src="@/assets/illu/palm.svg" alt="" class="w-64 h-64 absolute -bottom-24 right-4">
        <div class="z-10 bg-primary-100/80 px-4 py-2 rounded-xl font-semibold text-black">Aucun cours prévu aujourd'hui.</div>
      </div>
    </template>
    <template v-else>
      <div class="flex flex-row justify-start items-start rounded-xl h-full min-h-42 relative palm bg-neutral-100 dark:bg-neutral-700">
        <div class="flex flex-col justify-center p-4 gap-2">
          <div class="text-lg font-bold">Aucun événement aujourd'hui</div>
        </div>
      </div>
    </template>
    <Button label="Voir l'emploi du temps" class="w-full" severity="secondary" size="small" @click="router.push('/intranet/agenda')"/>
  </div>
</template>

<style scoped>
.palm {
  background-image: url("@/assets/illu/palm.svg");
  background-size: 30%;
  background-repeat: no-repeat;
  background-position: right;
}
</style>
