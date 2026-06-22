<script setup>
import { ref, computed } from 'vue';
import { useToast } from 'primevue/usetoast';

const toast = useToast();

// Mock initial template content
const templateBody = ref(`CONVENTION DE STAGE
ENTRE LES SOUSSIGNÉS :

L'Établissement d'enseignement : IUT de Troyes, représenté par son Directeur, d'une part,
Et l'Entreprise : {entreprise.nom}, située au {entreprise.adresse}, représentée par {entreprise.signataire}, d'autre part,
Et l'étudiant(e) : {etudiant.prenom} {etudiant.nom}, inscrit en {etudiant.parcours}.

IL A ÉTÉ CONVENU CE QUI SUIT :

Article 1 : Le présent stage est conclu du {stage.date_debut} au {stage.date_fin}.
Article 2 : Les missions confiées à l'étudiant consisteront en :
{stage.missions}

Article 3 : La gratification horaire nette est fixée à {stage.gratification} €/heure pour un volume de {stage.heures_hebdo} heures par semaine.
Article 4 : Le suivi pédagogique de l'étudiant est confié au tuteur universitaire : {tuteur.nom}.`);

// Available placeholders list with description
const placeholders = [
  { tag: '{etudiant.nom}', desc: 'Nom de famille de l\'étudiant', example: 'MARTIN' },
  { tag: '{etudiant.prenom}', desc: 'Prénom de l\'étudiant', example: 'Lucas' },
  { tag: '{etudiant.parcours}', desc: 'Parcours / BUT / Année', example: 'BUT 3 Informatique' },
  { tag: '{entreprise.nom}', desc: 'Raison sociale entreprise', example: 'Avenir Digital' },
  { tag: '{entreprise.adresse}', desc: 'Adresse du lieu de stage', example: '8 Rue Kléber, 75016 Paris' },
  { tag: '{entreprise.signataire}', desc: 'Nom du représentant signataire', example: 'Mme. Sylvie Martin' },
  { tag: '{stage.date_debut}', desc: 'Date de début du stage', example: '02/03/2026' },
  { tag: '{stage.date_fin}', desc: 'Date de fin du stage', example: '26/06/2026' },
  { tag: '{stage.missions}', desc: 'Sujet/Missions du stage', example: 'Développement d\'une interface moderne et migration vers Vue.js 3.' },
  { tag: '{stage.gratification}', desc: 'Taux horaire de gratification', example: '4.95' },
  { tag: '{stage.heures_hebdo}', desc: 'Heures de travail par semaine', example: '35' },
  { tag: '{tuteur.nom}', desc: 'Nom du tuteur affecté', example: 'Mme. Sophie Gomez' }
];

// Helper to insert tag at current position or end
const insertTag = (tag) => {
  templateBody.value += ' ' + tag;
  toast.add({
    severity: 'info',
    summary: 'Balise insérée',
    detail: `La balise ${tag} a été ajoutée à la fin de votre modèle.`,
    life: 2000
  });
};

// Computed property to parse and render preview in real-time
const renderedPreview = computed(() => {
  let output = templateBody.value;
  
  // Replace tags with sample data
  placeholders.forEach(item => {
    // Escape special characters for regex search
    const escapedTag = item.tag.replace(/[-\/\\^$*+?.()|[\]{}]/g, '\\$&');
    const regex = new RegExp(escapedTag, 'g');
    output = output.replace(regex, `<span class="bg-violet-100 dark:bg-violet-950/40 text-violet-700 dark:text-violet-400 px-1 py-0.5 rounded font-semibold font-mono text-[11px]">${item.example}</span>`);
  });

  // Preserve linebreaks as html tags
  return output.replace(/\n/g, '<br>');
});

const saveTemplate = () => {
  toast.add({
    severity: 'success',
    summary: 'Modèle enregistré',
    detail: 'Le modèle de convention a été mis à jour dans la base de données.',
    life: 3000
  });
};
</script>

