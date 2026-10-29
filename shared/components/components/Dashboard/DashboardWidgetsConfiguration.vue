<script setup>
import {computed, onMounted, ref } from 'vue';
import {useRouter} from 'vue-router';
import {useUsersStore} from '@stores';
import {getWidgetsAvailableService, updateDashboardWidgetLayoutService } from '@requests';
import ApplicationBadge from '../Application/ApplicationBadge.vue';

const router = useRouter();
const userStore = useUsersStore();
const widgets = ref([]);
const dashboardCode = 'portail';
const structureDepartementPersonnelId = computed(() => {
  return userStore.departementDefaut?.departementPersonnel?.id || null;
});
const loading = ref(false);
const saving = ref({});

onMounted(async () => {
  await getBundleWidgets();
});

const getBundleWidgets = async () => {
  loading.value = true;

  const params = {
    structureDepartementPersonnelId: structureDepartementPersonnelId.value
  };
  const response = await getWidgetsAvailableService(dashboardCode, params);
  widgets.value = response.widgets || [];
  loading.value = false;

}

const updateWidget = async (widget) => {
  saving.value[widget.key] = true;

  const payload = { enabled: !widget.enabled };
  const params = {
    dashboardCode,
    structureDepartementPersonnelId: structureDepartementPersonnelId.value
  };

  await updateDashboardWidgetLayoutService(widget.key, payload, params);

  await getBundleWidgets();
  saving.value[widget.key] = false;
}
</script>

<template>
  <div class="space-y-4">
    <div class="flex items-center justify-between card card-body">
      <div>
        <div class="text-xl font-semibold">Personnaliser mon Portail</div>
        <div class="text-sm text-color-secondary">Choisissez les widgets de votre Portail pour le département actif.</div>
      </div>
      <Button icon="pi pi-chevron-left" label="Retour au Portail" severity="secondary" size="small" @click="router.push({ name: 'portail' })"/>
    </div>

    <div v-if="loading" class="card">
      Chargement des widgets...
    </div>

    <div v-else class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-3">
      <div v-for="widget in widgets" :key="widget.key" class="card">
        <div class="mb-2 flex items-center justify-between gap-2 card-header">
          <div class="flex flex-col">
            <div class="font-semibold">{{ widget.label }}</div>
            <div v-if="widget.bundle" class="mt-1">
              <ApplicationBadge :app="widget.bundle" size="xs" show-dot />
            </div>
          </div>
          <Badge :value="widget.enabled ? 'Actif' : 'Inactif'" :severity="widget.enabled ? 'success' : 'danger'" />
        </div>
        <div class="card-body">
          <div class="mb-4 text-sm text-color-secondary">
            Taille par défaut: <strong>{{ widget.colSpan }} × {{ widget.rowSpan }}</strong> (colonnes × lignes)
          </div>

          <Button
              :severity="widget.enabled ? 'danger' : 'primary'"
              :disabled="saving[widget.key]"
              @click="updateWidget(widget)"
          >
            {{ saving[widget.key] ? 'Sauvegarde...' : (widget.enabled ? 'Masquer' : 'Afficher') }}
          </Button>
        </div>
      </div>
    </div>
  </div>
</template>
