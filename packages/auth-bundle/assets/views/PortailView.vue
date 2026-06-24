<script setup>
import {computed, onMounted, ref} from 'vue';
import {TopbarComponent} from '@components';
import {tools} from '@config/uniServices.js';
import {getWidgetDataByCodeService, getWidgetsCatalogService} from '@requests';

const STORAGE_KEY = 'portail.widgets.layout';

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

const bundles = ref([]);
const widgets = ref([]);
const selectedBundle = ref('all');
const widgetData = ref({});
const loading = ref(true);

const bundleAliases = {
  portfolio: 'unifolio',
};

const getBundleUrl = (bundleCode) => {
  const normalizedCode = bundleAliases[bundleCode] || bundleCode;
  const tool = tools.find(({urlSlug, url}) => urlSlug === normalizedCode || url?.includes(`/${normalizedCode}/`));
  return tool?.url || null;
};

const onBundleClick = (bundleUrl) => {
  if (bundleUrl === 'all') {
    selectedBundle.value = 'all';
    return;
  }
    window.location.href = bundleUrl;
};

const visibleWidgets = computed(() => {
  const filtered = selectedBundle.value === 'all'
      ? widgets.value
      : widgets.value.filter((widget) => widget.bundle === selectedBundle.value);

  return [...filtered]
      .filter((widget) => widget.enabled)
      .sort((a, b) => a.position - b.position);
});

const gridClass = (size) => {
  if (size === 'small') {
    return 'col-span-12 lg:col-span-4';
  }
  if (size === 'large') {
    return 'col-span-12';
  }

  return 'col-span-12 lg:col-span-6';
};

const saveLayout = () => {
  const persisted = widgets.value.map((widget) => ({
    code: widget.code,
    enabled: widget.enabled,
    size: widget.size,
    position: widget.position,
  }));
  localStorage.setItem(STORAGE_KEY, JSON.stringify(persisted));
};

const applyPersistedLayout = (catalogWidgets) => {
  const raw = localStorage.getItem(STORAGE_KEY);
  if (!raw) {
    return catalogWidgets.map((widget, index) => ({...widget, position: index}));
  }

  const saved = JSON.parse(raw);
  const savedByCode = new Map(saved.map((item) => [item.code, item]));

  return catalogWidgets.map((widget, index) => {
    const savedWidget = savedByCode.get(widget.code);
    if (!savedWidget) {
      return {...widget, position: index};
    }

    return {
      ...widget,
      enabled: savedWidget.enabled ?? widget.enabled,
      size: savedWidget.size ?? widget.size,
      position: Number.isInteger(savedWidget.position) ? savedWidget.position : index,
    };
  });
};

const rotateSize = (widget) => {
  const sizes = ['small', 'medium', 'large'];
  const index = sizes.indexOf(widget.size || 'medium');
  widget.size = sizes[(index + 1) % sizes.length];
  saveLayout();
};

const toggleWidget = (widget) => {
  widget.enabled = !widget.enabled;
  saveLayout();
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
  saveLayout();
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
    bundles.value = [{code: 'all', label: 'Tous les bundles'}, ...(tools || [])];
    const response = await getWidgetsCatalogService();
    widgets.value = applyPersistedLayout(response.widgets || []);
    await Promise.all(widgets.value.filter((widget) => widget.enabled).map((widget) => loadWidgetData(widget.code)));
  } finally {
    loading.value = false;
  }
});
</script>

<template>
  <main>
    <TopbarComponent :app-name :logo-url/>

    <div class="px-4 lg:px-10">
      <div class="grid grid-cols-12 gap-4">
        <aside class="col-span-12 lg:col-span-3 pt-28 pb-14 h-screen">
          <div class="card h-full overflow-y-auto">
            <div class="mb-3 text-sm font-semibold text-color-secondary">Bundles disponibles</div>
          <div class="flex flex-col gap-2">
            <button
                v-for="bundle in bundles"
                :key="bundle.urlSlug"
                type="button"
                class="text-left rounded-lg px-3 py-2"
                @click="onBundleClick(bundle.url)"
            >
              {{ bundle.name }}
            </button>
          </div>
          </div>
        </aside>

        <section class="col-span-12 lg:col-span-9 pt-28 pb-14 h-screen">
          <div v-if="loading" class="rounded-xl border border-surface-200 bg-surface-0 p-6 text-color-secondary h-full overflow-hidden">
            Chargement des widgets...
          </div>

          <div v-else class="grid grid-cols-12 gap-4 h-full overflow-y-auto">
            <article
                v-for="widget in visibleWidgets"
                :key="widget.code"
                :class="`${gridClass(widget.size)} rounded-xl border border-surface-200 bg-surface-0 p-4`"
            >
              <div class="mb-3 flex items-center justify-between gap-2">
                <div class="font-semibold"><i :class="`${widget.icon} mr-2 text-primary-500`"/>{{ widget.label }}</div>
                <div class="flex items-center gap-1">
                  <Button icon="pi pi-arrow-left" text rounded @click="moveWidget(widget, -1)"/>
                  <Button icon="pi pi-arrow-right" text rounded @click="moveWidget(widget, 1)"/>
                  <Button icon="pi pi-arrows-h" text rounded @click="rotateSize(widget)"/>
                  <Button icon="pi pi-times" text rounded @click="toggleWidget(widget)"/>
                </div>
              </div>
              <div class="text-sm text-color-secondary mb-2">{{ widget.code }}</div>
              <pre class="widget-data">{{ widgetData[widget.code] || { message: 'Chargement...' } }}</pre>
            </article>
          </div>
        </section>
      </div>
    </div>
  </main>
</template>

<style scoped>
.widget-data {
  white-space: pre-wrap;
  background: #f3f4f6;
  border-radius: 0.5rem;
  padding: 0.75rem;
  font-size: 0.875rem;
}
</style>
