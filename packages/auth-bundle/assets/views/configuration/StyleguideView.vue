<script setup lang="ts">
import { ref, computed } from 'vue';
import {
  DocumentTextIcon,
  RocketLaunchIcon,
  ChatBubbleLeftRightIcon,
  ArrowTrendingUpIcon,
  PlusIcon,
  DocumentDuplicateIcon,
  ListBulletIcon,
  CheckCircleIcon,
  ClockIcon,
  ExclamationTriangleIcon,
  InformationCircleIcon,
  ClipboardIcon,
  CheckIcon
} from '@heroicons/vue/24/outline';
import {
  HeaderComponent,
  Kpi,
  Card,
  QuickActionCard,
  ButtonDelete,
  ButtonEdit,
  ButtonSave,
  ButtonInfo,
  ButtonDuplicate,
  FormValidator,
  ValidatedInput,
  AddressAutocomplete,
  ExampleValidatedForm
} from '@components';
import ActionButtonVertical from '@components/components/ActionButtonVertical.vue';
import Alert from '@components/components/Alert.vue';

// Active tab sidebar selection
const activeComponent = ref<'header' | 'card' | 'kpi' | 'quick-action' | 'action-btn' | 'alert' | 'buttons' | 'forms'>('header');
const copiedText = ref(false);

function triggerCopiedNotification() {
  copiedText.value = true;
  setTimeout(() => {
    copiedText.value = false;
  }, 2000);
}

function copyCode(text: string) {
  navigator.clipboard.writeText(text);
  triggerCopiedNotification();
}

// -------------------------------------------------------------
// Interactive states for each component demo
// -------------------------------------------------------------

// 1. HeaderComponent
const headerProps = ref({
  titre: 'Documentation des Composants',
  description: 'Explorez et expérimentez avec notre bibliothèque de composants d\'interface.',
  icon: 'pi pi-palette',
  showBack: true,
  backUrl: ''
});

const headerCode = computed(() => {
  const backUrlStr = headerProps.value.backUrl ? `\n  back-url="${headerProps.value.backUrl}"` : '';
  const showBackStr = !headerProps.value.showBack ? `\n  :show-back="false"` : '';
  return `<HeaderComponent
  icon="${headerProps.value.icon}"
  titre="${headerProps.value.titre}"
  description="${headerProps.value.description}"${backUrlStr}${showBackStr}
/>`;
});

// 2. Card
const cardProps = ref({
  title: 'Statistiques Hebdomadaires',
  bodyClass: 'p-6',
  content: 'Contenu principal de la carte. Vous pouvez insérer n\'importe quel élément ici.'
});

const cardCode = computed(() => {
  const bodyClassStr = cardProps.value.bodyClass ? ` body-class="${cardProps.value.bodyClass}"` : '';
  return `<Card title="${cardProps.value.title}"${bodyClassStr}>
  <p>${cardProps.value.content}</p>
</Card>`;
});

// 3. Kpi
const kpiProps = ref({
  label: 'Évaluations Actives',
  value: '14',
  color: 'emerald',
  description: 'En hausse de 12% ce mois-ci'
});

const kpiCode = computed(() => {
  const descStr = kpiProps.value.description ? `\n  description="${kpiProps.value.description}"` : '';
  return `<Kpi
  label="${kpiProps.value.label}"
  :value="${kpiProps.value.value}"
  :icon="RocketLaunchIcon"
  color="${kpiProps.value.color}"${descStr}
/>`;
});

// 4. QuickActionCard
const qaProps = ref({
  title: 'Nouveau Questionnaire',
  description: 'Créer un questionnaire de zéro',
  color: 'blue',
  buttonLabel: 'Créer',
  to: '/auth/configuration/styleguide'
});

const qaCode = computed(() => {
  const toStr = qaProps.value.to ? `\n  :to="{ name: 'styleguide' }"` : '';
  return `<QuickActionCard
  title="${qaProps.value.title}"
  description="${qaProps.value.description}"
  :icon="PlusIcon"
  color="${qaProps.value.color}"
  button-label="${qaProps.value.buttonLabel}"${toStr}
/>`;
});

// 5. ActionButtonVertical
const abvProps = ref({
  label: 'Exporter Excel',
  severity: 'primary' as 'primary' | 'secondary' | 'success' | 'info' | 'warning' | 'help' | 'danger',
  disabled: false
});

const abvCode = computed(() => {
  const disabledStr = abvProps.value.disabled ? `\n  :disabled="true"` : '';
  return `<ActionButtonVertical
  label="${abvProps.value.label}"
  :icon="DocumentTextIcon"
  severity="${abvProps.value.severity}"${disabledStr}
/>`;
});

