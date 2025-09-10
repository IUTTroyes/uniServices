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
const rolesItems = ref([]);

const selectedAnneeUniversitaire = ref(null);

onMounted(async () => {
  selectedAnneeUniversitaire.value = localStorage.getItem('selectedAnneeUniv');
  await fetchData();
});

watch(() => userStore.user, async () => {
  await fetchData();
});

// Surveiller les changements du rôle temporaire pour mettre à jour l'interface utilisateur
watch(() => userStore.temporaryRole?.value, () => {
  // Mettre à jour l'état actif des éléments de rôle lorsque le rôle temporaire change
  if (rolesItems.value.length > 0) {
    const roleProperties = [
      { property: 'isPersonnel', label: 'Personnel' },
      { property: 'isEtudiant', label: 'Etudiant' },
      { property: 'isAssistant', label: 'Assistant' },
      { property: 'isQualite', label: 'Qualité' },
      { property: 'isCompta', label: 'Compta' },
      { property: 'isScolarite', label: 'Scolarité' },
      { property: 'isDirection', label: 'Direction' },
      { property: 'isChefDepartement', label: 'Chef Département' },
      { property: 'isRespParcours', label: 'Responsable Parcours' },
      { property: 'isDirecteurEtudes', label: 'Directeur Etudes' },
      { property: 'isAbsence', label: 'Absence' },
      { property: 'isNote', label: 'Note' },
      { property: 'isEdt', label: 'EDT' },
      { property: 'isStage', label: 'Stage' },
      { property: 'isRelaiComm', label: 'Relai Communication' },
      { property: 'isEdusign', label: 'Edusign' },
      { property: 'isSuperAdmin', label: 'Super Admin' }
    ];

    // Mettre à jour l'état actif de chaque élément de rôle en utilisant la même logique que le mappage initial
    roleProperties.forEach((role, index) => {
      if (index < rolesItems.value.length - 1) { // Ignorer l'option "Réinitialiser"
        const roleKey = role.property.toUpperCase().replace('IS', '');
        const roleValue = `ROLE_${roleKey}`;
        rolesItems.value[index].active = userStore.temporaryRole?.value ?
          (userStore.temporaryRole?.value === roleValue) :
          userStore[role.property];
      }
    });
  }
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

    if (userStore.user) {
      const roleProperties = [
        { property: 'isPersonnel', label: 'Personnel' },
        { property: 'isEtudiant', label: 'Etudiant' },
        { property: 'isAssistant', label: 'Assistant' },
        { property: 'isQualite', label: 'Qualité' },
        { property: 'isCompta', label: 'Compta' },
        { property: 'isScolarite', label: 'Scolarité' },
        { property: 'isDirection', label: 'Direction' },
        { property: 'isChefDepartement', label: 'Chef Département' },
        { property: 'isRespParcours', label: 'Responsable Parcours' },
        { property: 'isDirecteurEtudes', label: 'Directeur Etudes' },
        { property: 'isAbsence', label: 'Absence' },
        { property: 'isNote', label: 'Note' },
        { property: 'isEdt', label: 'EDT' },
        { property: 'isStage', label: 'Stage' },
        { property: 'isRelaiComm', label: 'Relai Communication' },
        { property: 'isEdusign', label: 'Edusign' },
        { property: 'isSuperAdmin', label: 'Super Admin' }
      ];

      // Fonction pour gérer la sélection des rôles
      const selectRole = (roleName, property) => {
        // Si le rôle est déjà actif en tant que rôle temporaire, le supprimer
        if (userStore.temporaryRole?.value === `ROLE_${property.toUpperCase().replace('IS', '')}`) {
          userStore.clearTemporaryRole();
        } else {
          // Sinon, le définir comme rôle temporaire
          userStore.setTemporaryRole(roleName);
        }
        console.log(userStore.user);
      };

      // Mapper les rôles aux éléments du menu avec l'état actif et la fonction de commande
      rolesItems.value = roleProperties.map(role => {
        const roleKey = role.property.toUpperCase().replace('IS', '');
        const roleValue = `ROLE_${roleKey}`;
        return {
          label: role.label,
          command: () => selectRole(role.label, role.property),
          // Ajouter la propriété active pour montrer quel rôle est actuellement actif
          // Si temporaryRole est défini, seul ce rôle sera actif
          // Si temporaryRole n'est pas défini, utiliser la propriété calculée
          active: userStore.temporaryRole?.value ? (userStore.temporaryRole?.value === roleValue) : userStore[role.property]
        };
      });

      // Ajouter une option "Réinitialiser le rôle" à la fin
      rolesItems.value.push({
        label: 'Réinitialiser le rôle',
        command: () => userStore.clearTemporaryRole(),
        icon: 'pi pi-refresh'
      });
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
  return userStore.applications.includes(item.name);
};

// Propriété calculée pour déterminer si le menu des rôles doit être affiché
const showRolesMenu = computed(() => {
  // Afficher le menu si l'utilisateur est un superAdmin ou a un rôle temporaire défini
  return userStore.isSuperAdmin || userStore.temporaryRole?.value !== null;
});

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

      <button v-if="showRolesMenu" type="button" class="layout-topbar-action-app" @click="toggleRolesMenu" aria-haspopup="true" aria-controls="roles_menu">
        <span>Rôles</span>
        <i class="pi pi-shield"></i>
      </button>
      <Menu ref="rolesMenu" id="roles_menu" :model="rolesItems" :popup="true" />
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
