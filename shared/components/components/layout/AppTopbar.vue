<script setup>
import { useLayout } from './composables/layout.js';
import { computed, onMounted, ref, watch } from 'vue';
import { AVAILABLE_ROLES } from "@utils/permissions";
import Logo from '@components/components/Logo.vue';
import { useAnneeUnivStore, useUsersStore } from "@stores";
import { useRoute, useRouter } from 'vue-router';
import { tools } from '@config/uniServices.js';
import noImage from "@images/photos_etudiants/noimage.png";
import { PermissionGuard } from "@components";
import ApplicationBadge from '../Application/ApplicationBadge.vue';
import api from '@helpers/axios';

const anneeUnivStore = useAnneeUnivStore();
const userStore = useUsersStore();
const route = useRoute();
const router = useRouter();

const hasError = ref(false);

const deptItems = ref([]);
const departementLabel = ref('');
const anneesUniv = ref([]);
const anneeItems = ref([
  {
    label: 'Années universitaires',
    items: []
  }
]);
const selectableRoles = AVAILABLE_ROLES.filter(role => !['SUPER_ADMIN', 'ROLE_SUPER_ADMIN'].includes(role.role));
const selectedRoles = computed({
  get: () => userStore.temporaryRoles ?? [],
  set: roles => userStore.setTemporaryRoles(roles),
});

const selectedAnneeUniversitaire = ref(null);
const unreadMessagesCount = ref(0);

const fetchUnreadMessages = async () => {
  if (!userStore.user) return;
  try {
    const res = await api.get('/api/messages/unread-count');
    unreadMessagesCount.value = res.data?.unreadCount ?? 0;
  } catch (_) {
    // Ignore silensieusement si non authentifié ou indisponible
  }
};

const goToMessages = () => {
  router.push('/message');
};

onMounted(async () => {
  selectedAnneeUniversitaire.value = localStorage.getItem('selectedAnneeUniv');
  await fetchData();
  await fetchUnreadMessages();
});

watch(() => userStore.user, async () => {
  await fetchData();
  await fetchUnreadMessages();
});

