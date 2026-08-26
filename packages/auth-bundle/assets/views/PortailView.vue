<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { TopbarComponent, WidgetCard, GlobalLoader, HeaderComponent, PermissionGuard } from '@components';
import { tools } from '@config/uniServices.js';
import { getWidgetDataByCodeService, getWidgetsCatalogService, updateDashboardWidgetLayoutService, getActualitesService } from '@requests';
import { useUsersStore, useAnneeUnivStore, useEtablissementStore } from "@stores";
import { formatDateLong } from "@helpers/date";

const router = useRouter();
const route = useRoute();
const userStore = useUsersStore();
const anneeUnivStore = useAnneeUnivStore();
const date = new Date();
const etablissementStore = useEtablissementStore();
const departement = userStore.departementDefaut;

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
const isLoadingBundles = ref(false);
const widgets = ref([]);
const isLoadingWidgets = ref(false);
const widgetData = ref({});
const selectedAnneeUniversitaireId = computed(() => anneeUnivStore.selectedAnneeUniv?.id ?? null);
const etablissement = ref([]);

const onBundleClick = (bundleUrl) => {
  window.location.href = bundleUrl;
};

const isBundleActivated = (bundle) => {
  return userStore.applications.includes(bundle.urlSlug);
};

const structureDepartementPersonnelId = computed(() => userStore.departementDefaut?.departementPersonnel?.id || null);

const updateWidgetSpan = async (widget, spanType, newValue) => {
  // Limiter colSpan à max 4 (largeur de la grille)
  if (spanType === 'colSpan') {
    newValue = Math.min(4, Math.max(1, newValue));
  }
  // rowSpan n'a pas de limite
  if (spanType === 'rowSpan') {
    newValue = Math.max(1, newValue);
  }

  const updateData = { [spanType]: newValue };

  if (spanType === 'colSpan') {
    widget.colSpan = newValue;
  } else if (spanType === 'rowSpan') {
    widget.rowSpan = newValue;
  }

  await updateDashboardWidgetLayoutService(
      widget.code,
      updateData,
      {
        dashboardCode: 'portail',
        structureDepartementPersonnelId: structureDepartementPersonnelId.value,
      }
  );
};

const toggleWidget = async (widget) => {
  widget.enabled = !widget.enabled;
  await updateDashboardWidgetLayoutService(
      widget.code,
      { enabled: widget.enabled },
      {
        dashboardCode: 'portail',
        structureDepartementPersonnelId: structureDepartementPersonnelId.value,
      }
  );
  await getWidgets();
};

const moveWidget = async (widget, direction) => {
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

  // pour chaque widget
  widgets.value.map(async widget => {
    // on met à jour la position dans la base de données
    await updateDashboardWidgetLayoutService(
        widget.code,
        {position: widget.position},
        {
          dashboardCode: 'portail',
          structureDepartementPersonnelId: structureDepartementPersonnelId.value,
        }
    );
  });

};

const loadWidgetData = async (code) => {
  try {
    widgetData.value[code] = await getWidgetDataByCodeService(code);
  } catch(error) {
    console.error(error)
  }
};

const getWidgets = async () => {
  isLoadingWidgets.value = true;
  const params = {
    dashboardCode: 'portail',
    structureDepartementPersonnelId: structureDepartementPersonnelId.value,
  };
  const response = await getWidgetsCatalogService(params);
  widgets.value = response.widgets || [];
  await Promise.all(
      widgets.value.map(({ code }) => loadWidgetData(code))
  );
  isLoadingWidgets.value = false;
};

