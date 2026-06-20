<script setup>
import { ref, computed } from 'vue';
import { useRouter } from 'vue-router';
import { useToast } from 'primevue/usetoast';

const router = useRouter();
const toast = useToast();

const currentStep = ref(1);
const totalSteps = 4;

// Form fields state
const form = ref({
  // Step 1: Student / Period
  period: 'BUT3',
  phone: '',
  emailPerso: '',
  insuranceCompany: '',
  insurancePolicyNumber: '',
  
  // Step 2: Company
  companyName: '',
  companySiret: '',
  companyAddress: '',
  companyPhone: '',
  signatoryName: '',
  signatoryTitle: '',
  
  // Step 3: Internship Details
  startDate: '',
  endDate: '',
  weeklyHours: 35,
  salaryAmount: 4.35, // hourly compensation rate
  subject: '',
  supervisorName: '',
  supervisorEmail: '',
});

// Step labels
const steps = [
  { id: 1, label: 'Profil & Assurances', icon: 'pi pi-user' },
  { id: 2, label: 'Entreprise d\'accueil', icon: 'pi pi-briefcase' },
  { id: 3, label: 'Dates & Mission', icon: 'pi pi-file-edit' },
  { id: 4, label: 'Vérification', icon: 'pi pi-check-circle' }
];

const nextStep = () => {
  if (currentStep.value < totalSteps) {
    currentStep.value++;
  }
};

const prevStep = () => {
  if (currentStep.value > 1) {
    currentStep.value--;
  }
};

const isSubmitting = ref(false);

const submitRequest = () => {
  isSubmitting.value = true;
  
  setTimeout(() => {
    isSubmitting.value = false;
    toast.add({
      severity: 'success',
      summary: 'Demande soumise',
      detail: 'Votre demande de convention a été enregistrée pour validation.',
      life: 5000
    });
    
    // Redirect back to student portal
    router.push({ name: 'EtudiantDashboard' });
  }, 1500);
};

const cancelRequest = () => {
  router.push({ name: 'EtudiantDashboard' });
};
</script>

