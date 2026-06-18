<script setup>
import {computed, onMounted, ref} from 'vue';
import {useRouter} from 'vue-router';
import {useUsersStore} from '@stores';
import {getDashboardService, patchDashboardWidgetLayoutService} from '@requests';

const router = useRouter();
const userStore = useUsersStore();
const widgets = ref([]);
const loading = ref(false);
const saving = ref({});

const structureDepartementPersonnelId = computed(() => userStore.departementDefaut?.departementPersonnel?.id || null);
const dashboardParams = computed(() => {
    if (!structureDepartementPersonnelId.value) {
        return {};
    }

    return {structureDepartementPersonnelId: structureDepartementPersonnelId.value};
});

const orderedWidgets = computed(() => [...widgets.value].sort((a, b) => a.label.localeCompare(b.label)));

const loadWidgets = async () => {
    loading.value = true;

    try {
        const response = await getDashboardService(dashboardParams.value);
        widgets.value = response.widgets || [];
    } finally {
        loading.value = false;
    }
};

const toggleWidget = async (widget) => {
    saving.value = {...saving.value, [widget.key]: true};

    try {
        const enabled = !widget.enabled;
        await patchDashboardWidgetLayoutService(widget.key, {
            enabled,
            collapsed: widget.collapsed,
            position: widget.position,
            size: widget.size,
            config: widget.config || {},
        }, dashboardParams.value);

        widget.enabled = enabled;
    } finally {
        saving.value = {...saving.value, [widget.key]: false};
    }
};

onMounted(loadWidgets);
</script>

<template>
    <div class="space-y-4">
        <div class="flex items-center justify-between rounded-xl border border-surface-200 bg-surface-0 p-4">
            <div>
                <div class="text-xl font-semibold">Configuration du dashboard</div>
                <div class="text-sm text-color-secondary">Choisissez les widgets à afficher pour votre structure active.</div>
            </div>
            <button class="border-none rounded-lg bg-surface-200 px-4 py-2" type="button" @click="router.push({name: 'Dashboard'})">
                Retour au dashboard
            </button>
        </div>

        <div v-if="loading" class="rounded-xl border border-surface-200 bg-surface-0 p-6 text-color-secondary">
            Chargement des widgets...
        </div>

        <div v-else class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-3">
            <div v-for="widget in orderedWidgets" :key="widget.key" class="rounded-xl border border-surface-200 bg-surface-0 p-4">
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
