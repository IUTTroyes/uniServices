<script setup>
import { ref, watch, onBeforeUnmount } from 'vue';
import EdtJour from '../components/Edt/EdtJour.vue';
import { useUsersStore, useSecurity } from '@stores';
import { getIntranetDashboardSectionService } from '@requests';

const props = defineProps({ section: { type: String, required: true }, title: String, icon: String });
const userStore = useUsersStore();
const security = useSecurity();
const data = ref(null);
const loading = ref(false);
const error = ref(false);
let requestId = 0;
async function load() {
  const current = ++requestId;
  data.value = null;
  error.value = false;
  loading.value = true;
  try {
    const response = await getIntranetDashboardSectionService(props.section);
    if (current === requestId) data.value = response;
  } catch {
    if (current === requestId) error.value = true;
  } finally {
    if (current === requestId) loading.value = false;
  }
}
watch(() => JSON.stringify([props.section, userStore.userId, security.isLoaded, security.currentDepartment?.id,
  security.resolvedPermissions, security.scopedPermissions]), () => {
  if (userStore.isLoaded && security.isLoaded) load();
  else { ++requestId; data.value = null; loading.value = false; }
}, { immediate: true });
onBeforeUnmount(() => { ++requestId; });
</script>

<template>
  <section class="card p-5! mb-0!" :aria-labelledby="`section-${section}`" :aria-busy="loading">
    <h2 :id="`section-${section}`" class="mt-0! mb-4! text-lg! flex items-center gap-3">
      <i :class="[icon, 'text-primary']" aria-hidden="true" />{{ title }}
    </h2>
    <p v-if="loading" class="m-0! text-sm text-muted-color">Chargement…</p>
    <div v-else-if="error" role="alert" class="flex flex-wrap items-center gap-3">
      <p class="m-0! text-sm">Ces informations ne sont pas disponibles pour le moment.</p>
      <Button label="Réessayer" icon="pi pi-refresh" severity="secondary" size="small" @click="load" />
    </div>
    <template v-else-if="data">
      <template v-if="section === 'activite'">
        <p class="mt-0! text-sm text-muted-color">{{ data.todayLabel }}</p>
        <EdtJour></EdtJour>
      </template>
      <template v-else-if="section === 'iut'">
        <div v-if="data.departements?.length" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3">
          <article v-for="department in data.departements" :key="department.id" class="p-4 rounded-xl border border-surface-200 dark:border-surface-700">
            <h3 class="m-0! text-base!">{{ department.libelle }}</h3>
            <p class="mt-2! mb-0! text-sm text-muted-color">{{ department.formations }} formation{{ department.formations > 1 ? 's' : '' }}</p>
          </article>
        </div>
        <p v-else class="m-0! text-sm text-muted-color">Aucun département actif dans votre périmètre de direction.</p>
      </template>
      <template v-else>
        <ul v-if="data.diplomes?.length" class="m-0! p-0! list-none flex flex-wrap gap-2">
          <li v-for="diplome in data.diplomes" :key="diplome.id" class="px-3 py-2 rounded-lg bg-surface-50 dark:bg-surface-800 border border-surface-200 dark:border-surface-700 text-sm">
            <span v-if="diplome.sigle" class="font-semibold">{{ diplome.sigle }} · </span>{{ diplome.libelle }}
          </li>
        </ul>
        <p v-else class="m-0! text-sm text-muted-color">Aucun diplôme dans votre périmètre.</p>
      </template>
    </template>
  </section>
</template>
