<script setup>
import {computed, onMounted, ref } from 'vue';
import {useRouter} from 'vue-router';
import {useUsersStore} from '@stores';
import {getWidgetsCatalogService} from '@requests';

const router = useRouter();
const userStore = useUsersStore();
const widgets = ref([]);
const bundle = computed(() => {
  return router.currentRoute.value.params.bundle || 'portail';
});
const loading = ref(false);
const saving = ref({});

onMounted(async () => {
  await getBundleWidgets();
});

const getBundleWidgets = async () => {
  loading.value = true;
  const params = {
    bundle: bundle.value,
  };
  const response = await getWidgetsCatalogService(params);
  widgets.value = response.widgets || [];
  loading.value = false;
}
</script>

<template>
    <div class="space-y-4">
        <div class="flex items-center justify-between rounded-xl border border-surface-200 bg-surface-0 p-4">
            <div>
                <div class="text-xl font-semibold">Configuration du dashboard</div>
                <div class="text-sm text-color-secondary">Choisissez les widgets à afficher pour votre structure active.</div>
            </div>
            <button class="border-none rounded-lg bg-surface-200 px-4 py-2" type="button" @click="router.back()">
                Retour au dashboard
            </button>
        </div>

        <div v-if="loading" class="rounded-xl border border-surface-200 bg-surface-0 p-6 text-color-secondary">
            Chargement des widgets...
        </div>

        <div v-else class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-3">
            <div v-for="widget in widgets" :key="widget.key" class="rounded-xl border border-surface-200 bg-surface-0 p-4">
                <div class="mb-2 flex items-center justify-between gap-2">
                    <div class="font-semibold">{{ widget.label }}</div>
                    <span class="rounded-full px-2 py-1 text-xs" :class="widget.enabled ? 'bg-green-100 text-green-700' : 'bg-surface-200 text-color-secondary'">
                        {{ widget.enabled ? 'Actif' : 'Inactif' }}
                    </span>
                </div>

                <div class="mb-4 text-sm text-color-secondary">
                    Taille par défaut: <strong>{{ widget.size }}</strong>
                </div>

                <button
                    class="w-full border-none rounded-lg px-3 py-2 font-medium"
                    :class="widget.enabled ? 'bg-red-100 text-red-700' : 'bg-primary text-white'"
                    :disabled="saving[widget.key]"
                    type="button"
                    @click="toggleWidget(widget)"
                >
                    {{ saving[widget.key] ? 'Sauvegarde...' : (widget.enabled ? 'Masquer' : 'Afficher') }}
                </button>
            </div>
        </div>
    </div>
</template>
