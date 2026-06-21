<script setup>
import {computed, onMounted, ref} from 'vue';
import {useRouter} from 'vue-router';
import {useUsersStore, useAnneeUnivStore} from '@stores';
import {getDashboardService, getDashboardWidgetDataService, patchDashboardWidgetLayoutService} from '@requests';
import {dashboardWidgetRegistry} from '@/components/Personnel/dashboard/dashboardWidgetRegistry';
import DashboardWidgetShell from '@/components/Personnel/dashboard/DashboardWidgetShell.vue';

const router = useRouter();
const userStore = useUsersStore();
const anneeUnivStore = useAnneeUnivStore();
const selectedAnneeUniversitaireId = computed(() => anneeUnivStore.selectedAnneeUniv?.id ?? null);
const widgets = ref([]);
const widgetData = ref({});
const widgetLoading = ref({});
const widgetError = ref({});

const structureDepartementPersonnelId = computed(() => userStore.departementDefaut?.departementPersonnel?.id || null);

const dashboardParams = computed(() => {
  if (!structureDepartementPersonnelId.value) {
    return {};
  }

  return {structureDepartementPersonnelId: structureDepartementPersonnelId.value, anneeUniversitaire: selectedAnneeUniversitaireId.value};
});

const getWidgetParams = (widget) => {
  const base = dashboardParams.value;

  switch (widget?.key) {
    case 'emploi_du_temps':
      return {
        ...base,
        day: new Date().toISOString().split('T')[0],
        personnel: userStore.userId,
        departement: userStore.departementDefaut?.id ?? null,
      };

    case 'actions_urgentes':
      return {
        ...base,
      };

    case 'notes':
      return {
        ...base,
      };

    case 'documents_recents':
      return {
        ...base,
        personnel: userStore.user.id,
        departement: userStore.departementDefaut?.id ?? null,
      };

    default:
      return base;
  }
};

const orderedWidgets = computed(() => [...widgets.value].sort((a, b) => a.position - b.position));

const gridClass = (size) => {
  if (size === 'small') {
    return 'col-span-4';
  }
  if (size === 'large') {
    return 'col-span-12';
  }

  return 'col-span-8';
};

const loadDashboard = async () => {
  const response = await getDashboardService(dashboardParams.value);
  widgets.value = response.widgets || [];
  await Promise.all(widgets.value.filter(widget => widget.enabled).map(widget => loadWidgetData(widget.key)));
};

const loadWidgetData = async (widgetKey) => {
  const widget = widgets.value.find(item => item.key === widgetKey);
  if (!widget || !widget.enabled) {
    return;
  }

  widgetLoading.value = {...widgetLoading.value, [widgetKey]: true};
  widgetError.value = {...widgetError.value, [widgetKey]: false};

  try {
    const data = await getDashboardWidgetDataService(widget.dataUrl, getWidgetParams(widget));
    widgetData.value = {...widgetData.value, [widgetKey]: data};
  } catch {
    widgetError.value = {...widgetError.value, [widgetKey]: true};
  } finally {
    widgetLoading.value = {...widgetLoading.value, [widgetKey]: false};
  }
};

const saveLayout = async (widget) => {
  await patchDashboardWidgetLayoutService(widget.key, {
    enabled: widget.enabled,
    collapsed: widget.collapsed,
    position: widget.position,
    size: widget.size,
    config: widget.config || {},
  }, dashboardParams.value);
};

const updateWidget = async (key, patch) => {
  const target = widgets.value.find(widget => widget.key === key);
  if (!target) {
    return;
  }

  Object.assign(target, patch);
  await saveLayout(target);
  if (patch.enabled === true) {
    await loadWidgetData(key);
  }
};

const moveWidget = async (key, direction) => {
  const sorted = [...widgets.value].sort((a, b) => a.position - b.position);
  const index = sorted.findIndex(w => w.key === key);
  if (index === -1) return;

  let targetIndex = index + direction;
  while (targetIndex >= 0 && targetIndex < sorted.length && !sorted[targetIndex].enabled) {
    targetIndex += direction;
  }

  if (targetIndex < 0 || targetIndex >= sorted.length) return;

  const element = sorted.splice(index, 1)[0];
  sorted.splice(targetIndex, 0, element);

  const promises = [];
  sorted.forEach((widget, i) => {
    if (widget.position !== i) {
      widget.position = i;
      promises.push(saveLayout(widget));
    }
  });

  await Promise.all(promises);
};

onMounted(async () => {
  await loadDashboard();
});
</script>

<template>
  <div class="flex flex-col gap-4">
    <div class="grid grid-cols-12 gap-4">
      <div v-for="widget in orderedWidgets" :key="widget.key" :class="gridClass(widget.size)">
        <template v-if="widget.enabled">
          <DashboardWidgetShell
              :widget="widget"
              :widgetsLength="widgets.length"
              :loading="!!widgetLoading[widget.key]"
              :error="!!widgetError[widget.key]"
              @refresh="loadWidgetData(widget.key)"
              @toggle-enabled="updateWidget(widget.key, {enabled: !widget.enabled})"
              @toggle-collapsed="updateWidget(widget.key, {collapsed: !widget.collapsed})"
              @resize="size => updateWidget(widget.key, {size})"
              @move-backward="moveWidget(widget.key, -1)"
              @move-forward="moveWidget(widget.key, 1)"
              class="h-full"
          >
            <component
                :is="dashboardWidgetRegistry[widget.component]"
                :widget="widget"
                :data="widgetData[widget.key] || {}"
            />
          </DashboardWidgetShell>
        </template>
      </div>
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
