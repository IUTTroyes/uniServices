<script setup>
import { useLayout } from './composables/layout.js';
import { defineProps, onMounted, watch, computed } from 'vue';
import { ref } from 'vue';
import { useUsersStore } from "common-stores";
import { useRoute } from 'vue-router';

const store = useUsersStore();
const route = useRoute();

const deptItems = ref([]);
const load = ref(false);
const departementLabel = ref('');

onMounted(async () => {
  await store.fetchUser();
  deptItems.value = store.departementsNotDefaut.map(departementPersonnel => ({
    label: departementPersonnel.departement.libelle,
    id: departementPersonnel.id,
    command: () => changeDepartement(departementPersonnel.id)
  }));

  departementLabel.value = store.departementDefaut.departement.libelle;
  load.value = true;
});

const props = defineProps({
  logoUrl: {
    type: String,
    required: true
  },
  appName: {
    type: String,
    required: true
  }
});

const { onMenuToggle, toggleDarkMode, isDarkTheme } = useLayout();

const search = ref('');

const anneeMenu = ref();
const profileMenu = ref();
const deptMenu = ref();

const profileItems = ref([
  {
    label: 'Options',
    items: [
      {
        label: 'Profil',
        icon: 'pi pi-user'
      },
      {
        label: 'Paramètres',
        icon: 'pi pi-cog'
      },
      {
        label: 'Déconnexion',
        icon: 'pi pi-sign-out',
        command: () => {
          localStorage.removeItem('token');
          document.cookie = 'token=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;';
          window.location.replace('http://localhost:3000/?logout=true');
        }
      }
    ]
  }
]);

const anneeItems = ref([
  {
    label: 'Années universitaires',
    items: [
      {
        label: '2023/2024',
      }
    ]
  }
]);

const toggleProfileMenu = (event) => {
  profileMenu.value.toggle(event);
};

const toggleAnneeMenu = (event) => {
  anneeMenu.value.toggle(event);
};

const toggleDeptMenu = (event) => {
  deptMenu.value.toggle(event);
};

const changeDepartement = async (departementId) => {
  try {
    await store.changeDepartement(departementId);
    departementLabel.value = store.departementDefaut.departement.libelle;
    deptItems.value = store.departementsNotDefaut.map(departementPersonnel => ({
      label: departementPersonnel.departement.libelle,
      id: departementPersonnel.id,
      command: () => changeDepartement(departementPersonnel.id)
    }));
  } catch (error) {
    console.error('Error changing department:', error);
  }
};

const initiales = computed(() => {
  if (store.user && store.user.name) {
    return store.user.name.split(' ').map(n => n[0]).join('');
  }
  return '';
});
</script>

<template>
  <div class="layout-topbar" v-if="load">
    <div class="layout-topbar-logo-container">
      <button v-if="route.path !== '/portail'" class="layout-menu-button layout-topbar-action" @click="onMenuToggle">
        <i class="pi pi-bars"></i>
      </button>
      <router-link to="/" class="layout-topbar-logo">
        <img :src="logoUrl" alt="logo" /> <span>{{appName}}</span>
      </router-link>
      <button type="button" class="layout-topbar-action-app" @click="toggleDeptMenu" aria-haspopup="true" aria-controls="dept_menu">
        <span>Département {{ departementLabel }}</span>
        <i class="pi pi-arrow-right-arrow-left"></i>
      </button>
      <Menu ref="deptMenu" id="dept_menu" :model="deptItems" :popup="true" />
    </div>

    <div class="layout-topbar-actions">
      <div v-if="route.path !== '/portail'" class="layout-topbar-search">
        <IconField>
          <InputIcon class="pi pi-search" />
          <InputText v-model="search" placeholder="Recherche" />
        </IconField>
      </div>

      <button v-if="route.path !== '/portail'" type="button" class="layout-topbar-action-app">
        <img src="@/assets/logo/logo_unifolio.png" alt="calendar" />
        <span>UniFolio</span>
      </button>
      <button v-if="route.path !== '/portail'" type="button" class="layout-topbar-action-app">
        <img src="@/assets/logo/logo_unifolio.png" alt="calendar" />
        <span>Correcto</span>
      </button>

      <div class="layout-config-menu">
        <button type="button" class="layout-topbar-action" @click="toggleDarkMode">
          <i :class="['pi', { 'pi-moon': isDarkTheme, 'pi-sun': !isDarkTheme }]"></i>
        </button>
      </div>

      <button
          class="layout-topbar-menu-button layout-topbar-action"
          v-styleclass="{ selector: '@next', enterFromClass: 'hidden', enterActiveClass: 'animate-scalein', leaveToClass: 'hidden', leaveActiveClass: 'animate-fadeout', hideOnOutsideClick: true }"

      >
        <i class="pi pi-ellipsis-v"></i>
      </button>

      <div class="layout-topbar-menu lg:block">
        <div class="layout-topbar-menu-content">

          <button v-if="route.path !== '/portail'" type="button" class="layout-topbar-action layout-topbar-action-text" @click="toggleAnneeMenu" aria-haspopup="true" aria-controls="annee_menu">
            <i class="pi pi-calendar"></i>
            <span>2024/2025</span>
          </button>
          <Menu ref="anneeMenu" id="annee_menu" :model="anneeItems" :popup="true" />

          <button type="button" class="layout-topbar-action">
            <i class="pi pi-inbox"></i>
            <span>Messages</span>
          </button>
          <button type="button" class="layout-topbar-action">
            <i class="pi pi-bell"></i>
            <span>Notifications</span>
          </button>
          <button type="button" class="layout-topbar-action layout-topbar-action-highlight"  @click="toggleProfileMenu" aria-haspopup="true" aria-controls="profile_menu">
            <template v-if="store.user.photoName">
              <img :src="store.user.photoName" alt="photo de profil" class="rounded-full">
            </template>
            <template v-else>
              <span class="text-gray-700 text-xl">{{ initiales }}</span>
            </template>
          </button>
          <Menu ref="profileMenu" id="profile_menu" :model="profileItems" :popup="true" />
        </div>
      </div>

    </div>
  </div>
</template>
