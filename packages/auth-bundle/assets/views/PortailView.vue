<script setup>
import {computed, onMounted, ref} from 'vue';
import {TopbarComponent, WidgetCard} from '@components';
import {tools} from '@config/uniServices.js';
import {getWidgetDataByCodeService, getWidgetsCatalogService} from '@requests';
import { useUsersStore } from "@stores";
import { formatDateLong } from "@helpers/date";

const userStore = useUsersStore();
const date = new Date();

defineProps({
  appName: {
    type: String,
    required: true,
  },
  logoUrl: {
    type: String,
    required: false,
    default: '',
  },
});

const activatedBundles = ref([]);
const unactivatedBundles = ref([]);
const widgets = ref([]);
const widgetData = ref({});
const loading = ref(true);

const onBundleClick = (bundleUrl) => {
  window.location.href = bundleUrl;
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
  const sorted = [...widgets.value].sort((a, b) => a.position - b.position);
  const index = sorted.findIndex((item) => item.code === widget.code);
  if (index === -1) {
    return;
  }
  
  const targetIndex = index + direction;
  if (targetIndex < 0 || targetIndex >= sorted.length) {
    return;
  }
  
  const element = sorted.splice(index, 1)[0];
  sorted.splice(targetIndex, 0, element);
  
  sorted.forEach((item, position) => {
    item.position = position;
  });
  widgets.value = sorted;
};

const loadWidgetData = async (code) => {
  try {
    widgetData.value[code] = await getWidgetDataByCodeService(code);
  } catch {
    widgetData.value[code] = {message: 'Aucune donnée disponible'};
  }
};

onMounted(async () => {
  loading.value = true;
  try {
    activatedBundles.value = tools.filter((bundle) => userStore.user.applications.includes(bundle.name));
    unactivatedBundles.value = tools.filter((bundle) => !userStore.user.applications.includes(bundle.name));
    const response = await getWidgetsCatalogService();
    widgets.value = response.widgets || [];
    await Promise.all(
    widgets.value.map(({ code }) =>
    loadWidgetData(code)
    )
    );  } finally {
      loading.value = false;
    }
  });
</script>

<template>
  <main>
    <TopbarComponent :app-name :logo-url/>
    
    <div class="px-4 lg:px-10">
      <div class="grid grid-cols-12 gap-4">
        <aside class="col-span-12 lg:col-span-2 pt-28 pb-14 h-screen">
          <div class="card h-full overflow-y-auto flex flex-col gap-6">
            <div class="flex flex-col gap-4">
              <div class="text-md font-semibold text-color-secondary uppercase">Applications</div>
              <div class="flex flex-col">
                <Button
                v-for="bundle in activatedBundles"
                :key="bundle.urlSlug"
                type="button"
                text
                rounded
                size="large"
                class="justify-start!"
                @click="onBundleClick(bundle.url)"
                >
                {{ bundle.name }}
              </Button>
            </div>
          </div>
          <div v-if="unactivatedBundles.length > 0" class="flex flex-col gap-4">
            <div class="text-ms font-semibold text-color-secondary uppercase">Non activé</div>
            <div class="flex flex-col">
              <Button
              v-for="bundle in unactivatedBundles"
              :key="bundle.urlSlug"
              type="button"
              text
              rounded
              severity="secondary"
              disabled
              size="large"
              class="justify-start!"
              >
              {{ bundle.name }}
            </Button>
          </div>
        </div>
      </div>
    </aside>
    
    <section class="col-span-12 lg:col-span-10 pt-28 pb-14 h-screen">
      <div class="h-full overflow-y-auto">
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
            <Button >
              Configurer les widgets
            </button>
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
          @move="moveWidget($event)"
          @rotate="rotateSize($event)"
          @toggle="toggleWidget($event)"
          />
        </div>
      </div>
    </section>
  </div>
</div>
</main>
</template>

<style scoped>
</style>
