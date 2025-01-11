<script setup>
import { useLayout } from './composables/layout.js';
import { computed, onMounted, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import AppFooter from './AppFooter.vue';
import AppSidebar from './AppSidebar.vue';
import AppTopbar from './AppTopbar.vue';
import AppBreadcrumb from "./AppBreadcrumb.vue";

const props = defineProps({
  menuItems: {
    type: Array,
    required: true
  },
  logoUrl: {
    type: String,
    required: true
  },
  appName: {
    type: String,
    required: true
  },
  breadcrumbItems: {
    type: Array,
    required: false
  }
});

const { layoutConfig, layoutState, isSidebarActive, resetMenu } = useLayout();
const outsideClickListener = ref(null);
const route = useRoute();
const router = useRouter();
const showBackButton = ref(false);

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
    <app-topbar :logo-url :app-name></app-topbar>
    <app-sidebar :menu-items="menuItems"></app-sidebar>
    <div class="layout-main-container">
      <div class="flex justify-between">
        <app-breadcrumb v-if="breadcrumbItems" :items="breadcrumbItems"></app-breadcrumb>
        <Button v-if="showBackButton" @click="goBack" severity="primary" rounded icon="pi pi-arrow-left"></Button>
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