<template>
  <div class="mx-auto space-y-6">
    <Toast />

    <!-- Top Header -->
    <div class="flex items-center gap-4 justify-between border-b border-slate-100 dark:border-slate-800 pb-5">
      <div>
        <h1 class="text-2xl font-black text-slate-900 dark:text-white flex items-center gap-2">
          <i class="pi pi-cog text-rose-500"></i>
          <span>Gestion des Modèles de Convention</span>
        </h1>
        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
          Modifiez le document type utilisé pour la génération officielle des conventions de stage.
        </p>
      </div>

      <button 
        @click="saveTemplate"
        class="text-xs font-bold px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl shadow-md transition-all flex items-center gap-2"
      >
        <i class="pi pi-save"></i>
        <span>Enregistrer le modèle</span>
      </button>
    </div>

    <!-- Main Workspace Splitter -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
      
      <!-- Left Column: Template Editor Area (7 columns) -->
      <div class="lg:col-span-7 space-y-6">
        
        <!-- Editor Input Card -->
        <div class="bg-white dark:bg-slate-800 border border-slate-100 dark:border-slate-700/60 rounded-3xl p-6 shadow-sm space-y-4">
          <div class="flex justify-between items-center">
            <h3 class="text-sm font-bold text-slate-900 dark:text-white">Corps de la Convention</h3>
            <span class="text-[10px] text-slate-400 font-mono">Format brut (Variables incluses)</span>
          </div>

          <textarea 
            v-model="templateBody" 
            rows="16" 
            class="w-full p-4 border border-slate-200 dark:border-slate-700 rounded-2xl bg-slate-50 dark:bg-slate-900 text-xs font-mono text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-rose-500 leading-relaxed"
            placeholder="Saisissez le texte du modèle de convention..."
          ></textarea>
        </div>

        <!-- Placeholders Helper Catalog -->
        <div class="bg-white dark:bg-slate-800 border border-slate-100 dark:border-slate-700/60 rounded-3xl p-6 shadow-sm">
          <h3 class="text-sm font-bold text-slate-900 dark:text-white mb-2 flex items-center gap-2">
            <i class="pi pi-tags text-rose-500"></i>
            <span>Variables dynamiques disponibles</span>
          </h3>
          <p class="text-xs text-slate-400 mb-4">Cliquez sur une variable ci-dessous pour l'ajouter à la fin du document.</p>

          <div class="flex flex-wrap gap-2 max-h-[160px] overflow-y-auto pr-2">
            <button 
              v-for="item in placeholders" 
              :key="item.tag"
              @click="insertTag(item.tag)"
              class="px-2.5 py-1.5 bg-slate-50 hover:bg-rose-50 dark:bg-slate-700/40 dark:hover:bg-rose-950/20 text-slate-700 hover:text-rose-700 dark:text-slate-300 dark:hover:text-rose-400 border border-slate-200/60 dark:border-slate-700 rounded-lg text-[10px] font-mono transition-all text-left flex flex-col gap-0.5 w-[calc(50%-4px)] md:w-[calc(33.33%-6px)]"
              v-tooltip="item.desc"
            >
              <strong class="text-rose-600 dark:text-rose-400">{{ item.tag }}</strong>
              <span class="text-[9px] text-slate-400 truncate">{{ item.desc }}</span>
            </button>
          </div>
        </div>

      </div>

      <!-- Right Column: Visual Real-time Preview (5 columns) -->
      <div class="lg:col-span-5 space-y-6">
        
        <div class="flex flex-col h-full">
          <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-3">Prévisualisation du rendu (Format A4)</h3>
          
          <!-- Mock Letterhead Page -->
          <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-700/50 rounded-2xl shadow-xl flex-1 p-8 min-h-[500px] flex flex-col relative overflow-hidden select-none">
            
            <!-- Letterhead header -->
            <div class="flex justify-between items-start border-b-2 border-slate-100 dark:border-slate-800 pb-4 mb-6">
              <div class="flex items-center gap-2">
                <div class="w-7 h-7 rounded-lg bg-rose-500 flex items-center justify-center text-white text-[10px] font-black tracking-tighter">
                  IUT
                </div>
                <div>
                  <h4 class="text-[10px] font-black text-slate-900 dark:text-white uppercase leading-none">IUT de Troyes</h4>
                  <span class="text-[8px] text-slate-400 leading-none">Université de Reims Champagne-Ardenne</span>
                </div>
              </div>
              <span class="text-[8px] text-slate-400 font-mono">DOC_CONV_V2</span>
            </div>

            <!-- Content Area rendering dynamic preview -->
            <div 
              class="text-[10px] leading-relaxed text-slate-700 dark:text-slate-300 flex-1 whitespace-pre-line"
              v-html="renderedPreview"
            ></div>

            <!-- Letterhead footer -->
            <div class="border-t border-slate-100 dark:border-slate-800 pt-3 mt-8 text-[7px] text-center text-slate-400 uppercase tracking-wider">
              Document officiel généré numériquement par UniServices - IUT de Troyes
            </div>

            <!-- Page watermark -->
            <div class="absolute inset-0 flex items-center justify-center pointer-events-none opacity-[0.03] transform -rotate-12 select-none">
              <span class="text-5xl font-black uppercase text-slate-900">Spécimen</span>
            </div>
          </div>
        </div>

      </div>

    </div>
  </div>
</template>

<style scoped>
.animate-fade-in {
  animation: fadeIn 0.3s ease-out;
}

@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}
</style>