<template>
  <div class="mx-auto space-y-6">
    <Toast />

    <!-- Top Header -->
    <div class="flex items-center gap-4 justify-between border-b border-slate-100 dark:border-slate-800 pb-5">
      <div>
        <h1 class="text-2xl font-black text-slate-900 dark:text-white flex items-center gap-2">
          <i class="pi pi-file-edit text-violet-600"></i>
          <span>Demande de Convention de Stage</span>
        </h1>
        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
          Remplissez les informations nécessaires pour la signature de votre convention de stage.
        </p>
      </div>
      <button 
        @click="cancelRequest"
        class="text-xs font-semibold px-4 py-2 border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-800 transition-all"
      >
        Annuler
      </button>
    </div>

    <!-- Stepper Navigation Bar -->
    <div class="grid grid-cols-4 gap-4 relative">
      <div 
        v-for="step in steps" 
        :key="step.id"
        :class="[
          'flex flex-col items-center text-center p-3 rounded-2xl transition-all duration-300',
          currentStep === step.id 
            ? 'bg-violet-50 dark:bg-violet-950/20 text-violet-700 dark:text-violet-400' 
            : currentStep > step.id 
              ? 'text-emerald-600 dark:text-emerald-400' 
              : 'text-slate-400 dark:text-slate-600'
        ]"
      >
        <div 
          :class="[
            'w-10 h-10 rounded-full flex items-center justify-center font-bold text-xs border-2 mb-2 transition-all',
            currentStep === step.id 
              ? 'border-violet-600 bg-violet-600 text-white' 
              : currentStep > step.id 
                ? 'border-emerald-500 bg-emerald-500 text-white' 
                : 'border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-500'
          ]"
        >
          <i v-if="currentStep > step.id" class="pi pi-check text-xs"></i>
          <span v-else>{{ step.id }}</span>
        </div>
        <span class="text-[10px] font-bold uppercase tracking-wider hidden md:block">{{ step.label }}</span>
      </div>
    </div>

    <!-- Main Wizard Card -->
    <div class="bg-white dark:bg-slate-800 border border-slate-100 dark:border-slate-700/60 rounded-3xl p-8 shadow-sm">
      
      <!-- Step 1: Student Information -->
      <div v-if="currentStep === 1" class="space-y-6 animate-slide-in">
        <h3 class="text-base font-bold text-slate-800 dark:text-slate-100 border-b border-slate-50 dark:border-slate-700/40 pb-2">
          Étape 1 : Coordonnées de l'étudiant & Assurances
        </h3>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div class="flex flex-col gap-2">
            <label class="text-xs font-bold text-slate-600 dark:text-slate-300">Période du parcours universitaire</label>
            <select v-model="form.period" class="w-full p-3 border border-slate-200 dark:border-slate-700 rounded-xl bg-slate-50 dark:bg-slate-900 text-xs font-semibold text-slate-800 dark:text-slate-200">
              <option value="BUT1">BUT 1 - Stage d'initiation (4 semaines)</option>
              <option value="BUT2">BUT 2 - Stage technique (8 semaines)</option>
              <option value="BUT3">BUT 3 - Stage de fin d'études (16 semaines)</option>
            </select>
          </div>

          <div class="flex flex-col gap-2">
            <label class="text-xs font-bold text-slate-600 dark:text-slate-300">Téléphone de l'étudiant</label>
            <input type="text" v-model="form.phone" placeholder="Ex: 06 12 34 56 78" class="w-full p-3 border border-slate-200 dark:border-slate-700 rounded-xl bg-slate-50 dark:bg-slate-900 text-xs text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-violet-500" />
          </div>

          <div class="flex flex-col gap-2">
            <label class="text-xs font-bold text-slate-600 dark:text-slate-300">Adresse e-mail personnelle (de secours)</label>
            <input type="email" v-model="form.emailPerso" placeholder="Ex: etudiant@gmail.com" class="w-full p-3 border border-slate-200 dark:border-slate-700 rounded-xl bg-slate-50 dark:bg-slate-900 text-xs text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-violet-500" />
          </div>

          <div class="flex flex-col gap-2">
            <label class="text-xs font-bold text-slate-600 dark:text-slate-300">Compagnie d'assurance responsabilité civile</label>
            <input type="text" v-model="form.insuranceCompany" placeholder="Ex: MAIF, MACIF, MAAF..." class="w-full p-3 border border-slate-200 dark:border-slate-700 rounded-xl bg-slate-50 dark:bg-slate-900 text-xs text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-violet-500" />
          </div>

          <div class="flex flex-col gap-2 md:col-span-2">
            <label class="text-xs font-bold text-slate-600 dark:text-slate-300">Numéro de police d'assurance civile</label>
            <input type="text" v-model="form.insurancePolicyNumber" placeholder="Ex: 9876543-A" class="w-full p-3 border border-slate-200 dark:border-slate-700 rounded-xl bg-slate-50 dark:bg-slate-900 text-xs text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-violet-500" />
          </div>
        </div>
      </div>

      <!-- Step 2: Company Details -->
      <div v-if="currentStep === 2" class="space-y-6 animate-slide-in">
        <h3 class="text-base font-bold text-slate-800 dark:text-slate-100 border-b border-slate-50 dark:border-slate-700/40 pb-2">
          Étape 2 : Entreprise d'accueil
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div class="flex flex-col gap-2">
            <label class="text-xs font-bold text-slate-600 dark:text-slate-300">Raison sociale (Nom de l'entreprise)</label>
            <input type="text" v-model="form.companyName" placeholder="Ex: Google France" class="w-full p-3 border border-slate-200 dark:border-slate-700 rounded-xl bg-slate-50 dark:bg-slate-900 text-xs text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-violet-500" />
          </div>

          <div class="flex flex-col gap-2">
            <label class="text-xs font-bold text-slate-600 dark:text-slate-300">Numéro SIRET (14 chiffres)</label>
            <input type="text" v-model="form.companySiret" placeholder="Ex: 12345678900010" class="w-full p-3 border border-slate-200 dark:border-slate-700 rounded-xl bg-slate-50 dark:bg-slate-900 text-xs text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-violet-500" />
          </div>

          <div class="flex flex-col gap-2 md:col-span-2">
            <label class="text-xs font-bold text-slate-600 dark:text-slate-300">Adresse postale du lieu de stage</label>
            <input type="text" v-model="form.companyAddress" placeholder="Ex: 8 Rue Kléber, 75016 Paris" class="w-full p-3 border border-slate-200 dark:border-slate-700 rounded-xl bg-slate-50 dark:bg-slate-900 text-xs text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-violet-500" />
          </div>

          <div class="flex flex-col gap-2">
            <label class="text-xs font-bold text-slate-600 dark:text-slate-300">Téléphone standard entreprise</label>
            <input type="text" v-model="form.companyPhone" placeholder="Ex: 01 02 03 04 05" class="w-full p-3 border border-slate-200 dark:border-slate-700 rounded-xl bg-slate-50 dark:bg-slate-900 text-xs text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-violet-500" />
          </div>

          <div class="flex flex-col gap-2">
            <label class="text-xs font-bold text-slate-600 dark:text-slate-300">Nom du représentant signataire</label>
            <input type="text" v-model="form.signatoryName" placeholder="Ex: Mme. Sylvie Martin" class="w-full p-3 border border-slate-200 dark:border-slate-700 rounded-xl bg-slate-50 dark:bg-slate-900 text-xs text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-violet-500" />
          </div>

          <div class="flex flex-col gap-2 md:col-span-2">
            <label class="text-xs font-bold text-slate-600 dark:text-slate-300">Fonction du représentant signataire</label>
            <input type="text" v-model="form.signatoryTitle" placeholder="Ex: Directrice des Ressources Humaines" class="w-full p-3 border border-slate-200 dark:border-slate-700 rounded-xl bg-slate-50 dark:bg-slate-900 text-xs text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-violet-500" />
          </div>
        </div>
      </div>

      <!-- Step 3: Internship Details -->
      <div v-if="currentStep === 3" class="space-y-6 animate-slide-in">
        <h3 class="text-base font-bold text-slate-800 dark:text-slate-100 border-b border-slate-50 dark:border-slate-700/40 pb-2">
          Étape 3 : Dates, Gratification & Missions
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div class="flex flex-col gap-2">
            <label class="text-xs font-bold text-slate-600 dark:text-slate-300">Date de début de stage</label>
            <input type="date" v-model="form.startDate" class="w-full p-3 border border-slate-200 dark:border-slate-700 rounded-xl bg-slate-50 dark:bg-slate-900 text-xs text-slate-800 dark:text-slate-200 focus:outline-none" />
          </div>

          <div class="flex flex-col gap-2">
            <label class="text-xs font-bold text-slate-600 dark:text-slate-300">Date de fin de stage</label>
            <input type="date" v-model="form.endDate" class="w-full p-3 border border-slate-200 dark:border-slate-700 rounded-xl bg-slate-50 dark:bg-slate-900 text-xs text-slate-800 dark:text-slate-200 focus:outline-none" />
          </div>

          <div class="flex flex-col gap-2">
            <label class="text-xs font-bold text-slate-600 dark:text-slate-300">Volume horaire hebdomadaire (Heures)</label>
            <input type="number" v-model="form.weeklyHours" class="w-full p-3 border border-slate-200 dark:border-slate-700 rounded-xl bg-slate-50 dark:bg-slate-900 text-xs text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-violet-500" />
          </div>

          <div class="flex flex-col gap-2">
            <label class="text-xs font-bold text-slate-600 dark:text-slate-300">Gratification horaire nette (€ / h)</label>
            <input type="number" step="0.01" v-model="form.salaryAmount" class="w-full p-3 border border-slate-200 dark:border-slate-700 rounded-xl bg-slate-50 dark:bg-slate-900 text-xs text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-violet-500" />
          </div>

          <div class="flex flex-col gap-2">
            <label class="text-xs font-bold text-slate-600 dark:text-slate-300">Nom du maître de stage (Entreprise)</label>
            <input type="text" v-model="form.supervisorName" placeholder="Ex: M. Robert Legrand" class="w-full p-3 border border-slate-200 dark:border-slate-700 rounded-xl bg-slate-50 dark:bg-slate-900 text-xs text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-violet-500" />
          </div>

          <div class="flex flex-col gap-2">
            <label class="text-xs font-bold text-slate-600 dark:text-slate-300">E-mail de contact du maître de stage</label>
            <input type="email" v-model="form.supervisorEmail" placeholder="Ex: r.legrand@entreprise.com" class="w-full p-3 border border-slate-200 dark:border-slate-700 rounded-xl bg-slate-50 dark:bg-slate-900 text-xs text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-violet-500" />
          </div>

          <div class="flex flex-col gap-2 md:col-span-2">
            <label class="text-xs font-bold text-slate-600 dark:text-slate-300">Missions confiées / Description du sujet de stage</label>
            <textarea v-model="form.subject" rows="4" placeholder="Décrivez en quelques lignes les tâches, objectifs et technologies abordées au cours du stage..." class="w-full p-3 border border-slate-200 dark:border-slate-700 rounded-xl bg-slate-50 dark:bg-slate-900 text-xs text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-violet-500"></textarea>
          </div>
        </div>
      </div>

      <!-- Step 4: Submission Summary -->
      <div v-if="currentStep === 4" class="space-y-6 animate-slide-in">
        <h3 class="text-base font-bold text-slate-800 dark:text-slate-100 border-b border-slate-50 dark:border-slate-700/40 pb-2">
          Étape 4 : Récapitulatif et envoi
        </h3>

        <div class="bg-slate-50 dark:bg-slate-900/60 border border-slate-100 dark:border-slate-800 rounded-2xl p-6 space-y-4">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
            <div>
              <span class="text-slate-400 block">Période du stage :</span>
              <span class="font-bold text-slate-800 dark:text-slate-200">{{ form.period }}</span>
            </div>
            <div>
              <span class="text-slate-400 block">Téléphone étudiant :</span>
              <span class="font-bold text-slate-800 dark:text-slate-200">{{ form.phone || '-' }}</span>
            </div>
            <div class="md:col-span-2">
              <span class="text-slate-400 block">Raison sociale & SIRET :</span>
              <span class="font-bold text-slate-800 dark:text-slate-200">{{ form.companyName || '-' }} (SIRET : {{ form.companySiret || '-' }})</span>
            </div>
            <div class="md:col-span-2">
              <span class="text-slate-400 block">Lieu du stage :</span>
              <span class="font-bold text-slate-800 dark:text-slate-200">{{ form.companyAddress || '-' }}</span>
            </div>
            <div>
              <span class="text-slate-400 block">Dates de stage :</span>
              <span class="font-bold text-slate-800 dark:text-slate-200">{{ form.startDate ? new Date(form.startDate).toLocaleDateString('fr-FR') : '-' }} au {{ form.endDate ? new Date(form.endDate).toLocaleDateString('fr-FR') : '-' }}</span>
            </div>
            <div>
              <span class="text-slate-400 block">Gratification horaire :</span>
              <span class="font-bold text-slate-800 dark:text-slate-200">{{ form.salaryAmount }} €/heure ({{ form.weeklyHours }}h / semaine)</span>
            </div>
            <div>
              <span class="text-slate-400 block">Maître de stage :</span>
              <span class="font-bold text-slate-800 dark:text-slate-200">{{ form.supervisorName || '-' }} ({{ form.supervisorEmail || '-' }})</span>
            </div>
            <div>
              <span class="text-slate-400 block">Représentant Signataire :</span>
              <span class="font-bold text-slate-800 dark:text-slate-200">{{ form.signatoryName || '-' }} ({{ form.signatoryTitle || '-' }})</span>
            </div>
            <div class="md:col-span-2 pt-2 border-t border-slate-200/40">
              <span class="text-slate-400 block">Sujet détaillé :</span>
              <p class="font-medium text-slate-700 dark:text-slate-300 mt-1 leading-relaxed whitespace-pre-line">{{ form.subject || 'Aucune description fournie.' }}</p>
            </div>
          </div>
        </div>

        <!-- Submit warning -->
        <div class="bg-amber-50 dark:bg-amber-950/20 border border-amber-200 dark:border-amber-900/40 rounded-2xl p-4 flex items-start gap-3">
          <i class="pi pi-exclamation-triangle text-amber-600 mt-0.5"></i>
          <div>
            <h5 class="text-xs font-bold text-amber-800 dark:text-amber-300">Déclaration sur l'honneur</h5>
            <p class="text-[10px] text-amber-600 dark:text-amber-400 mt-0.5">
              En soumettant cette demande, vous certifiez l'exactitude des informations relatives à l'entreprise d'accueil et à vos garanties d'assurance responsabilité civile. Des informations fausses retarderont l'édition et la signature de la convention.
            </p>
          </div>
        </div>
      </div>

      <!-- Action Buttons -->
      <div class="flex items-center justify-between border-t border-slate-50 dark:border-slate-700/40 pt-6 mt-8">
        <button 
          v-if="currentStep > 1" 
          @click="prevStep"
          class="text-xs font-bold px-5 py-3 rounded-xl border border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-800 transition-all flex items-center gap-2"
        >
          <i class="pi pi-arrow-left text-[10px]"></i>
          <span>Précédent</span>
        </button>
        <div v-else></div> <!-- Spacer -->

        <div>
          <!-- Next Button -->
          <button 
            v-if="currentStep < totalSteps" 
            @click="nextStep"
            class="text-xs font-bold px-6 py-3 rounded-xl bg-violet-600 hover:bg-violet-700 text-white transition-all flex items-center gap-2"
          >
            <span>Suivant</span>
            <i class="pi pi-arrow-right text-[10px]"></i>
          </button>

          <!-- Final Submit Button -->
          <button 
            v-else 
            @click="submitRequest"
            :disabled="isSubmitting"
            class="text-xs font-bold px-6 py-3 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white transition-all flex items-center gap-2"
          >
            <i v-if="isSubmitting" class="pi pi-spin pi-spinner"></i>
            <i v-else class="pi pi-check"></i>
            <span>{{ isSubmitting ? 'Soumission...' : 'Soumettre ma demande' }}</span>
          </button>
        </div>
      </div>

    </div>
  </div>
</template>

<style scoped>
.animate-slide-in {
  animation: slideIn 0.3s ease-out;
}

@keyframes slideIn {
  from { opacity: 0; transform: translateX(12px); }
  to { opacity: 1; transform: translateX(0); }
}
</style>
