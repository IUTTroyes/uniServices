<script setup>
import { ref, computed } from 'vue';
import { useUsersStore } from '@stores';
import ResponsableDashboardView from './Responsable/ResponsableDashboardView.vue';
import EnseignantDashboardView from './Enseignant/EnseignantDashboardView.vue';
import EtudiantDashboardView from './Etudiant/EtudiantDashboardView.vue';

const userStore = useUsersStore();

// Retrieve computed properties from store
const isEtudiant = computed(() => userStore.isEtudiant);
const isPersonnel = computed(() => userStore.isPersonnel);
const isSuperAdmin = computed(() => userStore.isSuperAdmin);

// We define Resp/Coordinator if they have ROLE_STAGE or similar admin permissions
const isCoordinator = computed(() => {
  if (userStore.temporaryRole) {
    return userStore.temporaryRole === 'ROLE_STAGE';
  }
  // Fallback to check if they have stage admin roles
  return userStore.user?.roles?.includes('ROLE_STAGE') || userStore.user?.roles?.includes('ROLE_CHEF_DEPARTEMENT') || userStore.isSuperAdmin;
});

// Roles list for the simulation switcher
const roles = [
  { label: 'Étudiant (Demandes & Dépôts)', role: 'ROLE_ETUDIANT', icon: 'pi pi-user', color: 'bg-emerald-500' },
  { label: 'Tuteur / Enseignant (Suivis)', role: 'ROLE_PERSONNEL', icon: 'pi pi-users', color: 'bg-blue-500' },
  { label: 'Responsable Stage / Alternance', role: 'ROLE_STAGE', icon: 'pi pi-shield', color: 'bg-violet-500' },
  { label: 'Super Admin (Modèles)', role: 'ROLE_SUPER_ADMIN', icon: 'pi pi-cog', color: 'bg-rose-500' }
];

const currentSimulatedRoleLabel = computed(() => {
  if (!userStore.temporaryRole) return 'Rôle Réel';
  const match = roles.find(r => r.role === userStore.temporaryRole);
  return match ? match.label : 'Rôle Personnalisé';
});

const selectRole = (roleKey) => {
  if (roleKey === 'REAL') {
    userStore.clearTemporaryRole();
    sessionStorage.removeItem('simulated_role');
  } else {
    userStore.setTemporaryRole(roleKey);
    sessionStorage.setItem('simulated_role', roleKey);
  }
  // Force refresh components
  window.location.reload();
};

const showSimulator = ref(false);
</script>

<template>
  <div class="relative min-h-screen">
    <!-- Render the active dashboard based on rights -->
    <ResponsableDashboardView v-if="isCoordinator || isSuperAdmin" />
    <EnseignantDashboardView v-else-if="isPersonnel" />
    <EtudiantDashboardView v-else-if="isEtudiant" />
    <div v-else class="p-8 text-center bg-white dark:bg-slate-900 rounded-3xl border border-slate-150 dark:border-slate-800">
      <div class="w-16 h-16 bg-red-50 dark:bg-red-950/20 text-red-500 rounded-full flex items-center justify-center mx-auto mb-4">
        <i class="pi pi-exclamation-triangle text-2xl"></i>
      </div>
      <h3 class="text-lg font-bold text-slate-850 dark:text-white">Accès non autorisé</h3>
      <p class="text-sm text-slate-500 dark:text-slate-400 mt-2">
        Votre profil ne dispose pas des droits nécessaires pour accéder à l'application de gestion des stages.
      </p>
    </div>

    <!-- Floating Role Simulator Widget (for demonstration/testing) -->
    <div class="fixed bottom-6 right-6 z-[9999]">
      <button
        @click="showSimulator = !showSimulator"
        class="w-12 h-12 rounded-full bg-violet-600 hover:bg-violet-700 text-white shadow-xl hover:scale-105 active:scale-95 transition-all duration-200 flex items-center justify-center border-0 cursor-pointer"
        title="Simulateur de Rôle (Démo)"
      >
        <i class="pi pi-cog text-xl"></i>
      </button>

      <div v-if="showSimulator" class="absolute bottom-16 right-0 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl p-4 shadow-2xl w-72 space-y-3 animate-fade-in">
        <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-700 pb-2">
          <span class="text-xs font-bold text-slate-850 dark:text-slate-200">Simulation de rôle</span>
          <span class="bg-violet-100 dark:bg-violet-950/40 text-violet-700 dark:text-violet-400 text-[10px] px-2 py-0.5 rounded font-mono font-bold">{{ currentSimulatedRoleLabel }}</span>
        </div>
        <div class="grid grid-cols-1 gap-2">
          <button v-for="r in roles" :key="r.role" @click="selectRole(r.role)" :class="[
            'p-2.5 rounded-xl flex items-center gap-3 border text-left text-xs transition-all duration-200 cursor-pointer',
            userStore.temporaryRole === r.role
              ? 'bg-violet-50 dark:bg-violet-950/40 text-violet-700 dark:text-violet-400 border-violet-250 dark:border-violet-850 font-semibold shadow-sm'
              : 'bg-white dark:bg-slate-800 border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700/55 text-slate-700 dark:text-slate-350'
          ]">
            <span :class="['w-2.5 h-2.5 rounded-full', r.color]"></span>
            <span class="flex-1 truncate">{{ r.label }}</span>
          </button>
          <button @click="selectRole('REAL')" :class="[
            'p-2.5 rounded-xl flex items-center justify-center gap-2 border text-xs font-semibold transition-all duration-200 cursor-pointer mt-1',
            !userStore.temporaryRole
              ? 'bg-slate-50 dark:bg-slate-700 text-slate-400 border-slate-200 dark:border-slate-600'
              : 'bg-rose-50 dark:bg-rose-950/20 border-rose-100 dark:border-rose-900/30 hover:bg-rose-100 text-rose-600 dark:text-rose-455'
          ]">
            <i class="pi pi-refresh"></i>
            <span>Rétablir mon rôle réel</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

