<script setup>
import { useLayout } from './composables/layout.js';
import { computed, ref, watch, onMounted } from 'vue';
import AppFooter from './AppFooter.vue';
import AppSidebar from './AppSidebar.vue';
import AppTopbar from './AppTopbar.vue';
import AppBreadcrumb from "./AppBreadcrumb.vue";

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
const selectedAnneeUniversitaire = computed(
  () => {
    const selectedAnnee = localStorage.getItem('selectedAnneeUniv');
    return selectedAnnee ? JSON.parse(selectedAnnee) : null;
  }
)

const containerClass = computed(() => {
  return {
    'layout-overlay': layoutConfig.menuMode === 'overlay',
    'layout-static': layoutConfig.menuMode === 'static',
    'layout-static-inactive': layoutState.staticMenuDesktopInactive && layoutConfig.menuMode === 'static',
    'layout-overlay-active': layoutState.overlayMenuActive,
    'layout-mobile-active': layoutState.staticMenuMobileActive
  };
});
</script>

<template>
  <div class="layout-wrapper" :class="containerClass">
    <app-topbar :app-name :logo-url></app-topbar>
    <app-sidebar :menu-items="menuItems"></app-sidebar>
    <div class="layout-main-container">
      <div class="flex justify-between items-center">
        <app-breadcrumb v-if="breadcrumbItems" :items="breadcrumbItems"></app-breadcrumb>
        <div v-if="selectedAnneeUniversitaire && !selectedAnneeUniversitaire.isActif">
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
