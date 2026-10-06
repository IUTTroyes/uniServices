<script setup>
import { ref, computed } from 'vue';
import { useToast } from 'primevue/usetoast';
import Button from 'primevue/button';
import {
  DocumentTextIcon,
  TagIcon,
  EyeIcon
} from '@heroicons/vue/24/outline';
import { FormSection } from '../../components/Form';

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
    output = output.replace(regex, `<span class="bg-teal-100 dark:bg-teal-950/50 text-teal-800 dark:text-teal-300 px-1 py-0.5 rounded font-semibold font-mono text-[11px] border border-teal-200 dark:border-teal-800/80">${item.example}</span>`);
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
    <div class="flex items-center gap-4 justify-between border-b border-slate-200 dark:border-slate-800 pb-5">
      <div>
        <h1 class="text-2xl font-black text-slate-900 dark:text-white flex items-center gap-2">
          <i class="pi pi-cog text-teal-600"></i>
          <span>Gestion des Modèles de Convention</span>
        </h1>
        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
          Modifiez le document type utilisé pour la génération officielle des conventions de stage.
        </p>
      </div>

      <Button
        label="Enregistrer le modèle"
        icon="pi pi-save"
        severity="primary"
        @click="saveTemplate"
      />
    </div>

    <!-- Main Workspace Splitter -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

      <!-- Left Column: Template Editor Area (7 columns) -->
      <div class="lg:col-span-7 space-y-6">

        <!-- Editor Input Card -->
        <FormSection
          :icon="DocumentTextIcon"
          tone="teal"
          title="Corps de la Convention"
          description="Format brut avec intégration de variables dynamiques entre accolades."
        >
          <textarea
            v-model="templateBody"
            rows="16"
            class="q-input font-mono text-xs leading-relaxed"
            placeholder="Saisissez le texte du modèle de convention..."
          ></textarea>
        </FormSection>

        <!-- Placeholders Helper Catalog -->
        <FormSection
          :icon="TagIcon"
          tone="indigo"
          title="Variables dynamiques disponibles"
          description="Cliquez sur une variable pour l'insérer à la fin du document."
        >
          <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2 max-h-[220px] overflow-y-auto pr-1">
            <button
              v-for="item in placeholders"
              :key="item.tag"
              @click="insertTag(item.tag)"
              class="p-2 bg-white dark:bg-slate-800 hover:bg-teal-50 dark:hover:bg-teal-950/30 text-slate-700 hover:text-teal-700 dark:text-slate-300 dark:hover:text-teal-300 border border-slate-200 dark:border-slate-700/80 hover:border-teal-300 rounded-xl text-xs font-mono transition-all text-left flex flex-col gap-0.5 cursor-pointer shadow-2xs"
            >
              <strong class="text-teal-600 dark:text-teal-400 text-xs">{{ item.tag }}</strong>
              <span class="text-2xs text-slate-400 dark:text-slate-500 truncate">{{ item.desc }}</span>
            </button>
          </div>
        </FormSection>

      </div>

      <!-- Right Column: Visual Real-time Preview (5 columns) -->
      <div class="lg:col-span-5 space-y-3">
        <div class="flex items-center gap-2 text-xs font-bold text-slate-500 uppercase tracking-wider">
          <EyeIcon class="w-4 h-4 text-teal-600" />
          <span>Aperçu du rendu (Format A4)</span>
        </div>

        <!-- Mock Letterhead Page -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700/80 rounded-2xl shadow-xs p-7 min-h-[520px] flex flex-col relative overflow-hidden select-none">

          <!-- Letterhead header -->
          <div class="flex justify-between items-start border-b border-slate-100 dark:border-slate-800 pb-4 mb-6">
            <div class="flex items-center gap-2.5">
              <div class="w-8 h-8 rounded-lg bg-teal-600 flex items-center justify-center text-white text-[11px] font-black tracking-tighter shadow-2xs">
                IUT
              </div>
              <div>
                <h4 class="text-xs font-black text-slate-900 dark:text-white uppercase leading-none">IUT de Troyes</h4>
                <span class="text-[9px] text-slate-400 leading-none">Université de Reims Champagne-Ardenne</span>
              </div>
            </div>
            <span class="text-[9px] text-slate-400 font-mono">DOC_CONV_V2</span>
          </div>

          <!-- Content Area rendering dynamic preview -->
          <div
            class="text-[11px] leading-relaxed text-slate-700 dark:text-slate-300 flex-1 whitespace-pre-line"
            v-html="renderedPreview"
          ></div>

          <!-- Letterhead footer -->
          <div class="border-t border-slate-100 dark:border-slate-800 pt-3 mt-8 text-[8px] text-center text-slate-400 uppercase tracking-wider">
            Document officiel généré numériquement par UniServices - IUT de Troyes
          </div>

          <!-- Page watermark -->
          <div class="absolute inset-0 flex items-center justify-center pointer-events-none opacity-[0.02] transform -rotate-12 select-none">
            <span class="text-6xl font-black uppercase text-slate-900 dark:text-white">Spécimen</span>
          </div>
        </div>

      </div>

    </div>
  </div>
</template>