const fetchData = async () => {
  try {
    // Les données sont déjà récupérées par initializeAppData, donc on les utilise simplement
    // Si anneesUniv est vide, on le récupère (solution de repli)
    if (anneeUnivStore.anneesUniv.length === 0) {
      await anneeUnivStore.getAllAnneesUniv();
    }

    // Préparer les années universitaires triées pour le menu déroulant
    const sortedAnnees = anneeUnivStore.anneesUniv.map(annee => ({
      id: annee.id,
      label: annee.libelle,
      isActif: annee.actif,
      command: () => selectAnneeUniversitaire(annee),
    })).sort((a, b) => b.label.localeCompare(a.label));
    anneesUniv.value = sortedAnnees;
    anneeItems.value[0].items = sortedAnnees;

    // Gérer l'année universitaire sélectionnée
    if (!selectedAnneeUniversitaire.value) {
      // Si aucune année n'est sélectionnée dans l'état local, utiliser celle du store ou définir la première
      if (anneeUnivStore.selectedAnneeUniv) {
        selectedAnneeUniversitaire.value = anneeUnivStore.selectedAnneeUniv;
      } else if (sortedAnnees.length > 0) {
        await anneeUnivStore.setSelectedAnneeUniv(sortedAnnees[0]);
        selectedAnneeUniversitaire.value = sortedAnnees[0];
      }
    } else {
      // Analyser l'année sélectionnée depuis localStorage
      selectedAnneeUniversitaire.value = JSON.parse(selectedAnneeUniversitaire.value);
      // S'assurer qu'elle a une propriété label
      if (selectedAnneeUniversitaire.value && selectedAnneeUniversitaire.value.libelle) {
        selectedAnneeUniversitaire.value.label = selectedAnneeUniversitaire.value.libelle;
      }
    }

    // Gérer les données des départements pour l'interface utilisateur
    if (userStore.user) {
      if (userStore.userType === 'personnels') {
        // Mapper les départements pour le menu déroulant
        deptItems.value = Array.isArray(userStore.departementsNotDefaut)
          ? userStore.departementsNotDefaut.map(departement => ({
            label: departement.libelle,
            id: departement.id,
            command: () => changeDepartement(departement.id)
          }))
          : [];

        // Définir le libellé du département par défaut
        departementLabel.value = userStore.departementDefaut?.libelle || '';
      } else {
        // Pour les utilisateurs non-personnel
        deptItems.value = [];
        departementLabel.value = userStore.departementDefaut?.libelle || '';
      }
    }
  } catch (error) {
    hasError.value = true;
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
const rolesMenu = ref();

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
          localStorage.removeItem('selectedAnneeUniv');
          document.cookie = 'token=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;';
          window.location.replace(`${window.location.origin}/?logout=true`);
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

const toggleRolesMenu = (event) => {
  rolesMenu.value.toggle(event);
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
    hasError.value = true;
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
  return item.urlSlug === 'intranet' || userStore.applications.includes(item.urlSlug);
};

const activeTools = computed(() => {
  return (tools || []).filter(item => isEnabled(item));
});

// L'accès au simulateur dépend des droits réels, même pendant une simulation vide.
const showRolesMenu = computed(() => userStore.isPersonnel && userStore.rolesActifs.includes('SUPER_ADMIN'));

const selectAnneeUniversitaire = (annee) => {
  // Passer l'objet annee original au store
  // Le store gérera la définition correcte de la propriété isActif
  anneeUnivStore.setSelectedAnneeUniv(annee);

  // Mettre à jour la valeur locale selectedAnneeUniversitaire avec la valeur du store
  selectedAnneeUniversitaire.value = anneeUnivStore.selectedAnneeUniv;

  // recharger la page
  window.location.reload();
};
</script>

<template>
  <div class="layout-topbar">
    <div class="layout-topbar-logo-container">
      <button v-if="route.name !== 'portail'" class="layout-menu-button layout-topbar-action" @click="onMenuToggle">
        <i class="pi pi-bars"></i>
      </button>

      <router-link to="/" class="layout-topbar-logo">
        <Logo :logo-url="logoUrl" alt="logo" class="rounded-xl p-2" /> <span class="text-lg">{{ appName }}</span>
      </router-link>
    </div>

    <div v-if="route.name !== 'portail'" class="layout-topbar-search hidden lg:block">
      <IconField>
        <InputIcon class="pi pi-search" />
        <InputText v-model="search" placeholder="Recherche" />
      </IconField>
    </div>

    <div class="layout-topbar-actions">
      <div v-if="route.name !== 'portail'" class="layout-topbar-search lg:hidden">
        <IconField>
          <InputIcon class="pi pi-search" />
          <InputText v-model="search" placeholder="Recherche" />
        </IconField>
      </div>

      <button class="layout-topbar-menu-button layout-topbar-action"
        v-styleclass="{ selector: '@next', enterFromClass: 'hidden', enterActiveClass: 'animate-scalein', leaveToClass: 'hidden', leaveActiveClass: 'animate-fadeout', hideOnOutsideClick: true }">
        <i class="pi pi-ellipsis-v"></i>
      </button>

      <div class="layout-topbar-menu lg:block">
        <div class="layout-topbar-menu-content">
          <router-link :to="{ name: 'portail' }" v-if="route.name !== 'portail'"
            class="layout-topbar-action layout-topbar-action-text">
            <i class="pi pi-arrow-left text-primary"></i>
            <span>Portail</span>
          </router-link>

          <button v-if="route.name !== 'portail'" type="button" class="layout-topbar-action layout-topbar-action-text"
            @click="toggleToolsMenu" aria-haspopup="true" aria-controls="tools_menu">
            <i class="pi pi-box text-primary"></i>
            <span>Applications</span>
          </button>
          <Menu ref="toolsMenu" id="tools_menu" :model="activeTools" :popup="true">
            <template #item="{ item, props }">
              <a v-if="item.url && isEnabled(item)" :href="item.url" v-ripple v-bind="props.action" class="flex items-center w-full">
                <ApplicationBadge :app="item.urlSlug || item" show-icon full-width />
              </a>
            </template>
          </Menu>

          <button v-if="userStore.userType === 'personnels'" type="button"
            class="layout-topbar-action layout-topbar-action-text" @click="toggleDeptMenu" aria-haspopup="true"
            aria-controls="dept_menu">
            <i class="pi pi-arrow-right-arrow-left text-primary"></i>
            <span>{{ departementLabel }}</span>
          </button>
          <div v-else-if="userStore.userType === 'etudiants'" class="hidden lg:inline-flex">
            <span>{{ departementLabel }}</span>
          </div>
          <Menu ref="deptMenu" id="dept_menu" :model="deptItems" :popup="true" />

          <button v-if="showRolesMenu" type="button" class="layout-topbar-action layout-topbar-action-text"
            @click="toggleRolesMenu" aria-haspopup="true" aria-controls="roles_menu">
            <i class="pi pi-shield text-primary"></i>
            <span>{{ userStore.isSimulatingRoles ? `Rôles (${selectedRoles.length})` : 'Rôles' }}</span>
          </button>
          <Popover v-if="showRolesMenu" ref="rolesMenu" id="roles_menu">
            <div class="w-80 max-w-[calc(100vw-3rem)] flex flex-col gap-3">
              <div>
                <p class="m-0! font-semibold">Simuler des rôles</p>
                <p class="mt-1! mb-0! text-sm text-muted-color">Cumulez les fonctions pour vérifier les accès affichés. Les droits enregistrés restent inchangés.</p>
              </div>
              <p class="m-0! text-xs text-muted-color" aria-live="polite">{{ userStore.isSimulatingRoles ? `${selectedRoles.length} rôle(s) simulé(s)` : 'Droits réels du compte actifs' }}</p>
              <div class="max-h-80 overflow-y-auto flex flex-col gap-1" role="group" aria-label="Rôles à simuler">
                <label v-for="role in selectableRoles" :key="role.role" :for="`simulate-${role.role}`" class="flex items-center gap-3 p-2 rounded-lg hover:bg-surface-100 dark:hover:bg-surface-800 cursor-pointer">
                  <Checkbox v-model="selectedRoles" :input-id="`simulate-${role.role}`" :value="role.role" />
                  <span class="text-sm">{{ role.label }}</span>
                </label>
              </div>
              <p v-if="userStore.isSimulatingRoles && !selectedRoles.length" class="m-0! text-xs text-muted-color">Aucun rôle métier simulé. Le type de compte reste inchangé.</p>
              <Button label="Rétablir mes droits réels" icon="pi pi-refresh" severity="secondary" size="small" :disabled="!userStore.isSimulatingRoles" @click="userStore.clearTemporaryRole()" />
            </div>
          </Popover>

          <PermissionGuard permission="isPersonnel">
            <button type="button" class="layout-topbar-action layout-topbar-action-text" @click="toggleAnneeMenu"
              aria-haspopup="true" aria-controls="annee_menu">
              <i class="pi pi-calendar text-primary"></i>
              <span>{{ selectedAnneeUniversitaire?.label }}</span>
            </button>
            <Menu ref="anneeMenu" id="annee_menu" :model="anneeItems" :popup="true" />
          </PermissionGuard>

          <button
            type="button"
            class="layout-topbar-action relative"
            @click="goToMessages"
            aria-label="Messagerie interne"
          >
            <i class="pi pi-inbox"></i>
            <span
              v-if="unreadMessagesCount > 0"
              class="absolute -top-1 -right-1 bg-red-500 text-white text-[10px] font-bold rounded-full h-4 min-w-4 px-1 flex items-center justify-center pointer-events-none"
            >
              {{ unreadMessagesCount > 99 ? '99+' : unreadMessagesCount }}
            </span>
            <span>Messages</span>
          </button>
          <button type="button" class="layout-topbar-action">
            <i class="pi pi-bell"></i>
            <span>Notifications</span>
          </button>
          <div class="layout-config-menu">
            <button type="button" class="layout-topbar-action" @click="toggleDarkMode">
              <i :class="['pi', { 'pi-moon': isDarkTheme, 'pi-sun': !isDarkTheme }]"></i>
            </button>
          </div>
          <Button severity="secondary" rounded @click="toggleProfileMenu" aria-haspopup="true"
            aria-controls="profile_menu" class="layout-topbar-action p-0!">
            <template v-if="userStore.userPhoto">
              <img :src="userStore.userPhoto" alt="photo de profil" class="rounded-full max-w-12 mx-auto">
            </template>
            <template v-else>
              <span class="text-gray-700 text-xl">{{ initiales }}</span>
            </template>
          </Button>
          <Menu ref="profileMenu" id="profile_menu" :model="profileItems" :popup="true" />
        </div>
      </div>

    </div>
  </div>
</template>
