<script setup>
import {computed, onMounted, ref} from 'vue';
import {useRouter} from 'vue-router';
import {useUsersStore, useAnneeUnivStore} from '@stores';
import {getWidgetsCatalogService} from '@requests';
import {WidgetCard} from '@components';
import { formatDateLong } from "@helpers/date";

const router = useRouter();
const userStore = useUsersStore();
const anneeUnivStore = useAnneeUnivStore();
const selectedAnneeUniversitaireId = computed(() => anneeUnivStore.selectedAnneeUniv?.id ?? null);
const widgets = ref([]);
const widgetData = ref({});
const loading = ref(true);
const date = new Date();

const structureDepartementPersonnelId = computed(() => userStore.departementDefaut?.departementPersonnel?.id || null);

onMounted(() => {
  getDashboardWidgets();
});

const getDashboardWidgets = async () => {
  loading.value = true;
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
  loading.value = false;
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
  <section class="col-span-12 lg:col-span-10 pb-14">
    <div class="h-screen overflow-y-auto">
      <div v-if="!userStore.isLoading" class="flex items-center justify-between mb-4">
        <div class="flex items-center">
          <div class="w-20 h-20 bg-primary-400 rounded-full flex items-center justify-center shrink-0">
            <template v-if="userStore.userPhoto">
              <img :src="userStore.userPhoto" alt="photo de profil" class="rounded-full" />
            </template>
            <template v-else>
              <span class="text-gray-700 text-xl">{{ initiales }}</span>
            </template>
          </div>
          <div class="ml-4">
            <h2 class="text-2xl! mb-0! font-bold flex items-center gap-2">
              <span class="font-light">Bonjour,</span> {{ userStore.user.prenom }}
            </h2>
            <small class="text-gray-500">{{ formatDateLong(date) }}</small>
          </div>
        </div>
        <div class="card flex justify-between items-center gap-6 m-0! p-4!">
          <div>
            <div class="text-xl font-semibold">Mon dashboard</div>
            <div class="text-sm text-color-secondary">Personnalisez vos widgets.</div>
          </div>
          <Button @click="router.push({name: 'IntranetDashboardWidgetsConfig'})" icon="pi pi-cog" label="Configurer" size="small"/>
        </div>
      </div>
      <div v-if="loading" class="rounded-xl border border-surface-200 bg-surface-0 p-6 text-color-secondary h-full overflow-hidden">
        Chargement des widgets...
      </div>
      <div v-else class="grid grid-cols-12 gap-4 overflow-y-auto">
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
    </div>
  </section>
</template>
