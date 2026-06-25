<script setup>
import {computed, onMounted, ref} from 'vue';
import {useRouter} from 'vue-router';
import {useUsersStore, useAnneeUnivStore} from '@stores';
import {getWidgetsCatalogService} from '@requests';
import {WidgetCard} from '@components';

const router = useRouter();
const userStore = useUsersStore();
const anneeUnivStore = useAnneeUnivStore();
const selectedAnneeUniversitaireId = computed(() => anneeUnivStore.selectedAnneeUniv?.id ?? null);
const widgets = ref([]);
const widgetData = ref({});

const structureDepartementPersonnelId = computed(() => userStore.departementDefaut?.departementPersonnel?.id || null);

onMounted(() => {
  getDashboardWidgets();
});

const getDashboardWidgets = async () => {
  const params = {
    dashboardCode: 'intranet',
    structureDepartementPersonnelId: structureDepartementPersonnelId.value,
  };
  const response = await getWidgetsCatalogService(params);
  widgets.value = response.widgets || [];
  await Promise.all(
    widgets.value.map(({ code }) =>
    loadWidgetData(code)
    )
    );
}

const loadWidgetData = async (code) => {
  try {
    widgetData.value[code] = await getWidgetDataByCodeService(code);
  } catch {
    widgetData.value[code] = {message: 'Aucune donnée disponible'};
  }
};

const rotateSize = (widget) => {
  const sizes = ['small', 'medium', 'large'];
  const index = sizes.indexOf(widget.size || 'medium');
  widget.size = sizes[(index + 1) % sizes.length];
};

const toggleWidget = (widget) => {
  widget.enabled = !widget.enabled;
};

const moveWidget = (widget, direction) => {
  // tableau trier par position
  const sorted = [...widgets.value].sort((a, b) => a.position - b.position);
  // trouver l'index du widget dans le tableau
  const index = sorted.findIndex((item) => item.code === widget.code);
  
  // si le widget n'est pas trouvé, on arrête
  if (index === -1) {
    return;
  }
  
  // index cible
  const targetIndex = index + direction;

  // si l'index cible est en dehors du tableau, on arrête
  if (targetIndex < 0 || targetIndex >= sorted.length) {
    return;
  }
  
  // on retire le widget de son index et on l'insère à l'index cible
  const movedWidget = sorted.splice(index, 1)[0];
  sorted.splice(targetIndex, 0, movedWidget);
  
  // on met à jour les positions des widgets
  sorted.forEach((item, position) => {
    item.position = position;
  });
  
  // on met à jour le tableau des widgets
  widgets.value = sorted;
};
</script>

<template>
  <div class="flex flex-col gap-4">
    <div class="grid grid-cols-12 gap-4">
      <WidgetCard
          v-for="widget in widgets"
          :key="widget.code"
          :widget="widget"
          :data="widgetData[widget.code]"
          :first="widget.position == 0 ? true : false"
          :last="widget.position == widgets.length - 1 ? true : false"
          @move="moveWidget"
          @rotate="rotateSize"
          @toggle="toggleWidget"
          />
    </div>
    <div class="card flex justify-between items-center">
      <div>
        <div class="text-xl font-semibold">Mon dashboard</div>
        <div class="text-sm text-color-secondary">Personnalisez vos widgets en fonction de votre structure.</div>
      </div>
      <button class="border-none rounded-lg bg-primary px-4 py-2 text-white" type="button" @click="router.push({name: 'DashboardWidgetsConfig'})">
        Configurer les widgets
      </button>
    </div>
  </div>
</template>