// 6. Alert
const alertProps = ref({
  severity: 'info' as 'info' | 'success' | 'warning' | 'error',
  message: 'Une mise à jour importante de la plateforme est planifiée ce soir.',
  closable: true
});

const alertCode = computed(() => {
  const closableStr = !alertProps.value.closable ? `\n  :closable="false"` : '';
  return `<Alert
  severity="${alertProps.value.severity}"
  message="${alertProps.value.message}"${closableStr}
/>`;
});

// 7. Buttons (Confirmation & Action buttons)
const buttonTooltip = ref('Action du bouton');
const buttonLabel = ref('Supprimer');
const lastActionLog = ref('');

function logButtonAction(actionName: string) {
  lastActionLog.value = `Événement capturé : "${actionName}" déclenché à ${new Date().toLocaleTimeString()}`;
}

const buttonsCode = computed(() => {
  return `<!-- Bouton Supprimer (avec dialogue de confirmation) -->
<ButtonDelete
  tooltip="${buttonTooltip.value}"
  label="${buttonLabel.value}"
  @confirm-delete="onDelete"
/>

<!-- Bouton Modifier -->
<ButtonEdit tooltip="Modifier l'élément" />

<!-- Bouton Enregistrer (avec dialogue de confirmation) -->
<ButtonSave tooltip="Enregistrer les modifications" @confirm-save="onSave" />

<!-- Bouton Info -->
<ButtonInfo tooltip="Plus d'informations" />

<!-- Bouton Dupliquer (avec dialogue de confirmation) -->
<ButtonDuplicate tooltip="Dupliquer l'élément" @confirm-duplicate="onDuplicate" />`;
});

// 8. Forms (ValidatedInputs)
const inputProps = ref({
  type: 'text',
  label: 'Prénom & Nom',
  placeholder: 'Ex: Jean Dupont',
  rules: 'required|min:3',
  value: ''
});

const inputOptions = [
  { label: 'Option A (Valeur 1)', value: '1' },
  { label: 'Option B (Valeur 2)', value: '2' },
  { label: 'Option C (Valeur 3)', value: '3' }
];

const formsCode = computed(() => {
  const optionsMarkup = (inputProps.value.type === 'select' || inputProps.value.type === 'multiselect')
    ? `\n  :options="[{ label: 'Option A (Valeur 1)', value: '1' }, ...]"`
    : '';
  return `<ValidatedInput
  type="${inputProps.value.type}"
  label="${inputProps.value.label}"
  placeholder="${inputProps.value.placeholder}"
  rules="${inputProps.value.rules}"
  v-model="valeur"${optionsMarkup}
/>`;
});
</script>

