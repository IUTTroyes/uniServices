<script setup>
import { useLayout } from './composables/layout.js';
import { computed, ref, watch, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import AppFooter from './AppFooter.vue';
import AppSidebar from './AppSidebar.vue';
import AppTopbar from './AppTopbar.vue';
import AppBreadcrumb from "./AppBreadcrumb.vue";
import { useSecurity } from '@stores';
import { bundles } from '../../../../packages/shell/assets/bundles-registry';

const props = defineProps({
  menuItems: {
    type: Array,
    required: false,
    default: () => []
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

const security = useSecurity();

// Helper to determine package name from route path
const getPackageFromPath = (path) => {
  const segments = path.split('/').filter(Boolean);
  if (segments.length === 0) return null;
  const first = segments[0];
  if (first === 'intranet') return 'intranet';
  if (first === 'stage') return 'stages';
  if (first === 'edt') return 'edt';
  if (first === 'helpdesk') return 'helpdesk';
  if (first === 'questionnaire') return 'questionnaire';
  if (first === 'unifolio') return 'unifolio';
  if (first === 'auth') return 'auth';
  return null;
};

const computedMenuItems = computed(() => {
  const currentRoute = useRoute();
  const pathPkg = getPackageFromPath(currentRoute.path);

  // Find the bundle manifest matching the current package name
  const activeBundle = bundles.find(b => b.name === pathPkg);
  if (!activeBundle || !activeBundle.menu) {
    // Fallback to props.menuItems if no bundle found
    if (props.menuItems && props.menuItems.length > 0) {
      return props.menuItems;
    }
    return [];
  }

  // Filter and return the menu items of the current package based on permissions
  const menuSections = Array.isArray(activeBundle.menu) ? activeBundle.menu : [activeBundle.menu];
  const assembled = [];

  menuSections.forEach(section => {
    const filteredItems = (section.items || []).filter(item => {
      if (item.permission) {
        return security.hasPermission(item.permission);
      }
      return true;
    });

    if (filteredItems.length > 0) {
      assembled.push({
        label: section.label,
        icon: section.icon,
        items: filteredItems
      });
    }
  });

  return assembled;
});
</script>

<template>
  <div class="layout-wrapper" :class="containerClass">
    <app-topbar :app-name :logo-url></app-topbar>
    <app-sidebar :menu-items="computedMenuItems"></app-sidebar>
    <div class="layout-main-container">
      <div class="flex justify-between items-center">
        <app-breadcrumb v-if="breadcrumbItems.length > 0" :items="breadcrumbItems"></app-breadcrumb>
        <div v-if="selectedAnneeUniversitaire && !selectedAnneeUniversitaire.isActif">
          <Message severity="error" class="absolute top-24 right-16 w-fit z-10" icon="pi pi-exclamation-triangle"><span class="font-bold">Attention !</span> Vous n'êtes pas sur l'année universitaire courante</Message>
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

<style scoped>

</style>
