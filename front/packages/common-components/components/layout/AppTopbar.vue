<script setup>
import {useLayout} from './composables/layout.js';
import {computed, onMounted, ref, watch} from 'vue';
import Logo from '@components/components/Logo.vue';
import {useAnneeUnivStore, useUsersStore} from "@stores";
import {useRoute, useRouter} from 'vue-router';
import {tools} from '@config/uniServices.js';
import noImage from "@images/photos_etudiants/noimage.png";

const anneeUnivStore = useAnneeUnivStore();
const userStore = useUsersStore();
const route = useRoute();
const router = useRouter();

const deptItems = ref([]);
const departementLabel = ref('');
const anneesUniv = ref([]);
const anneeItems = ref([
  {
    label: 'Années universitaires',
    items: []
  }
]);

const selectedAnneeUniversitaire = ref(null);

onMounted(async () => {
  selectedAnneeUniversitaire.value = localStorage.getItem('selectedAnneeUniv');
  await fetchData();
});

watch(() => userStore.user, async () => {
  await fetchData();
});

const fetchData = async () => {
  try {
    // Data is already fetched by initializeAppData, so we just use it
    // If anneesUniv is empty, fetch it (fallback)
    if (anneeUnivStore.anneesUniv.length === 0) {
      await anneeUnivStore.getAllAnneesUniv();
    }

    const sortedAnnees = anneeUnivStore.anneesUniv.map(annee => ({
      id: annee.id,
      label: annee.libelle,
      isActif: annee.actif,
      command: () => selectAnneeUniversitaire(annee),
    })).sort((a, b) => b.label.localeCompare(a.label));
    anneesUniv.value = sortedAnnees;
    anneeItems.value[0].items = sortedAnnees;

    // si selectedAnneeUniv dans le localStorage est un tableau vide on lance la méthode setSelectedAnneeUniv du store
    if (!selectedAnneeUniversitaire.value) {
    console.log(sortedAnnees[0]);
      await anneeUnivStore.setSelectedAnneeUniv(sortedAnnees[0]);
    }
    selectedAnneeUniversitaire.value = localStorage.getItem('selectedAnneeUniv');
    selectedAnneeUniversitaire.value = JSON.parse(selectedAnneeUniversitaire.value);
    selectedAnneeUniversitaire.value.label = selectedAnneeUniversitaire.value.libelle;

    if (userStore.user) {
      if (userStore.userType === 'personnels') {
        deptItems.value = userStore.departementsNotDefaut.map(departementPersonnel => ({
          label: departementPersonnel.libelle,
          id: departementPersonnel.id,
          command: () => changeDepartement(departementPersonnel.id)
        }));
        departementLabel.value = userStore.departementDefaut.libelle;
      } else {
        deptItems.value = [];
        departementLabel.value = userStore.departementDefaut.libelle;
      }
    }
  } catch (error) {
    console.error('Error fetching data:', error);
  }
};

const props = defineProps({
  appName: {
    type: String,
    required: true
  },
  logoUrl: {
    type: String,
    required: false
  },
});

const { onMenuToggle, toggleDarkMode, isDarkTheme } = useLayout();

const search = ref('');

const anneeMenu = ref();
const toolsMenu = ref();
const profileMenu = ref();
const deptMenu = ref();

const profileItems = ref([
  {
    label: 'Options',
    items: [
      {
        label: 'Profil',
        icon: 'pi pi-user',
        command: () => {
          router.push('/profil');
        }
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

const toggleProfileMenu = (event) => {
  profileMenu.value.toggle(event);
};

const toggleAnneeMenu = (event) => {
  anneeMenu.value.toggle(event);
};

const toggleToolsMenu = (event) => {
  toolsMenu.value.toggle(event);
};

const toggleDeptMenu = (event) => {
  deptMenu.value.toggle(event);
};

const changeDepartement = async (departementId) => {
  try {
    await userStore.changeDepartement(departementId);
    deptItems.value = userStore.departementsNotDefaut.map(departementPersonnel => ({
      label: departementPersonnel.libelle,
      id: departementPersonnel.id,
      command: () => changeDepartement(departementPersonnel.id)
    }));
    departementLabel.value = userStore.departementDefaut.libelle;
  } catch (error) {
    console.error('Error changing department:', error);
  }
};

const initiales = computed(() => {
  if (userStore.user && userStore.user.name) {
    return userStore.user.name.split(' ').map(n => n[0]).join('');
  }
  return '';
});

const isEnabled = (item) => {
  return userStore.applications.includes(item.name);
};

const selectAnneeUniversitaire = (annee) => {
  const selectedAnnee = {
    id: annee.id,
    label: annee.libelle,
    libelle: annee.libelle,
    isActif: annee.actif,
  };
  anneeUnivStore.setSelectedAnneeUniv(selectedAnnee);
  selectedAnneeUniversitaire.value = selectedAnnee;

  // recharger la page
  window.location.reload();
};
</script>

<template>
  <div class="layout-topbar">
    <div class="layout-topbar-logo-container">
      <button v-if="route.path !== '/portail'" class="layout-menu-button layout-topbar-action" @click="onMenuToggle">
        <i class="pi pi-bars"></i>
      </button>
      <router-link to="/" class="layout-topbar-logo">
        <Logo :logo-url="logoUrl" alt="logo" class="rounded-xl p-2"/> <span class="text-lg">{{appName}}</span>
      </router-link>
      <button v-if="userStore.userType === 'personnels'" type="button" class="layout-topbar-action-app" @click="toggleDeptMenu" aria-haspopup="true" aria-controls="dept_menu">
        <span>Département {{ departementLabel }}</span>
        <i class="pi pi-arrow-right-arrow-left"></i>
      </button>
      <div v-else-if="userStore.userType === 'etudiants'">
        <span>Département {{ departementLabel }}</span>
      </div>
      <Menu ref="deptMenu" id="dept_menu" :model="deptItems" :popup="true" />
    </div>

    <div class="layout-topbar-actions">
      <div v-if="route.path !== '/portail'" class="layout-topbar-search">
        <IconField>
          <InputIcon class="pi pi-search" />
          <InputText v-model="search" placeholder="Recherche" />
        </IconField>
      </div>

      <button v-if="route.path !== '/portail'" type="button" class="layout-topbar-action layout-topbar-action-text" @click="toggleToolsMenu" aria-haspopup="true" aria-controls="tools_menu">
        <i class="pi pi-microsoft text-primary"></i>
        <span>Applications</span>
      </button>
      <Menu ref="toolsMenu" id="tools_menu" :model="tools" :popup="true">
        <template #item="{ item, props }">
          <a v-if="item.url && isEnabled(item)" :href="item.url" v-ripple v-bind="props.action">
            <Logo :logo-url="item.logo" class="logo_menu" />
            <span class="ml-2">{{ item.name }}</span>
          </a>
        </template>
      </Menu>

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

          <button  v-if="route.path !== '/portail' && userStore.userType === 'personnels'" type="button" class="layout-topbar-action layout-topbar-action-text" @click="toggleAnneeMenu" aria-haspopup="true" aria-controls="annee_menu">
            <i class="pi pi-calendar"></i>
            <span>{{selectedAnneeUniversitaire?.label}}</span>
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
            <template v-if="userStore.userPhoto">
              <img :src="userStore.userPhoto" alt="photo de profil" class="rounded-full">
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
