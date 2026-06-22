<script setup>
import { computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useUsersStore } from '@stores';

const userStore = useUsersStore();
const router = useRouter();

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

const navigateTo = (routeName) => {
  router.push({ name: routeName });
};
</script>

<template>
  <div class="space-y-6">

    <!-- Welcome Header -->
    <div
      class="bg-gradient-to-r from-violet-600 to-indigo-700 rounded-3xl p-8 text-white shadow-xl flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
      <div>
        <span class="bg-white/20 text-white text-xs font-semibold px-3 py-1 rounded-full uppercase tracking-wider">
          Portail Stages & Alternances
        </span>
        <h1 class="text-3xl font-extrabold mt-3 tracking-tight">
          Bonjour, {{ userStore.user?.prenom || 'Utilisateur' }} !
        </h1>
        <p class="text-indigo-100 mt-2 text-sm max-w-xl">
          Bienvenue sur l'application de gestion des stages et de l'alternance de l'IUT de Troyes. Accédez à vos outils
          selon votre profil.
        </p>
      </div>

      <!-- Quick Role Selector Box (for demonstration/testing) -->
      <div class="bg-white/10 backdrop-blur-md border border-white/20 rounded-2xl p-4 w-full md:w-auto min-w-[280px]">
        <div class="flex items-center justify-between gap-4 mb-2">
          <span class="text-xs font-medium text-indigo-200">Simulation de rôle (Démo)</span>
          <span class="bg-violet-400/30 text-white text-[10px] px-2 py-0.5 rounded font-mono font-bold">{{
            currentSimulatedRoleLabel }}</span>
        </div>
        <div class="grid grid-cols-2 gap-2 text-xs">
          <button v-for="r in roles" :key="r.role" @click="selectRole(r.role)" :class="[
            'p-2 rounded-lg flex items-center gap-2 border text-left transition-all duration-200',
            userStore.temporaryRole === r.role
              ? 'bg-white text-slate-900 border-white font-semibold shadow-md'
              : 'bg-white/5 border-white/10 hover:bg-white/10 text-white'
          ]">
            <i :class="[r.icon, userStore.temporaryRole === r.role ? 'text-violet-600' : 'text-white/70']"></i>
            <span class="truncate">{{ r.label.split(' ')[0] }}</span>
          </button>
          <button @click="selectRole('REAL')" :class="[
            'p-2 col-span-2 rounded-lg flex items-center justify-center gap-2 border transition-all duration-200',
            !userStore.temporaryRole
              ? 'bg-white text-slate-900 border-white font-semibold'
              : 'bg-white/5 border-white/10 hover:bg-white/10 text-white'
          ]">
            <i class="pi pi-refresh"></i>
            <span>Rétablir mon rôle réel</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Active Portals Selection Cards -->
    <div class="space-y-4">
      <h2 class="text-xl font-bold text-slate-800 dark:text-slate-100 flex items-center gap-2">
        <i class="pi pi-compass text-violet-600"></i>
        <span>Accéder à vos espaces</span>
      </h2>

      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">

        <!-- Student Card -->
        <div v-if="isEtudiant"
          class="group bg-white dark:bg-slate-800 border border-slate-100 dark:border-slate-700/60 rounded-3xl p-6 shadow-sm hover:shadow-lg hover:border-violet-300 dark:hover:border-violet-500/30 transition-all duration-300 flex flex-col justify-between">
          <div>
            <div
              class="w-12 h-12 rounded-2xl bg-emerald-50 dark:bg-emerald-500/10 flex items-center justify-center text-emerald-600 dark:text-emerald-400 group-hover:scale-110 transition-transform duration-300">
              <i class="pi pi-user text-xl"></i>
            </div>
            <h3 class="text-lg font-bold text-slate-900 dark:text-slate-100 mt-4">Espace Étudiant</h3>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-2">
              Déposez vos demandes de convention de stage, suivez les validations et déposez vos rapports.
            </p>
          </div>
          <button @click="navigateTo('ConventionRequest')"
            class="mt-6 w-full py-2.5 px-4 rounded-xl bg-slate-50 dark:bg-slate-700/40 hover:bg-violet-600 dark:hover:bg-violet-600 hover:text-white text-slate-700 dark:text-slate-300 font-semibold text-xs transition-all flex items-center justify-center gap-2">
            <span>Ouvrir mon espace</span>
            <i class="pi pi-arrow-right text-[10px]"></i>
          </button>
        </div>

        <!-- Teacher/Tutor Card -->
        <div v-if="isPersonnel"
          class="group bg-white dark:bg-slate-800 border border-slate-100 dark:border-slate-700/60 rounded-3xl p-6 shadow-sm hover:shadow-lg hover:border-violet-300 dark:hover:border-violet-500/30 transition-all duration-300 flex flex-col justify-between">
          <div>
            <div
              class="w-12 h-12 rounded-2xl bg-blue-50 dark:bg-blue-500/10 flex items-center justify-center text-blue-600 dark:text-blue-400 group-hover:scale-110 transition-transform duration-300">
              <i class="pi pi-users text-xl"></i>
            </div>
            <h3 class="text-lg font-bold text-slate-900 dark:text-slate-100 mt-4">Espace Tuteur / Enseignant</h3>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-2">
              Consultez vos étudiants en stage, complétez vos fiches de suivi et téléchargez les rapports.
            </p>
          </div>
          <button @click="navigateTo('EnseignantDashboard')"
            class="mt-6 w-full py-2.5 px-4 rounded-xl bg-slate-50 dark:bg-slate-700/40 hover:bg-violet-600 dark:hover:bg-violet-600 hover:text-white text-slate-700 dark:text-slate-300 font-semibold text-xs transition-all flex items-center justify-center gap-2">
            <span>Ouvrir mon espace</span>
            <i class="pi pi-arrow-right text-[10px]"></i>
          </button>
        </div>

        <!-- Coordinator Card -->
        <div v-if="isPersonnel"
          class="group bg-white dark:bg-slate-800 border border-slate-100 dark:border-slate-700/60 rounded-3xl p-6 shadow-sm hover:shadow-lg hover:border-violet-300 dark:hover:border-violet-500/30 transition-all duration-300 flex flex-col justify-between">
          <div>
            <div
              class="w-12 h-12 rounded-2xl bg-violet-50 dark:bg-violet-500/10 flex items-center justify-center text-violet-600 dark:text-violet-400 group-hover:scale-110 transition-transform duration-300">
              <i class="pi pi-shield text-xl"></i>
            </div>
            <h3 class="text-lg font-bold text-slate-900 dark:text-slate-100 mt-4">Espace Responsable</h3>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-2">
              Créez les périodes universitaires, pilotez le workflow des conventions et suivez l'avancement général.
            </p>
          </div>
          <button @click="navigateTo('ResponsableDashboard')"
            class="mt-6 w-full py-2.5 px-4 rounded-xl bg-slate-50 dark:bg-slate-700/40 hover:bg-violet-600 dark:hover:bg-violet-600 hover:text-white text-slate-700 dark:text-slate-300 font-semibold text-xs transition-all flex items-center justify-center gap-2">
            <span>Ouvrir mon espace</span>
            <i class="pi pi-arrow-right text-[10px]"></i>
          </button>
        </div>

        <!-- Super Admin Card -->
        <div v-if="isSuperAdmin"
          class="group bg-white dark:bg-slate-800 border border-slate-100 dark:border-slate-700/60 rounded-3xl p-6 shadow-sm hover:shadow-lg hover:border-violet-300 dark:hover:border-violet-500/30 transition-all duration-300 flex flex-col justify-between">
          <div>
            <div
              class="w-12 h-12 rounded-2xl bg-rose-50 dark:bg-rose-500/10 flex items-center justify-center text-rose-600 dark:text-rose-400 group-hover:scale-110 transition-transform duration-300">
              <i class="pi pi-cog text-xl"></i>
            </div>
            <h3 class="text-lg font-bold text-slate-900 dark:text-slate-100 mt-4">Modèles de Convention</h3>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-2">
              Éditez le modèle de convention de stage dynamique utilisé pour générer les documents PDF.
            </p>
          </div>
          <button @click="navigateTo('TemplateEditor')"
            class="mt-6 w-full py-2.5 px-4 rounded-xl bg-slate-50 dark:bg-slate-700/40 hover:bg-violet-600 dark:hover:bg-violet-600 hover:text-white text-slate-700 dark:text-slate-300 font-semibold text-xs transition-all flex items-center justify-center gap-2">
            <span>Ouvrir mon espace</span>
            <i class="pi pi-arrow-right text-[10px]"></i>
          </button>
        </div>

      </div>
    </div>

    <!-- Quick Info Section / Helpdesk integration suggestion -->
    <div class="bg-slate-50 dark:bg-slate-800/40 rounded-3xl p-6 border border-slate-100 dark:border-slate-700/30">
      <div class="flex items-start gap-4">
        <div
          class="w-10 h-10 rounded-xl bg-violet-100 dark:bg-violet-500/10 flex items-center justify-center text-violet-600 shrink-0">
          <i class="pi pi-info-circle text-lg"></i>
        </div>
        <div>
          <h4 class="text-sm font-bold text-slate-900 dark:text-slate-100">Besoin d'aide ?</h4>
          <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 leading-relaxed">
            Pour toute question concernant le processus de validation de votre convention de stage ou alternance,
            veuillez contacter le secrétariat de votre département. Pour les soucis techniques, créez un ticket sur
            l'application <a href="/helpdesk" class="text-violet-600 hover:underline font-semibold">Helpdesk</a>.
          </p>
        </div>
      </div>
    </div>

  </div>
</template>

<style scoped>
.animate-fade-in {
  animation: fadeIn 0.4s ease-out;
}

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(8px);
  }

  to {
    opacity: 1;
    transform: translateY(0);
  }
}
</style>