<template>
  <ConfirmDialog />
  <div class="flex flex-col xl:flex-row gap-8 min-h-screen">
    <!-- Navigation Sidebar -->
    <div class="w-full xl:w-64 shrink-0">
      <div class="card sticky top-4">
        <div class="card-header font-bold text-gray-900 dark:text-white text-sm uppercase tracking-wider">
          Composants
        </div>
        <div class="card-body p-2 flex flex-col gap-1">
          <button
            @click="activeComponent = 'header'"
            :class="['w-full text-left px-4 py-2.5 rounded-xl text-sm font-semibold transition-all duration-200 flex items-center gap-2',
                     activeComponent === 'header' ? 'bg-primary-500 text-white shadow-md' : 'text-gray-700 dark:text-gray-300 hover:bg-slate-100 dark:hover:bg-slate-700/50']"
          >
            <i class="pi pi-window-maximize text-sm" />
            HeaderComponent
          </button>
          <button
            @click="activeComponent = 'card'"
            :class="['w-full text-left px-4 py-2.5 rounded-xl text-sm font-semibold transition-all duration-200 flex items-center gap-2',
                     activeComponent === 'card' ? 'bg-primary-500 text-white shadow-md' : 'text-gray-700 dark:text-gray-300 hover:bg-slate-100 dark:hover:bg-slate-700/50']"
          >
            <i class="pi pi-clone text-sm" />
            Card
          </button>
          <button
            @click="activeComponent = 'kpi'"
            :class="['w-full text-left px-4 py-2.5 rounded-xl text-sm font-semibold transition-all duration-200 flex items-center gap-2',
                     activeComponent === 'kpi' ? 'bg-primary-500 text-white shadow-md' : 'text-gray-700 dark:text-gray-300 hover:bg-slate-100 dark:hover:bg-slate-700/50']"
          >
            <i class="pi pi-percentage text-sm" />
            Kpi
          </button>
          <button
            @click="activeComponent = 'quick-action'"
            :class="['w-full text-left px-4 py-2.5 rounded-xl text-sm font-semibold transition-all duration-200 flex items-center gap-2',
                     activeComponent === 'quick-action' ? 'bg-primary-500 text-white shadow-md' : 'text-gray-700 dark:text-gray-300 hover:bg-slate-100 dark:hover:bg-slate-700/50']"
          >
            <i class="pi pi-directions text-sm" />
            QuickActionCard
          </button>
          <button
            @click="activeComponent = 'action-btn'"
            :class="['w-full text-left px-4 py-2.5 rounded-xl text-sm font-semibold transition-all duration-200 flex items-center gap-2',
                     activeComponent === 'action-btn' ? 'bg-primary-500 text-white shadow-md' : 'text-gray-700 dark:text-gray-300 hover:bg-slate-100 dark:hover:bg-slate-700/50']"
          >
            <i class="pi pi-box text-sm" />
            ActionButtonVertical
          </button>
          <button
            @click="activeComponent = 'alert'"
            :class="['w-full text-left px-4 py-2.5 rounded-xl text-sm font-semibold transition-all duration-200 flex items-center gap-2',
                     activeComponent === 'alert' ? 'bg-primary-500 text-white shadow-md' : 'text-gray-700 dark:text-gray-300 hover:bg-slate-100 dark:hover:bg-slate-700/50']"
          >
            <i class="pi pi-info-circle text-sm" />
            Alert
          </button>
          <div class="h-[1px] bg-slate-100 dark:bg-slate-700 my-2"></div>
          <button
            @click="activeComponent = 'buttons'"
            :class="['w-full text-left px-4 py-2.5 rounded-xl text-sm font-semibold transition-all duration-200 flex items-center gap-2',
                     activeComponent === 'buttons' ? 'bg-primary-500 text-white shadow-md' : 'text-gray-700 dark:text-gray-300 hover:bg-slate-100 dark:hover:bg-slate-700/50']"
          >
            <i class="pi pi-check-square text-sm" />
            Boutons Actions
          </button>
          <button
            @click="activeComponent = 'forms'"
            :class="['w-full text-left px-4 py-2.5 rounded-xl text-sm font-semibold transition-all duration-200 flex items-center gap-2',
                     activeComponent === 'forms' ? 'bg-primary-500 text-white shadow-md' : 'text-gray-700 dark:text-gray-300 hover:bg-slate-100 dark:hover:bg-slate-700/50']"
          >
            <i class="pi pi-pencil text-sm" />
            Formulaires & Validations
          </button>
        </div>
      </div>
    </div>

    <!-- Main Content Area -->
    <div class="flex-1 space-y-8">
      <!-- 1. HEADER COMPONENT DOCUMENTATION -->
      <div v-if="activeComponent === 'header'" class="space-y-6">
        <div class="card p-6 bg-gradient-to-br from-primary-500/5 to-transparent border border-primary-500/10 rounded-2xl">
          <h2 class="text-2xl font-bold text-gray-900 dark:text-white">HeaderComponent</h2>
          <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
            Gère l'affichage uniforme des en-têtes de page avec titre, description, icône et bouton de retour optionnel.
          </p>
        </div>

        <!-- Live Demo -->
        <Card title="Aperçu Interactif">
          <div class="bg-slate-50 dark:bg-slate-900/40 p-6 rounded-2xl border border-slate-100 dark:border-slate-800/80 mb-6">
            <HeaderComponent
              :icon="headerProps.icon"
              :titre="headerProps.titre"
              :description="headerProps.description"
              :show-back="headerProps.showBack"
              :back-url="headerProps.backUrl"
            />
          </div>

          <!-- Controls -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6 bg-slate-50/50 dark:bg-slate-800/20 p-5 rounded-2xl border border-slate-100 dark:border-slate-800/40">
            <div class="space-y-4">
              <div>
                <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider block mb-1">Titre</label>
                <input v-model="headerProps.titre" type="text" class="w-full p-2.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm" />
              </div>
              <div>
                <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider block mb-1">Description</label>
                <textarea v-model="headerProps.description" rows="2" class="w-full p-2.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm"></textarea>
              </div>
            </div>
            <div class="space-y-4">
              <div>
                <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider block mb-1">Icône (PrimeIcons)</label>
                <input v-model="headerProps.icon" type="text" class="w-full p-2.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm" />
              </div>
              <div class="flex items-center gap-4 pt-4">
                <label class="flex items-center gap-2 text-sm font-semibold text-gray-700 dark:text-gray-300 cursor-pointer">
                  <input v-model="headerProps.showBack" type="checkbox" class="w-4 h-4 rounded text-primary-500 focus:ring-primary-500" />
                  Afficher le bouton Retour
                </label>
              </div>
            </div>
          </div>
        </Card>

        <!-- Code Snippet -->
        <Card title="Code d'intégration">
          <div class="relative">
            <pre class="bg-slate-950 text-slate-200 p-5 rounded-2xl overflow-x-auto text-xs leading-relaxed font-mono"><code>{{ headerCode }}</code></pre>
            <button
              @click="copyCode(headerCode)"
              class="absolute top-3 right-3 bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white p-2 rounded-xl text-xs flex items-center gap-1.5 transition-all duration-200 border border-slate-700/50"
            >
              <CheckIcon v-if="copiedText" class="w-4 h-4 text-green-400" />
              <ClipboardIcon v-else class="w-4 h-4" />
              {{ copiedText ? 'Copié !' : 'Copier' }}
            </button>
          </div>
        </Card>
      </div>

      <!-- 2. CARD COMPONENT DOCUMENTATION -->
      <div v-if="activeComponent === 'card'" class="space-y-6">
        <div class="card p-6 bg-gradient-to-br from-primary-500/5 to-transparent border border-primary-500/10 rounded-2xl">
          <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Card</h2>
          <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
            Composant de boîte conteneur standardisé avec un en-tête structuré (titre ou slot header) et un corps à style personnalisable.
          </p>
        </div>

        <!-- Live Demo -->
        <Card title="Aperçu Interactif">
          <div class="bg-slate-50 dark:bg-slate-900/40 p-6 rounded-2xl border border-slate-100 dark:border-slate-800/80 mb-6">
            <Card :title="cardProps.title" :body-class="cardProps.bodyClass">
              <p class="text-sm text-gray-700 dark:text-gray-300">{{ cardProps.content }}</p>
            </Card>
          </div>

          <!-- Controls -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6 bg-slate-50/50 dark:bg-slate-800/20 p-5 rounded-2xl border border-slate-100 dark:border-slate-800/40">
            <div>
              <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider block mb-1">Titre de la carte</label>
              <input v-model="cardProps.title" type="text" class="w-full p-2.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm" />
            </div>
            <div>
              <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider block mb-1">Classes additionnelles de corps</label>
              <input v-model="cardProps.bodyClass" type="text" class="w-full p-2.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm" />
            </div>
            <div class="col-span-1 md:col-span-2">
              <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider block mb-1">Contenu intérieur</label>
              <textarea v-model="cardProps.content" rows="2" class="w-full p-2.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm"></textarea>
            </div>
          </div>
        </Card>

        <!-- Code Snippet -->
        <Card title="Code d'intégration">
          <div class="relative">
            <pre class="bg-slate-950 text-slate-200 p-5 rounded-2xl overflow-x-auto text-xs leading-relaxed font-mono"><code>{{ cardCode }}</code></pre>
            <button
              @click="copyCode(cardCode)"
              class="absolute top-3 right-3 bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white p-2 rounded-xl text-xs flex items-center gap-1.5 transition-all duration-200 border border-slate-700/50"
            >
              <CheckIcon v-if="copiedText" class="w-4 h-4 text-green-400" />
              <ClipboardIcon v-else class="w-4 h-4" />
              {{ copiedText ? 'Copié !' : 'Copier' }}
            </button>
          </div>
        </Card>
      </div>

      <!-- 3. KPI COMPONENT DOCUMENTATION -->
      <div v-if="activeComponent === 'kpi'" class="space-y-6">
        <div class="card p-6 bg-gradient-to-br from-primary-500/5 to-transparent border border-primary-500/10 rounded-2xl">
          <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Kpi</h2>
          <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
            Composant de boîte de statistique clé avec icône, dégradé dynamique basé sur le thème et description optionnelle.
          </p>
        </div>

        <!-- Live Demo -->
        <Card title="Aperçu Interactif">
          <div class="bg-slate-50 dark:bg-slate-900/40 p-6 rounded-2xl border border-slate-100 dark:border-slate-800/80 mb-6 max-w-sm mx-auto">
            <Kpi
              :label="kpiProps.label"
              :value="kpiProps.value"
              :icon="RocketLaunchIcon"
              :color="kpiProps.color"
              :description="kpiProps.description"
            />
          </div>

          <!-- Controls -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6 bg-slate-50/50 dark:bg-slate-800/20 p-5 rounded-2xl border border-slate-100 dark:border-slate-800/40">
            <div class="space-y-4">
              <div>
                <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider block mb-1">Libellé</label>
                <input v-model="kpiProps.label" type="text" class="w-full p-2.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm" />
              </div>
              <div>
                <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider block mb-1">Valeur</label>
                <input v-model="kpiProps.value" type="text" class="w-full p-2.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm" />
              </div>
            </div>
            <div class="space-y-4">
              <div>
                <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider block mb-1">Description explicative</label>
                <input v-model="kpiProps.description" type="text" class="w-full p-2.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm" />
              </div>
              <div>
                <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider block mb-1">Thème de Couleur</label>
                <select v-model="kpiProps.color" class="w-full p-2.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm">
                  <option value="blue">Blue</option>
                  <option value="green">Green</option>
                  <option value="yellow">Yellow</option>
                  <option value="purple">Purple</option>
                  <option value="red">Red</option>
                  <option value="emerald">Emerald</option>
                  <option value="teal">Teal</option>
                  <option value="orange">Orange</option>
                  <option value="pink">Pink</option>
                  <option value="gray">Gray</option>
                </select>
              </div>
            </div>
          </div>
        </Card>

        <!-- Code Snippet -->
        <Card title="Code d'intégration">
          <div class="relative">
            <pre class="bg-slate-950 text-slate-200 p-5 rounded-2xl overflow-x-auto text-xs leading-relaxed font-mono"><code>{{ kpiCode }}</code></pre>
            <button
              @click="copyCode(kpiCode)"
              class="absolute top-3 right-3 bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white p-2 rounded-xl text-xs flex items-center gap-1.5 transition-all duration-200 border border-slate-700/50"
            >
              <CheckIcon v-if="copiedText" class="w-4 h-4 text-green-400" />
              <ClipboardIcon v-else class="w-4 h-4" />
              {{ copiedText ? 'Copié !' : 'Copier' }}
            </button>
          </div>
        </Card>
      </div>

      <!-- 4. QUICK ACTION CARD COMPONENT DOCUMENTATION -->
      <div v-if="activeComponent === 'quick-action'" class="space-y-6">
        <div class="card p-6 bg-gradient-to-br from-primary-500/5 to-transparent border border-primary-500/10 rounded-2xl">
          <h2 class="text-2xl font-bold text-gray-900 dark:text-white">QuickActionCard</h2>
          <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
            Carte de raccourci d'action au design moderne. Toute la surface de la carte est cliquable et un bouton d'action thématique est affiché à droite.
          </p>
        </div>

        <!-- Live Demo -->
        <Card title="Aperçu Interactif">
          <div class="bg-slate-50 dark:bg-slate-900/40 p-6 rounded-2xl border border-slate-100 dark:border-slate-800/80 mb-6 max-w-md mx-auto">
            <QuickActionCard
              :title="qaProps.title"
              :description="qaProps.description"
              :icon="PlusIcon"
              :color="qaProps.color"
              :button-label="qaProps.buttonLabel"
              :to="qaProps.to"
              @action="triggerCopiedNotification"
            />
          </div>

          <!-- Controls -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6 bg-slate-50/50 dark:bg-slate-800/20 p-5 rounded-2xl border border-slate-100 dark:border-slate-800/40">
            <div class="space-y-4">
              <div>
                <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider block mb-1">Titre de l'action</label>
                <input v-model="qaProps.title" type="text" class="w-full p-2.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm" />
              </div>
              <div>
                <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider block mb-1">Description</label>
                <input v-model="qaProps.description" type="text" class="w-full p-2.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm" />
              </div>
            </div>
            <div class="space-y-4">
              <div>
                <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider block mb-1">Libellé du bouton</label>
                <input v-model="qaProps.buttonLabel" type="text" class="w-full p-2.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm" />
              </div>
              <div>
                <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider block mb-1">Thème de Couleur</label>
                <select v-model="qaProps.color" class="w-full p-2.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm">
                  <option value="blue">Blue (Primary solid style)</option>
                  <option value="green">Green (Accent border style)</option>
                  <option value="purple">Purple (Accent border style)</option>
                </select>
              </div>
            </div>
          </div>
        </Card>

        <!-- Code Snippet -->
        <Card title="Code d'intégration">
          <div class="relative">
            <pre class="bg-slate-950 text-slate-200 p-5 rounded-2xl overflow-x-auto text-xs leading-relaxed font-mono"><code>{{ qaCode }}</code></pre>
            <button
              @click="copyCode(qaCode)"
              class="absolute top-3 right-3 bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white p-2 rounded-xl text-xs flex items-center gap-1.5 transition-all duration-200 border border-slate-700/50"
            >
              <CheckIcon v-if="copiedText" class="w-4 h-4 text-green-400" />
              <ClipboardIcon v-else class="w-4 h-4" />
              {{ copiedText ? 'Copié !' : 'Copier' }}
            </button>
          </div>
        </Card>
      </div>

      <!-- 5. ACTION BUTTON VERTICAL COMPONENT DOCUMENTATION -->
      <div v-if="activeComponent === 'action-btn'" class="space-y-6">
        <div class="card p-6 bg-gradient-to-br from-primary-500/5 to-transparent border border-primary-500/10 rounded-2xl">
          <h2 class="text-2xl font-bold text-gray-900 dark:text-white">ActionButtonVertical</h2>
          <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
            Bouton d'action vertical compact (icône en haut, label en bas) très utilisé dans les en-têtes de page ou les barres d'actions rapides.
          </p>
        </div>

        <!-- Live Demo -->
        <Card title="Aperçu Interactif">
          <div class="bg-slate-50 dark:bg-slate-900/40 p-6 rounded-2xl border border-slate-100 dark:border-slate-800/80 mb-6 flex justify-center">
            <ActionButtonVertical
              :label="abvProps.label"
              :icon="DocumentTextIcon"
              :severity="abvProps.severity"
              :disabled="abvProps.disabled"
              @click="triggerCopiedNotification"
            />
          </div>

          <!-- Controls -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6 bg-slate-50/50 dark:bg-slate-800/20 p-5 rounded-2xl border border-slate-100 dark:border-slate-800/40">
            <div class="space-y-4">
              <div>
                <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider block mb-1">Libellé</label>
                <input v-model="abvProps.label" type="text" class="w-full p-2.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm" />
              </div>
              <div class="flex items-center gap-4 pt-4">
                <label class="flex items-center gap-2 text-sm font-semibold text-gray-700 dark:text-gray-300 cursor-pointer">
                  <input v-model="abvProps.disabled" type="checkbox" class="w-4 h-4 rounded text-primary-500 focus:ring-primary-500" />
                  Désactivé (disabled)
                </label>
              </div>
            </div>
            <div>
              <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider block mb-1">Sévérité (Style)</label>
              <select v-model="abvProps.severity" class="w-full p-2.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm">
                <option value="primary">Primary (Orange)</option>
                <option value="secondary">Secondary (Gray)</option>
                <option value="success">Success (Emerald)</option>
                <option value="info">Info (Blue)</option>
                <option value="warning">Warning (Amber)</option>
                <option value="help">Help (Purple)</option>
                <option value="danger">Danger (Red)</option>
              </select>
            </div>
          </div>
        </Card>

        <!-- Code Snippet -->
        <Card title="Code d'intégration">
          <div class="relative">
            <pre class="bg-slate-950 text-slate-200 p-5 rounded-2xl overflow-x-auto text-xs leading-relaxed font-mono"><code>{{ abvCode }}</code></pre>
            <button
              @click="copyCode(abvCode)"
              class="absolute top-3 right-3 bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white p-2 rounded-xl text-xs flex items-center gap-1.5 transition-all duration-200 border border-slate-700/50"
            >
              <CheckIcon v-if="copiedText" class="w-4 h-4 text-green-400" />
              <ClipboardIcon v-else class="w-4 h-4" />
              {{ copiedText ? 'Copié !' : 'Copier' }}
            </button>
          </div>
        </Card>
      </div>

      <!-- 6. ALERT COMPONENT DOCUMENTATION -->
      <div v-if="activeComponent === 'alert'" class="space-y-6">
        <div class="card p-6 bg-gradient-to-br from-primary-500/5 to-transparent border border-primary-500/10 rounded-2xl">
          <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Alert</h2>
          <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
            Enveloppe d'alerte ou message d'information avec choix de sévérité, icônes automatiques et possibilité de fermeture.
          </p>
        </div>

        <!-- Live Demo -->
        <Card title="Aperçu Interactif">
          <div class="bg-slate-50 dark:bg-slate-900/40 p-6 rounded-2xl border border-slate-100 dark:border-slate-800/80 mb-6">
            <Alert
              :severity="alertProps.severity"
              :message="alertProps.message"
              :closable="alertProps.closable"
            />
          </div>

          <!-- Controls -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6 bg-slate-50/50 dark:bg-slate-800/20 p-5 rounded-2xl border border-slate-100 dark:border-slate-800/40">
            <div class="space-y-4">
              <div>
                <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider block mb-1">Message</label>
                <input v-model="alertProps.message" type="text" class="w-full p-2.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm" />
              </div>
              <div class="flex items-center gap-4 pt-4">
                <label class="flex items-center gap-2 text-sm font-semibold text-gray-700 dark:text-gray-300 cursor-pointer">
                  <input v-model="alertProps.closable" type="checkbox" class="w-4 h-4 rounded text-primary-500 focus:ring-primary-500" />
                  Fermable (closable)
                </label>
              </div>
            </div>
            <div>
              <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider block mb-1">Sévérité</label>
              <select v-model="alertProps.severity" class="w-full p-2.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm">
                <option value="info">Info</option>
                <option value="success">Success</option>
                <option value="warning">Warning</option>
                <option value="error">Error</option>
              </select>
            </div>
          </div>
        </Card>

        <!-- Code Snippet -->
        <Card title="Code d'intégration">
          <div class="relative">
            <pre class="bg-slate-950 text-slate-200 p-5 rounded-2xl overflow-x-auto text-xs leading-relaxed font-mono"><code>{{ alertCode }}</code></pre>
            <button
              @click="copyCode(alertCode)"
              class="absolute top-3 right-3 bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white p-2 rounded-xl text-xs flex items-center gap-1.5 transition-all duration-200 border border-slate-700/50"
            >
              <CheckIcon v-if="copiedText" class="w-4 h-4 text-green-400" />
              <ClipboardIcon v-else class="w-4 h-4" />
              {{ copiedText ? 'Copié !' : 'Copier' }}
            </button>
          </div>
        </Card>
      </div>

      <!-- 7. BOUTONS ACTIONS DOCUMENTATION -->
      <div v-if="activeComponent === 'buttons'" class="space-y-6">
        <div class="card p-6 bg-gradient-to-br from-primary-500/5 to-transparent border border-primary-500/10 rounded-2xl">
          <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Boutons d'Actions standards (CRUD)</h2>
          <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
            Une suite de boutons d'actions normalisés (Suppression, Édition, Enregistrement, Info, Duplication) avec infobulles, dialogues de confirmation intégrés et styles homogènes.
          </p>
        </div>

        <!-- Live Demo -->
        <Card title="Aperçu Interactif">
          <div class="bg-slate-50 dark:bg-slate-900/40 p-6 rounded-2xl border border-slate-100 dark:border-slate-800/80 mb-6 flex flex-wrap gap-4 items-center justify-center">
            <ButtonDelete
              :tooltip="buttonTooltip"
              :label="buttonLabel"
              @confirm-delete="logButtonAction('Suppression')"
            />
            <ButtonEdit
              :tooltip="'Modifier l\'élément'"
              @click="logButtonAction('Édition')"
            />
            <ButtonSave
              :tooltip="'Enregistrer les modifications'"
              @confirm-save="logButtonAction('Enregistrement')"
            />
            <ButtonInfo
              :tooltip="'Plus d\'informations'"
              @click="logButtonAction('Info')"
            />
            <ButtonDuplicate
              :tooltip="'Dupliquer l\'élément'"
              @confirm-duplicate="logButtonAction('Duplication')"
            />
          </div>

          <div v-if="lastActionLog" class="p-3 bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 rounded-xl text-xs text-center border border-slate-200/50 dark:border-slate-700/50 mb-6 font-mono">
            {{ lastActionLog }}
          </div>

          <!-- Controls -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6 bg-slate-50/50 dark:bg-slate-800/20 p-5 rounded-2xl border border-slate-100 dark:border-slate-800/40">
            <div>
              <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider block mb-1">Infobulle (Tooltip) du bouton Delete</label>
              <input v-model="buttonTooltip" type="text" class="w-full p-2.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm" />
            </div>
            <div>
              <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider block mb-1">Libellé (Label) du bouton Delete</label>
              <input v-model="buttonLabel" type="text" class="w-full p-2.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm" />
            </div>
          </div>
        </Card>

        <!-- Code Snippet -->
        <Card title="Code d'intégration">
          <div class="relative">
            <pre class="bg-slate-950 text-slate-200 p-5 rounded-2xl overflow-x-auto text-xs leading-relaxed font-mono"><code>{{ buttonsCode }}</code></pre>
            <button
              @click="copyCode(buttonsCode)"
              class="absolute top-3 right-3 bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white p-2 rounded-xl text-xs flex items-center gap-1.5 transition-all duration-200 border border-slate-700/50"
            >
              <CheckIcon v-if="copiedText" class="w-4 h-4 text-green-400" />
              <ClipboardIcon v-else class="w-4 h-4" />
              {{ copiedText ? 'Copié !' : 'Copier' }}
            </button>
          </div>
        </Card>
      </div>

      <!-- 8. FORMULAIRES & VALIDATIONS -->
      <div v-if="activeComponent === 'forms'" class="space-y-6">
        <div class="card p-6 bg-gradient-to-br from-primary-500/5 to-transparent border border-primary-500/10 rounded-2xl">
          <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Formulaires & Validations réactives</h2>
          <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
            Fournit des champs de saisie auto-validés (`ValidatedInput`) gérant de nombreux types (texte, nombre, mot de passe, listes, date) et intégrant le système de règles globales.
          </p>
        </div>

        <!-- Live Demo -->
        <Card title="Aperçu Interactif">
          <div class="bg-slate-50 dark:bg-slate-900/40 p-6 rounded-2xl border border-slate-100 dark:border-slate-800/80 mb-6 max-w-md mx-auto">
            <ValidatedInput
              :type="inputProps.type"
              :label="inputProps.label"
              :placeholder="inputProps.placeholder"
              :rules="inputProps.rules"
              v-model="inputProps.value"
              :options="inputOptions"
              name="styleguide-input"
            />
            <div class="mt-4 p-2.5 bg-slate-100 dark:bg-slate-800 rounded-lg text-xs font-mono text-slate-600 dark:text-slate-400">
              Valeur actuelle : "{{ inputProps.value }}"
            </div>
          </div>

          <!-- Controls -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6 bg-slate-50/50 dark:bg-slate-800/20 p-5 rounded-2xl border border-slate-100 dark:border-slate-800/40">
            <div class="space-y-4">
              <div>
                <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider block mb-1">Type de saisie (input)</label>
                <select v-model="inputProps.type" class="w-full p-2.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm">
                  <option value="text">Texte classique (text)</option>
                  <option value="number">Numérique (number)</option>
                  <option value="password">Mot de passe (password)</option>
                  <option value="textarea">Zone de texte (textarea)</option>
                  <option value="select">Liste déroulante (select)</option>
                  <option value="multiselect">Sélection multiple (multiselect)</option>
                  <option value="date">Date (date)</option>
                </select>
              </div>
              <div>
                <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider block mb-1">Libellé (Label)</label>
                <input v-model="inputProps.label" type="text" class="w-full p-2.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm" />
              </div>
            </div>
            <div class="space-y-4">
              <div>
                <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider block mb-1">Règles de validation (rules)</label>
                <input v-model="inputProps.rules" type="text" class="w-full p-2.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm" />
                <span class="text-[10px] text-gray-500 mt-1 block">Séparez avec un pipe (ex: "required|email" ou "required|min:3")</span>
              </div>
              <div>
                <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider block mb-1">Texte d'aide (Placeholder)</label>
                <input v-model="inputProps.placeholder" type="text" class="w-full p-2.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm" />
              </div>
            </div>
          </div>
        </Card>

        <!-- Code Snippet -->
        <Card title="Code d'intégration">
          <div class="relative">
            <pre class="bg-slate-950 text-slate-200 p-5 rounded-2xl overflow-x-auto text-xs leading-relaxed font-mono"><code>{{ formsCode }}</code></pre>
            <button
              @click="copyCode(formsCode)"
              class="absolute top-3 right-3 bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white p-2 rounded-xl text-xs flex items-center gap-1.5 transition-all duration-200 border border-slate-700/50"
            >
              <CheckIcon v-if="copiedText" class="w-4 h-4 text-green-400" />
              <ClipboardIcon v-else class="w-4 h-4" />
              {{ copiedText ? 'Copié !' : 'Copier' }}
            </button>
          </div>
        </Card>

        <!-- Additional components in forms -->
        <Card title="Autres composants de formulaire">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="border border-slate-200/60 dark:border-slate-700/60 p-5 rounded-2xl bg-slate-50/20">
              <h3 class="font-bold text-gray-900 dark:text-white text-sm mb-1">AddressAutocomplete</h3>
              <p class="text-xs text-gray-500 mb-4">Champ d'autocomplétion géographique interfacé avec l'API Adresse.</p>
              <pre class="bg-slate-950 text-slate-200 p-3 rounded-xl overflow-x-auto text-[11px] font-mono leading-relaxed"><code>&lt;AddressAutocomplete
  v-model="adresse"
  country="fr"
/&gt;</code></pre>
            </div>
            <div class="border border-slate-200/60 dark:border-slate-700/60 p-5 rounded-2xl bg-slate-50/20">
              <h3 class="font-bold text-gray-900 dark:text-white text-sm mb-1">ExampleValidatedForm</h3>
              <p class="text-xs text-gray-500 mb-4">Formulaire complet pré-configuré démontrant l'utilisation du validateur global.</p>
              <pre class="bg-slate-950 text-slate-200 p-3 rounded-xl overflow-x-auto text-[11px] font-mono leading-relaxed"><code>&lt;ExampleValidatedForm /&gt;</code></pre>
            </div>
          </div>
        </Card>
      </div>

    </div>
  </div>
</template>

<style scoped>
pre {
  white-space: pre-wrap;
  word-wrap: break-word;
}
</style>
