<script setup>
import {computed, onMounted, ref } from 'vue';
import {useRouter} from 'vue-router';
import {useUsersStore} from '@stores';
import {getWidgetsAvailableService, updateDashboardWidgetLayoutService } from '@requests';

const router = useRouter();
const userStore = useUsersStore();
const widgets = ref([]);
const bundle = computed(() => {
  if (router.currentRoute.value.params.bundle) {
    return router.currentRoute.value.params.bundle;
  }
  const routeName = router.currentRoute.value.name;
  if (routeName && routeName.toLowerCase().includes('intranet')) {
    return 'intranet';
  }
  const routePath = router.currentRoute.value.path;
  if (routePath && routePath.toLowerCase().includes('intranet')) {
    return 'intranet';
  }
  return 'portail';
});
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
  console.log(bundle.value);
  const response = await getWidgetsAvailableService(bundle.value, params);
  widgets.value = response.widgets || [];
  loading.value = false;

  console.log(widgets.value);
}

const updateWidget = async (widget) => {
    saving.value[widget.key] = true;
    
    const payload = { enabled: !widget.enabled };
    const params = {
        dashboardCode: bundle.value,
        structureDepartementPersonnelId: structureDepartementPersonnelId.value
    };
    
    const response = await updateDashboardWidgetLayoutService(widget.key, payload, params);
    console.log(response);
    
    await getBundleWidgets();
    saving.value[widget.key] = false;
}
</script>

<template>
    <div class="space-y-4">
        <div class="flex items-center justify-between card">
            <div>
                <div class="text-xl font-semibold">Configuration du dashboard</div>
                <div class="text-sm text-color-secondary">Choisissez les widgets à afficher pour votre structure active.</div>
            </div>
            <Button icon="pi pi-chevron-left" label="Retour au dashboard" severity="secondary" size="small" @click="router.back()"/>
        </div>

        <div v-if="loading" class="card">
            Chargement des widgets...
        </div>

        <div v-else class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-3">
            <div v-for="widget in widgets" :key="widget.key" class="card p-4">
                <div class="mb-2 flex items-center justify-between gap-2">
                    <div class="font-semibold">{{ widget.label }}</div>
                    <Badge :value="widget.enabled ? 'Actif' : 'Inactif'" :severity="widget.enabled ? 'success' : 'danger'" />
                </div>

                <div class="mb-4 text-sm text-color-secondary">
                    Taille par défaut: <strong>{{ widget.size }}</strong>
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
</template>
