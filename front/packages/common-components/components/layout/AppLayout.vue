<script setup>
import { useLayout } from './composables/layout.js';
import { computed, ref, watch, onMounted } from 'vue';
import AppFooter from './AppFooter.vue';
import AppSidebar from './AppSidebar.vue';
import AppTopbar from './AppTopbar.vue';
import AppBreadcrumb from "./AppBreadcrumb.vue";
import { useRoute, useRouter } from 'vue-router';

const props = defineProps({
  menuItems: {
    type: Array,
    required: true
  },
  appName: {
    type: String,
    required: true
  },
  breadcrumbItems: {
    type: Array,
    required: false
  },
  logoUrl: {
    type: String,
    required: false
  }
});

const { layoutConfig, layoutState, isSidebarActive, resetMenu } = useLayout();
const outsideClickListener = ref(null);
const route = useRoute();
const router = useRouter();
const showBackButton = ref(false);
const selectedAnneeUniversitaire = computed(
  () => {
    const selectedAnnee = localStorage.getItem('selectedAnneeUniv');
    return selectedAnnee ? JSON.parse(selectedAnnee) : null;
  }
)

const updateBackButtonVisibility = (path) => {
  const segments = path.split('/').filter(Boolean);
  showBackButton.value = segments.length >= 2;
};

onMounted(() => {
  updateBackButtonVisibility(route.path);
});

watch(route, (newRoute) => {
  updateBackButtonVisibility(newRoute.path);
});

const containerClass = computed(() => {
  return {
    'layout-overlay': layoutConfig.menuMode === 'overlay',
    'layout-static': layoutConfig.menuMode === 'static',
    'layout-static-inactive': layoutState.staticMenuDesktopInactive && layoutConfig.menuMode === 'static',
    'layout-overlay-active': layoutState.overlayMenuActive,
    'layout-mobile-active': layoutState.staticMenuMobileActive
  };
});

function bindOutsideClickListener() {
  if (!outsideClickListener.value) {
    outsideClickListener.value = (event) => {
      if (isOutsideClicked(event)) {
        resetMenu();
      }
    };
    document.addEventListener('click', outsideClickListener.value);
  }
}

function unbindOutsideClickListener() {
  if (outsideClickListener.value) {
    document.removeEventListener('click', outsideClickListener);
    outsideClickListener.value = null;
  }
}

function isOutsideClicked(event) {
  const sidebarEl = document.querySelector('.layout-sidebar');
  const topbarEl = document.querySelector('.layout-menu-button');

  return !(sidebarEl.isSameNode(event.target) || sidebarEl.contains(event.target) || topbarEl.isSameNode(event.target) || topbarEl.contains(event.target));
}

function goBack() {
  router.back();
}
</script>

<template>
  <div class="layout-wrapper" :class="containerClass">
    <app-topbar :app-name :logo-url></app-topbar>
    <app-sidebar :menu-items="menuItems"></app-sidebar>
    <div class="layout-main-container">
      <div class="flex justify-between items-center">
        <app-breadcrumb v-if="breadcrumbItems" :items="breadcrumbItems"></app-breadcrumb>
        <Button v-if="showBackButton" @click="goBack" severity="contrast" label="Retour" size="small" icon="pi pi-arrow-left" class="h-fit"></Button>

        <div v-if="!selectedAnneeUniversitaire.isActif">
          <Message severity="error" class="absolute top-24 right-16 w-fit z-10" icon="pi pi-exclamation-triangle"><span class="font-bold">Attention !</span> Vous n'êtes pas sur l'année universitaire actuelle</Message>
        </div>
      </div>
      <div class="layout-main">
        <router-view></router-view>
      </div>
      <app-footer></app-footer>
    </div>
    <div class="layout-mask animate-fadein"></div>
  </div>
  <Toast />
</template>