onMounted(async () => {
  isLoadingBundles.value = true;
  try {
    etablissement.value = await etablissementStore.etablissement;
    console.log(etablissement.value)

    activatedBundles.value = tools.filter((bundle) => isBundleActivated(bundle));
    unactivatedBundles.value = tools.filter((bundle) => !isBundleActivated(bundle));
    // si on a le bundle "intranet" on le place en premier dans le tableau
    if (activatedBundles.value.some((bundle) => bundle.urlSlug === 'intranet')) {
      const intranetBundle = activatedBundles.value.find((bundle) => bundle.urlSlug === 'intranet');
      activatedBundles.value = [intranetBundle, ...activatedBundles.value.filter((bundle) => bundle.urlSlug !== 'intranet')];
    }
    await getWidgets();
  } finally {
    isLoadingBundles.value = false;
  }
});

// Recharger les widgets quand on revient de la page de configuration
watch(() => route.path, async (newPath, oldPath) => {
  if (oldPath?.includes('/portail/widgets') && !newPath.includes('/portail/widgets')) {
    isLoadingWidgets.value = true;
    try {
      await getWidgets();
    } finally {
      isLoadingWidgets.value = false;
    }
  }
});
</script>

<template>
  <main>
    <TopbarComponent :app-name :logo-url/>

    <RouterView class="mt-28 mx-10"/>
    <div v-if="!route.path.includes('/portail/widgets')" class="px-4 lg:px-10">
      <div class="grid grid-cols-12 gap-4">
        <aside class="col-span-12 lg:col-span-2 pt-28 pb-14 h-screen">
          <div class="card card-body h-full overflow-y-auto flex flex-col gap-6">
            <div class="flex flex-col gap-4">
              <div class="text-md font-semibold text-color-secondary uppercase">Applications</div>
              <GlobalLoader v-if="isLoadingBundles" text="Chargement des applications..."/>
              <div v-else class="flex flex-col">
                <template v-if="activatedBundles.length > 0">
                  <Button
                      v-for="bundle in activatedBundles"
                      :key="bundle.urlSlug"
                      type="button"
                      text
                      rounded
                      class="justify-start! text-left font-semibold"
                      @click="onBundleClick(bundle.url)"
                  >
                    {{ bundle.name }}
                  </Button>
                </template>
                <Message v-else severity="warn" class="flex! flex-col! items-center! justify-center! text-center!">
                  <i class="pi pi-exclamation-triangle text-2xl! font-bold" />
                  <p class="font-bold">Vos accès aux applications n'ont pas été configurés. Veuillez contacter votre administrateur pour plus d'informations.</p>
                </Message>
              </div>
            </div>
            <div v-if="userStore.isSuperAdmin" class="flex flex-col gap-4">
              <div class="text-md font-semibold text-color-secondary uppercase">Administration</div>
              <div class="flex flex-col">
                <Button
                    type="button"
                    text
                    rounded
                    class="justify-start! text-left font-semibold"
                    @click="router.push('/configuration')"
                >
                  Configuration
                </Button>
              </div>
            </div>
            <div v-if="unactivatedBundles.length > 0" class="flex flex-col gap-4">
              <div class="text-ms font-semibold text-color-secondary uppercase">Non activé</div>
              <GlobalLoader v-if="isLoadingBundles" text="Chargement des applications..."/>
              <div v-else class="flex flex-col">
                <Button
                    v-for="bundle in unactivatedBundles"
                    :key="bundle.urlSlug"
                    type="button"
                    text
                    rounded
                    severity="secondary"
                    disabled
                    class="justify-start! text-left font-semibold"
                >
                  {{ bundle.name }}
                </Button>
              </div>
            </div>
          </div>
        </aside>

        <section class="col-span-12 lg:col-span-10 pt-28 pb-14 h-screen">
          <div class="h-full overflow-y-auto">
            <HeaderComponent
                icon="pi pi-home"
                :titre="`Portail - ${departement.libelle}`"
                description="Accédez à vos applications et personnalisez votre tableau de bord"
            />
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
                <Button icon="pi pi-cog" label="Configurer" size="small" @click="router.push({name: 'PortailDashboardWidgetsConfig', params: {bundle: 'portail'}})"/>
              </div>
            </div>
            <GlobalLoader v-if="isLoadingWidgets" text="Chargement des widgets..."/>
            <div v-else class="dashboard-grid">
              <WidgetCard
                  v-for="widget in widgets"
                  :key="widget.code"
                  :widget="widget"
                  :data="widgetData[widget.code]"
                  :first="widget.position == 0 ? true : false"
                  :last="widget.position == widgets.length - 1 ? true : false"
                  @move="moveWidget"
                  @updateSpan="updateWidgetSpan"
                  @toggle="toggleWidget"
                  :is-portail = true
              />
            </div>

            <div class="flex justify-between mt-4 gap-4">
              <div class="card w-2/3">
                <header class="card-header flex justify-between items-center w-full">
                  <div class="flex flex-col items-start">
                    <h2 class="m-0! text-xl!">
                      <i class="pi pi-link text-primary"></i> Liens utiles
                    </h2>
                  </div>
                  <PermissionGuard permission="isSuperAdmin">
                    <Button severity="primary" size="small" icon="pi pi-pencil" label="Modifier"/>
                  </PermissionGuard>
                </header>
                <div class="card-body flex items-start justify-around gap-4">
                  <div class="w-full flex flex-col items-center justify-center bg-surface-200/20 rounded-md p-2">
                    <i class="pi pi-book text-2xl! text-primary"></i>
                    <div class="font-bold">Documentation</div>
                    <Button severity="primary" size="small" icon="pi pi-external-link" label="Accéder"/>
                  </div>
                  <div class="w-full flex flex-col items-center justify-center bg-surface-200/20 rounded-md p-2">
                    <i class="pi pi-crown text-2xl! text-primary"></i>
                    <div class="font-bold">Site de l'URCA</div>
                    <Button severity="primary" size="small" icon="pi pi-external-link" label="Accéder"/>
                  </div>
                  <div class="w-full flex flex-col items-center justify-center bg-surface-200/20 rounded-md p-2">
                    <i class="pi pi-building-columns text-2xl! text-primary"></i>
                    <div class="font-bold">Site de l'IUT</div>
                    <Button severity="primary" size="small" icon="pi pi-external-link" label="Accéder"/>
                  </div>
                </div>
              </div>
              <div class="card w-1/3">
                <header class="card-header flex justify-between items-center w-full">
                  <div class="flex flex-col items-start">
                    <h2 class="m-0! text-xl!"><i class="pi pi-users text-primary"></i> Contacts</h2>
                  </div>
                  <Button severity="primary" size="small" icon="pi pi-plus" label="Voir plus"/>
                </header>
                <div class="card-body flex items-start justify-around gap-4">
                  <div class="w-full flex flex-col items-center justify-center">
                    <p class="uppercase text-xs font-bold mb-0! text-muted-color">département</p>
                    <div class="font-bold">Chef de département</div>
                    <div>
                      JOHN DOE
                    </div>
                    <div class="text-sm text-muted-color">
                      john.doe@univ-reims.fr
                    </div>
                  </div>
                  <div class="w-full flex flex-col items-center justify-center">
                    <p class="uppercase text-xs font-bold mb-0! text-muted-color">département</p>
                    <div class="font-bold">Support administratif</div>
                    <div>
                      JOHN DOE
                    </div>
                    <div class="text-sm text-muted-color">
                      john.doe@univ-reims.fr
                    </div>
                  </div>
                  <div class="w-full flex flex-col items-center justify-center">
                    <p class="uppercase text-xs font-bold mb-0! text-muted-color">établissement</p>
                    <div class="font-bold">Support technique</div>
                    <div>
                      CYNDEL HEROLT
                    </div>
                    <div class="text-sm text-muted-color">
                      cyndel.herolt@univ-reims.fr
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </section>
      </div>
    </div>
  </main>
</template>

<style scoped>
.dashboard-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  grid-auto-rows: 1fr;
  gap: 1rem;
  overflow-y: auto;
}
</style>
