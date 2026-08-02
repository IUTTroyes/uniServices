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
  EmptyState,
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
const activeComponent = ref<'header' | 'card' | 'kpi' | 'quick-action' | 'action-btn' | 'alert' | 'empty-state' | 'buttons' | 'forms'>('header');
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
  subtitle: 'Données mises à jour en temps réel',
  icon: 'pi pi-chart-bar',
  color: 'blue',
  badge: 'Actif',
  badgeSeverity: 'success',
  bodyClass: 'p-6',
  content: 'Contenu principal de la carte. Vous pouvez insérer n\'importe quel élément ici.',
  useHeaderSlot: false
});

const cardCode = computed(() => {
  const bodyClassStr = cardProps.value.bodyClass ? ` body-class="${cardProps.value.bodyClass}"` : '';
  if (cardProps.value.useHeaderSlot) {
    return `<Card${bodyClassStr}>
  <template #header>
    <div class="flex items-center justify-between w-full">
      <div class="flex items-center gap-3">
        <i class="${cardProps.value.icon} text-primary-500 text-lg" />
        <div>
          <h3 class="text-sm font-bold text-slate-900 dark:text-white leading-snug">${cardProps.value.title}</h3>
          <p class="text-[11px] text-slate-400 mt-0.5">${cardProps.value.subtitle}</p>
        </div>
      </div>
      <div class="flex items-center gap-2">
        <Button icon="pi pi-refresh" severity="secondary" rounded text size="small" />
        <Button icon="pi pi-ellipsis-v" severity="secondary" rounded text size="small" />
      </div>
    </div>
  </template>
  <p>${cardProps.value.content}</p>
</Card>`;
  }

  const subStr = cardProps.value.subtitle ? `\n  subtitle="${cardProps.value.subtitle}"` : '';
  const iconStr = cardProps.value.icon ? `\n  icon="${cardProps.value.icon}"` : '';
  const colorStr = cardProps.value.color ? `\n  color="${cardProps.value.color}"` : '';
  const badgeStr = cardProps.value.badge ? `\n  badge="${cardProps.value.badge}"\n  badge-severity="${cardProps.value.badgeSeverity}"` : '';

  return `<Card
  title="${cardProps.value.title}"${subStr}${iconStr}${colorStr}${badgeStr}${bodyClassStr}
>
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

// 6b. EmptyState
const emptyStateProps = ref({
  title: 'Pas de données disponibles',
  description: 'Aucun élément n\'a été trouvé pour le moment dans cette section.',
  icon: 'pi pi-inbox',
  color: 'gray',
  compact: false,
  actionLabel: 'Créer un élément',
  actionIcon: 'pi pi-plus'
});

const emptyStateCode = computed(() => {
  const compactStr = emptyStateProps.value.compact ? '\n  compact' : '';
  const colorStr = emptyStateProps.value.color !== 'gray' ? `\n  color="${emptyStateProps.value.color}"` : '';
  const actionLabelStr = emptyStateProps.value.actionLabel ? `\n  action-label="${emptyStateProps.value.actionLabel}"` : '';
  const actionIconStr = emptyStateProps.value.actionIcon ? `\n  action-icon="${emptyStateProps.value.actionIcon}"` : '';

  return `<EmptyState
  title="${emptyStateProps.value.title}"
  description="${emptyStateProps.value.description}"
  icon="${emptyStateProps.value.icon}"${colorStr}${compactStr}${actionLabelStr}${actionIconStr}
  @action="onActionClick"
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
          <button
            @click="activeComponent = 'empty-state'"
            :class="['w-full text-left px-4 py-2.5 rounded-xl text-sm font-semibold transition-all duration-200 flex items-center gap-2',
                     activeComponent === 'empty-state' ? 'bg-primary-500 text-white shadow-md' : 'text-gray-700 dark:text-gray-300 hover:bg-slate-100 dark:hover:bg-slate-700/50']"
          >
            <i class="pi pi-inbox text-sm" />
            EmptyState
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

        <!-- Properties Table -->
        <Card title="Propriétés & Configuration">
          <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
              <thead>
                <tr class="border-b border-slate-200 dark:border-slate-700/80">
                  <th class="py-3 px-4 font-semibold text-slate-900 dark:text-white w-1/4">Propriété</th>
                  <th class="py-3 px-4 font-semibold text-slate-900 dark:text-white w-1/5">Type</th>
                  <th class="py-3 px-4 font-semibold text-slate-900 dark:text-white w-1/6">Défaut</th>
                  <th class="py-3 px-4 font-semibold text-slate-900 dark:text-white w-1/12">Requis</th>
                  <th class="py-3 px-4 font-semibold text-slate-900 dark:text-white">Description & Valeurs</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
                <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/10">
                  <td class="py-3 px-4 font-mono text-primary-600 dark:text-primary-400 font-semibold">titre</td>
                  <td class="py-3 px-4 font-mono text-slate-600 dark:text-slate-400">String</td>
                  <td class="py-3 px-4 font-mono text-slate-500">—</td>
                  <td class="py-3 px-4 text-red-500 font-semibold">Oui</td>
                  <td class="py-3 px-4 text-slate-600 dark:text-slate-400">Le titre principal affiché dans l'en-tête de la page.</td>
                </tr>
                <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/10">
                  <td class="py-3 px-4 font-mono text-primary-600 dark:text-primary-400 font-semibold">description</td>
                  <td class="py-3 px-4 font-mono text-slate-600 dark:text-slate-400">String</td>
                  <td class="py-3 px-4 font-mono text-slate-500">—</td>
                  <td class="py-3 px-4 text-red-500 font-semibold">Oui</td>
                  <td class="py-3 px-4 text-slate-600 dark:text-slate-400">Le texte explicatif ou descriptif situé sous le titre.</td>
                </tr>
                <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/10">
                  <td class="py-3 px-4 font-mono text-primary-600 dark:text-primary-400 font-semibold">icon</td>
                  <td class="py-3 px-4 font-mono text-slate-600 dark:text-slate-400">String | Object | Function</td>
                  <td class="py-3 px-4 font-mono text-slate-500">null</td>
                  <td class="py-3 px-4 text-slate-500">Non</td>
                  <td class="py-3 px-4 text-slate-600 dark:text-slate-400">Icône décorative. Peut être une chaîne (ex: classe PrimeIcons <code class="font-mono text-xs">pi pi-user</code>) ou un composant d'icône (ex: Heroicons).</td>
                </tr>
                <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/10">
                  <td class="py-3 px-4 font-mono text-primary-600 dark:text-primary-400 font-semibold">backUrl</td>
                  <td class="py-3 px-4 font-mono text-slate-600 dark:text-slate-400">String | Object</td>
                  <td class="py-3 px-4 font-mono text-slate-500">null</td>
                  <td class="py-3 px-4 text-slate-500">Non</td>
                  <td class="py-3 px-4 text-slate-600 dark:text-slate-400">L'URL ou l'objet route pour le bouton de retour. Si non spécifié, utilise l'historique du navigateur (<code class="font-mono text-xs">router.go(-1)</code>).</td>
                </tr>
                <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/10">
                  <td class="py-3 px-4 font-mono text-primary-600 dark:text-primary-400 font-semibold">showBack</td>
                  <td class="py-3 px-4 font-mono text-slate-600 dark:text-slate-400">Boolean</td>
                  <td class="py-3 px-4 font-mono text-slate-500">true</td>
                  <td class="py-3 px-4 text-slate-500">Non</td>
                  <td class="py-3 px-4 text-slate-600 dark:text-slate-400">Indique si le bouton "Retour" doit être affiché.</td>
                </tr>
                <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/10">
                  <td class="py-3 px-4 font-mono text-primary-600 dark:text-primary-400 font-semibold">color</td>
                  <td class="py-3 px-4 font-mono text-slate-600 dark:text-slate-400">String</td>
                  <td class="py-3 px-4 font-mono text-slate-500">''</td>
                  <td class="py-3 px-4 text-slate-500">Non</td>
                  <td class="py-3 px-4 text-slate-600 dark:text-slate-400">Clé de couleur thématique pour styliser le fond et le texte de l'icône (ex: <code class="font-mono text-xs">'blue'</code>, <code class="font-mono text-xs">'green'</code>, <code class="font-mono text-xs">'emerald'</code>, etc.).</td>
                </tr>
                <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/10">
                  <td class="py-3 px-4 font-mono text-primary-600 dark:text-primary-400 font-semibold">iconClass</td>
                  <td class="py-3 px-4 font-mono text-slate-600 dark:text-slate-400">String</td>
                  <td class="py-3 px-4 font-mono text-slate-500">''</td>
                  <td class="py-3 px-4 text-slate-500">Non</td>
                  <td class="py-3 px-4 text-slate-600 dark:text-slate-400">Classes CSS additionnelles appliquées directement à l'icône.</td>
                </tr>
                <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/10">
                  <td class="py-3 px-4 font-mono text-primary-600 dark:text-primary-400 font-semibold">iconBgClass</td>
                  <td class="py-3 px-4 font-mono text-slate-600 dark:text-slate-400">String</td>
                  <td class="py-3 px-4 font-mono text-slate-500">''</td>
                  <td class="py-3 px-4 text-slate-505">Non</td>
                  <td class="py-3 px-4 text-slate-600 dark:text-slate-400">Classes CSS additionnelles appliquées au conteneur de l'icône.</td>
                </tr>
              </tbody>
            </table>
          </div>
          <div class="mt-4 pt-4 border-t border-slate-100 dark:border-slate-800/60 text-xs">
            <span class="font-semibold text-slate-800 dark:text-slate-200">Slots disponibles :</span>
            <ul class="list-disc list-inside mt-2 space-y-1 text-slate-600 dark:text-slate-400">
              <li><code class="font-mono text-primary-600 dark:text-primary-400 font-semibold">actions</code> : Permet d'insérer des boutons ou actions personnalisés à l'extrême droite du header.</li>
            </ul>
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
            <Card
              v-if="!cardProps.useHeaderSlot"
              :title="cardProps.title"
              :subtitle="cardProps.subtitle"
              :icon="cardProps.icon"
              :color="cardProps.color"
              :badge="cardProps.badge"
              :badge-severity="cardProps.badgeSeverity"
              :body-class="cardProps.bodyClass"
            >
              <p class="text-sm text-gray-700 dark:text-gray-300">{{ cardProps.content }}</p>
            </Card>

            <Card
              v-else
              :body-class="cardProps.bodyClass"
            >
              <template #header>
                <div class="flex items-center justify-between w-full">
                  <div class="flex items-center gap-3">
                    <span v-if="cardProps.icon" class="flex-shrink-0 w-8 h-8 rounded-lg flex items-center justify-center border bg-blue-50 dark:bg-blue-950/20 text-blue-600 dark:text-blue-400 border-blue-100 dark:border-blue-900/30">
                      <i :class="[cardProps.icon, 'text-sm']" />
                    </span>
                    <div>
                      <h3 class="text-sm font-bold text-slate-900 dark:text-white leading-snug">{{ cardProps.title }}</h3>
                      <p v-if="cardProps.subtitle" class="text-[11px] text-slate-400 mt-0.5">{{ cardProps.subtitle }}</p>
                    </div>
                  </div>
                  <div class="flex items-center gap-2">
                    <Button icon="pi pi-refresh" severity="secondary" rounded text size="small" />
                    <Button icon="pi pi-ellipsis-v" severity="secondary" rounded text size="small" />
                  </div>
                </div>
              </template>
              <p class="text-sm text-gray-700 dark:text-gray-300">{{ cardProps.content }}</p>
            </Card>
          </div>

          <!-- Controls -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6 bg-slate-50/50 dark:bg-slate-800/20 p-5 rounded-2xl border border-slate-100 dark:border-slate-800/40">
            <div class="col-span-1 md:col-span-2 flex items-center gap-4">
              <label class="flex items-center gap-2 text-sm font-semibold text-gray-700 dark:text-gray-300 cursor-pointer">
                <input v-model="cardProps.useHeaderSlot" type="checkbox" class="w-4 h-4 rounded text-primary-500 focus:ring-primary-500" />
                Utiliser le slot #header (démonstration avec boutons d'actions)
              </label>
            </div>
            <div>
              <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider block mb-1">Titre de la carte</label>
              <input v-model="cardProps.title" type="text" class="w-full p-2.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm" />
            </div>
            <div>
              <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider block mb-1">Sous-titre de la carte</label>
              <input v-model="cardProps.subtitle" type="text" class="w-full p-2.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm" />
            </div>
            <div>
              <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider block mb-1">Icône (PrimeIcons)</label>
              <input v-model="cardProps.icon" type="text" class="w-full p-2.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm" />
            </div>
            <div v-if="!cardProps.useHeaderSlot">
              <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider block mb-1">Thème de Couleur de l'icône</label>
              <select v-model="cardProps.color" class="w-full p-2.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm">
                <option value="">Aucun</option>
                <option value="blue">Blue</option>
                <option value="green">Green</option>
                <option value="emerald">Emerald</option>
                <option value="yellow">Yellow</option>
                <option value="purple">Purple</option>
                <option value="red">Red</option>
                <option value="orange">Orange</option>
              </select>
            </div>
            <div v-if="!cardProps.useHeaderSlot">
              <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider block mb-1">Badge de statut</label>
              <input v-model="cardProps.badge" type="text" class="w-full p-2.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm" />
            </div>
            <div v-if="!cardProps.useHeaderSlot && cardProps.badge">
              <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider block mb-1">Sévérité du badge</label>
              <select v-model="cardProps.badgeSeverity" class="w-full p-2.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm">
                <option value="secondary">Secondary (Gris)</option>
                <option value="primary">Primary (Orange)</option>
                <option value="success">Success (Vert)</option>
                <option value="info">Info (Bleu)</option>
                <option value="warning">Warning (Jaune)</option>
                <option value="danger">Danger (Rouge)</option>
              </select>
            </div>
            <div>
              <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider block mb-1">Classes de corps (bodyClass)</label>
              <input v-model="cardProps.bodyClass" type="text" class="w-full p-2.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm" />
            </div>
            <div class="col-span-1 md:col-span-2">
              <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider block mb-1">Contenu intérieur</label>
              <textarea v-model="cardProps.content" rows="2" class="w-full p-2.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm"></textarea>
            </div>
          </div>
        </Card>

        <!-- Properties Table -->
        <Card title="Propriétés & Configuration">
          <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
              <thead>
                <tr class="border-b border-slate-200 dark:border-slate-700/80">
                  <th class="py-3 px-4 font-semibold text-slate-900 dark:text-white w-1/4">Propriété</th>
                  <th class="py-3 px-4 font-semibold text-slate-900 dark:text-white w-1/5">Type</th>
                  <th class="py-3 px-4 font-semibold text-slate-900 dark:text-white w-1/6">Défaut</th>
                  <th class="py-3 px-4 font-semibold text-slate-900 dark:text-white w-1/12">Requis</th>
                  <th class="py-3 px-4 font-semibold text-slate-900 dark:text-white">Description & Valeurs</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
                <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/10">
                  <td class="py-3 px-4 font-mono text-primary-600 dark:text-primary-400 font-semibold">title</td>
                  <td class="py-3 px-4 font-mono text-slate-600 dark:text-slate-400">String</td>
                  <td class="py-3 px-4 font-mono text-slate-500">''</td>
                  <td class="py-3 px-4 text-slate-500">Non</td>
                  <td class="py-3 px-4 text-slate-600 dark:text-slate-400">Le titre principal affiché dans l'en-tête de la carte.</td>
                </tr>
                <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/10">
                  <td class="py-3 px-4 font-mono text-primary-600 dark:text-primary-400 font-semibold">subtitle</td>
                  <td class="py-3 px-4 font-mono text-slate-600 dark:text-slate-400">String</td>
                  <td class="py-3 px-4 font-mono text-slate-505">''</td>
                  <td class="py-3 px-4 text-slate-500">Non</td>
                  <td class="py-3 px-4 text-slate-600 dark:text-slate-400">Sous-titre affiché sous le titre.</td>
                </tr>
                <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/10">
                  <td class="py-3 px-4 font-mono text-primary-600 dark:text-primary-400 font-semibold">icon</td>
                  <td class="py-3 px-4 font-mono text-slate-600 dark:text-slate-400">String | Object | Function</td>
                  <td class="py-3 px-4 font-mono text-slate-505">null</td>
                  <td class="py-3 px-4 text-slate-505">Non</td>
                  <td class="py-3 px-4 text-slate-600 dark:text-slate-400">Icône de titre (PrimeIcons string ou composant Heroicons).</td>
                </tr>
                <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/10">
                  <td class="py-3 px-4 font-mono text-primary-600 dark:text-primary-400 font-semibold">color</td>
                  <td class="py-3 px-4 font-mono text-slate-600 dark:text-slate-400">String</td>
                  <td class="py-3 px-4 font-mono text-slate-505">''</td>
                  <td class="py-3 px-4 text-slate-505">Non</td>
                  <td class="py-3 px-4 text-slate-600 dark:text-slate-400">Clé de couleur thématique pour l'icône (ex: <code class="font-mono text-xs">'blue'</code>, <code class="font-mono text-xs">'green'</code>, etc.).</td>
                </tr>
                <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/10">
                  <td class="py-3 px-4 font-mono text-primary-600 dark:text-primary-400 font-semibold">iconClass</td>
                  <td class="py-3 px-4 font-mono text-slate-600 dark:text-slate-400">String</td>
                  <td class="py-3 px-4 font-mono text-slate-505">''</td>
                  <td class="py-3 px-4 text-slate-505">Non</td>
                  <td class="py-3 px-4 text-slate-600 dark:text-slate-400">Classes CSS additionnelles appliquées à l'icône.</td>
                </tr>
                <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/10">
                  <td class="py-3 px-4 font-mono text-primary-600 dark:text-primary-400 font-semibold">iconBgClass</td>
                  <td class="py-3 px-4 font-mono text-slate-600 dark:text-slate-400">String</td>
                  <td class="py-3 px-4 font-mono text-slate-505">''</td>
                  <td class="py-3 px-4 text-slate-505">Non</td>
                  <td class="py-3 px-4 text-slate-600 dark:text-slate-400">Classes CSS additionnelles appliquées au badge conteneur de l'icône.</td>
                </tr>
                <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/10">
                  <td class="py-3 px-4 font-mono text-primary-600 dark:text-primary-400 font-semibold">badge</td>
                  <td class="py-3 px-4 font-mono text-slate-600 dark:text-slate-400">String</td>
                  <td class="py-3 px-4 font-mono text-slate-505">''</td>
                  <td class="py-3 px-4 text-slate-505">Non</td>
                  <td class="py-3 px-4 text-slate-600 dark:text-slate-400">Texte d'un badge à afficher à l'extrémité droite de l'en-tête.</td>
                </tr>
                <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/10">
                  <td class="py-3 px-4 font-mono text-primary-600 dark:text-primary-400 font-semibold">badgeSeverity</td>
                  <td class="py-3 px-4 font-mono text-slate-600 dark:text-slate-400">String</td>
                  <td class="py-3 px-4 font-mono text-slate-505">'secondary'</td>
                  <td class="py-3 px-4 text-slate-505">Non</td>
                  <td class="py-3 px-4 text-slate-600 dark:text-slate-400">Sévérité visuelle du badge (<code class="font-mono text-xs">'success'</code>, <code class="font-mono text-xs">'info'</code>, <code class="font-mono text-xs">'warning'</code>, <code class="font-mono text-xs">'danger'</code>, <code class="font-mono text-xs">'primary'</code>, <code class="font-mono text-xs">'secondary'</code>).</td>
                </tr>
                <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/10">
                  <td class="py-3 px-4 font-mono text-primary-600 dark:text-primary-400 font-semibold">bodyClass</td>
                  <td class="py-3 px-4 font-mono text-slate-600 dark:text-slate-400">String</td>
                  <td class="py-3 px-4 font-mono text-slate-505">''</td>
                  <td class="py-3 px-4 text-slate-505">Non</td>
                  <td class="py-3 px-4 text-slate-600 dark:text-slate-400">Classes CSS additionnelles appliquées au corps intérieur de la carte.</td>
                </tr>
              </tbody>
            </table>
          </div>
          <div class="mt-4 pt-4 border-t border-slate-100 dark:border-slate-800/60 text-xs">
            <span class="font-semibold text-slate-800 dark:text-slate-200">Slots disponibles :</span>
            <ul class="list-disc list-inside mt-2 space-y-1 text-slate-600 dark:text-slate-400">
              <li><code class="font-mono text-primary-600 dark:text-primary-400 font-semibold">header</code> : Permet de remplacer entièrement l'en-tête de la carte.</li>
              <li><code class="font-mono text-primary-600 dark:text-primary-400 font-semibold">default</code> : Slot principal pour y déposer le contenu interne de la carte.</li>
            </ul>
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

        <!-- Properties Table -->
        <Card title="Propriétés & Configuration">
          <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
              <thead>
                <tr class="border-b border-slate-200 dark:border-slate-700/80">
                  <th class="py-3 px-4 font-semibold text-slate-900 dark:text-white w-1/4">Propriété</th>
                  <th class="py-3 px-4 font-semibold text-slate-900 dark:text-white w-1/5">Type</th>
                  <th class="py-3 px-4 font-semibold text-slate-900 dark:text-white w-1/6">Défaut</th>
                  <th class="py-3 px-4 font-semibold text-slate-900 dark:text-white w-1/12">Requis</th>
                  <th class="py-3 px-4 font-semibold text-slate-900 dark:text-white">Description & Valeurs</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
                <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/10">
                  <td class="py-3 px-4 font-mono text-primary-600 dark:text-primary-400 font-semibold">label</td>
                  <td class="py-3 px-4 font-mono text-slate-600 dark:text-slate-400">string</td>
                  <td class="py-3 px-4 font-mono text-slate-505">—</td>
                  <td class="py-3 px-4 text-red-500 font-semibold">Oui</td>
                  <td class="py-3 px-4 text-slate-600 dark:text-slate-400">Le titre de la statistique (affiché en haut, en capitales).</td>
                </tr>
                <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/10">
                  <td class="py-3 px-4 font-mono text-primary-600 dark:text-primary-400 font-semibold">value</td>
                  <td class="py-3 px-4 font-mono text-slate-650">string | number</td>
                  <td class="py-3 px-4 font-mono text-slate-505">—</td>
                  <td class="py-3 px-4 text-red-500 font-semibold">Oui</td>
                  <td class="py-3 px-4 text-slate-600 dark:text-slate-400">La valeur quantitative ou texte principale de la statistique.</td>
                </tr>
                <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/10">
                  <td class="py-3 px-4 font-mono text-primary-600 dark:text-primary-400 font-semibold">icon</td>
                  <td class="py-3 px-4 font-mono text-slate-650">any</td>
                  <td class="py-3 px-4 font-mono text-slate-505">—</td>
                  <td class="py-3 px-4 text-red-500 font-semibold">Oui</td>
                  <td class="py-3 px-4 text-slate-600 dark:text-slate-400">Le composant d'icône Heroicons à afficher à droite.</td>
                </tr>
                <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/10">
                  <td class="py-3 px-4 font-mono text-primary-600 dark:text-primary-400 font-semibold">color</td>
                  <td class="py-3 px-4 font-mono text-slate-650">string</td>
                  <td class="py-3 px-4 font-mono text-slate-505">—</td>
                  <td class="py-3 px-4 text-red-500 font-semibold">Oui</td>
                  <td class="py-3 px-4 text-slate-600 dark:text-slate-400">Identifiant de couleur thématique (ex: <code class="font-mono text-xs">blue, green, yellow, purple, red, indigo, gray, emerald, orange, teal, pink</code>).</td>
                </tr>
                <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/10">
                  <td class="py-3 px-4 font-mono text-primary-600 dark:text-primary-400 font-semibold">description</td>
                  <td class="py-3 px-4 font-mono text-slate-650">string</td>
                  <td class="py-3 px-4 font-mono text-slate-505">undefined</td>
                  <td class="py-3 px-4 text-slate-505">Non</td>
                  <td class="py-3 px-4 text-slate-600 dark:text-slate-400">Libellé informatif ou tendance affiché en dessous de la valeur.</td>
                </tr>
              </tbody>
            </table>
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

        <!-- Properties Table -->
        <Card title="Propriétés & Configuration">
          <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
              <thead>
                <tr class="border-b border-slate-200 dark:border-slate-700/80">
                  <th class="py-3 px-4 font-semibold text-slate-900 dark:text-white w-1/4">Propriété</th>
                  <th class="py-3 px-4 font-semibold text-slate-900 dark:text-white w-1/5">Type</th>
                  <th class="py-3 px-4 font-semibold text-slate-900 dark:text-white w-1/6">Défaut</th>
                  <th class="py-3 px-4 font-semibold text-slate-900 dark:text-white w-1/12">Requis</th>
                  <th class="py-3 px-4 font-semibold text-slate-900 dark:text-white">Description & Valeurs</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
                <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/10">
                  <td class="py-3 px-4 font-mono text-primary-600 dark:text-primary-400 font-semibold">title</td>
                  <td class="py-3 px-4 font-mono text-slate-650">string</td>
                  <td class="py-3 px-4 font-mono text-slate-505">—</td>
                  <td class="py-3 px-4 text-red-500 font-semibold">Oui</td>
                  <td class="py-3 px-4 text-slate-600 dark:text-slate-400">Le titre principal de la carte d'action.</td>
                </tr>
                <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/10">
                  <td class="py-3 px-4 font-mono text-primary-600 dark:text-primary-400 font-semibold">description</td>
                  <td class="py-3 px-4 font-mono text-slate-650">string</td>
                  <td class="py-3 px-4 font-mono text-slate-505">—</td>
                  <td class="py-3 px-4 text-red-500 font-semibold">Oui</td>
                  <td class="py-3 px-4 text-slate-600 dark:text-slate-400">Courte description décrivant l'action.</td>
                </tr>
                <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/10">
                  <td class="py-3 px-4 font-mono text-primary-600 dark:text-primary-400 font-semibold">icon</td>
                  <td class="py-3 px-4 font-mono text-slate-650">any</td>
                  <td class="py-3 px-4 font-mono text-slate-505">—</td>
                  <td class="py-3 px-4 text-red-500 font-semibold">Oui</td>
                  <td class="py-3 px-4 text-slate-600 dark:text-slate-400">Composant d'icône Heroicons affiché dans le badge de gauche.</td>
                </tr>
                <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/10">
                  <td class="py-3 px-4 font-mono text-primary-600 dark:text-primary-400 font-semibold">color</td>
                  <td class="py-3 px-4 font-mono text-slate-650">'blue' | 'green' | 'purple' | string</td>
                  <td class="py-3 px-4 font-mono text-slate-505">—</td>
                  <td class="py-3 px-4 text-red-500 font-semibold">Oui</td>
                  <td class="py-3 px-4 text-slate-600 dark:text-slate-400">Choix de la couleur pour l'icône et le style du bouton (<code class="font-mono text-xs">'blue'</code>, <code class="font-mono text-xs">'green'</code>, <code class="font-mono text-xs">'purple'</code>).</td>
                </tr>
                <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/10">
                  <td class="py-3 px-4 font-mono text-primary-600 dark:text-primary-400 font-semibold">buttonLabel</td>
                  <td class="py-3 px-4 font-mono text-slate-650">string</td>
                  <td class="py-3 px-4 font-mono text-slate-505">—</td>
                  <td class="py-3 px-4 text-red-500 font-semibold">Oui</td>
                  <td class="py-3 px-4 text-slate-600 dark:text-slate-400">Le libellé affiché sur le bouton à droite.</td>
                </tr>
                <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/10">
                  <td class="py-3 px-4 font-mono text-primary-600 dark:text-primary-400 font-semibold">to</td>
                  <td class="py-3 px-4 font-mono text-slate-650">string | object</td>
                  <td class="py-3 px-4 font-mono text-slate-500">undefined</td>
                  <td class="py-3 px-4 text-slate-505">Non</td>
                  <td class="py-3 px-4 text-slate-600 dark:text-slate-400">Route ou URL de redirection. Si présent, le composant utilise un <code class="font-mono text-xs">&lt;router-link&gt;</code>. Sinon, c'est une div cliquable émettant l'événement <code class="font-mono text-xs">action</code>.</td>
                </tr>
              </tbody>
            </table>
          </div>
          <div class="mt-4 pt-4 border-t border-slate-100 dark:border-slate-800/60 text-xs">
            <span class="font-semibold text-slate-800 dark:text-slate-200">Événements émis :</span>
            <ul class="list-disc list-inside mt-2 space-y-1 text-slate-600 dark:text-slate-400">
              <li><code class="font-mono text-primary-600 dark:text-primary-400 font-semibold">@action</code> : Déclenché lors du clic sur la carte, uniquement si la propriété <code class="font-mono text-xs">to</code> n'est pas fournie.</li>
            </ul>
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

        <!-- Properties Table -->
        <Card title="Propriétés & Configuration">
          <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
              <thead>
                <tr class="border-b border-slate-200 dark:border-slate-700/80">
                  <th class="py-3 px-4 font-semibold text-slate-900 dark:text-white w-1/4">Propriété</th>
                  <th class="py-3 px-4 font-semibold text-slate-900 dark:text-white w-1/5">Type</th>
                  <th class="py-3 px-4 font-semibold text-slate-900 dark:text-white w-1/6">Défaut</th>
                  <th class="py-3 px-4 font-semibold text-slate-900 dark:text-white w-1/12">Requis</th>
                  <th class="py-3 px-4 font-semibold text-slate-900 dark:text-white">Description & Valeurs</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
                <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/10">
                  <td class="py-3 px-4 font-mono text-primary-600 dark:text-primary-400 font-semibold">label</td>
                  <td class="py-3 px-4 font-mono text-slate-650">string</td>
                  <td class="py-3 px-4 font-mono text-slate-505">—</td>
                  <td class="py-3 px-4 text-red-500 font-semibold">Oui</td>
                  <td class="py-3 px-4 text-slate-600 dark:text-slate-400">Le libellé de texte affiché sous l'icône.</td>
                </tr>
                <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/10">
                  <td class="py-3 px-4 font-mono text-primary-600 dark:text-primary-400 font-semibold">icon</td>
                  <td class="py-3 px-4 font-mono text-slate-650">any</td>
                  <td class="py-3 px-4 font-mono text-slate-505">—</td>
                  <td class="py-3 px-4 text-red-500 font-semibold">Oui</td>
                  <td class="py-3 px-4 text-slate-600 dark:text-slate-400">Le composant d'icône Heroicons affiché en haut.</td>
                </tr>
                <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/10">
                  <td class="py-3 px-4 font-mono text-primary-600 dark:text-primary-400 font-semibold">to</td>
                  <td class="py-3 px-4 font-mono text-slate-650">string | object</td>
                  <td class="py-3 px-4 font-mono text-slate-500">undefined</td>
                  <td class="py-3 px-4 text-slate-505">Non</td>
                  <td class="py-3 px-4 text-slate-600 dark:text-slate-400">Route ou URL de redirection. Si présent, le composant utilise un <code class="font-mono text-xs">&lt;router-link&gt;</code>, sinon un bouton standard.</td>
                </tr>
                <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/10">
                  <td class="py-3 px-4 font-mono text-primary-600 dark:text-primary-400 font-semibold">severity</td>
                  <td class="py-3 px-4 font-mono text-slate-650">'primary' | 'secondary' | 'success' | 'info' | 'warning' | 'help' | 'danger'</td>
                  <td class="py-3 px-4 font-mono text-slate-505">'secondary'</td>
                  <td class="py-3 px-4 text-slate-505">Non</td>
                  <td class="py-3 px-4 text-slate-600 dark:text-slate-400">Style visuel et palette de couleur du bouton.</td>
                </tr>
                <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/10">
                  <td class="py-3 px-4 font-mono text-primary-600 dark:text-primary-400 font-semibold">disabled</td>
                  <td class="py-3 px-4 font-mono text-slate-650">boolean</td>
                  <td class="py-3 px-4 font-mono text-slate-500">false</td>
                  <td class="py-3 px-4 text-slate-505">Non</td>
                  <td class="py-3 px-4 text-slate-600 dark:text-slate-400">Indique si le bouton doit être désactivé (bloque le clic et applique une transparence).</td>
                </tr>
              </tbody>
            </table>
          </div>
          <div class="mt-4 pt-4 border-t border-slate-100 dark:border-slate-800/60 text-xs">
            <span class="font-semibold text-slate-800 dark:text-slate-200">Événements émis :</span>
            <ul class="list-disc list-inside mt-2 space-y-1 text-slate-600 dark:text-slate-400">
              <li><code class="font-mono text-primary-600 dark:text-primary-400 font-semibold">@click</code> : Déclenché lors du clic sur le bouton, uniquement s'il n'est pas désactivé.</li>
            </ul>
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

        <!-- Properties Table -->
        <Card title="Propriétés & Configuration">
          <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
              <thead>
                <tr class="border-b border-slate-200 dark:border-slate-700/80">
                  <th class="py-3 px-4 font-semibold text-slate-900 dark:text-white w-1/4">Propriété</th>
                  <th class="py-3 px-4 font-semibold text-slate-900 dark:text-white w-1/5">Type</th>
                  <th class="py-3 px-4 font-semibold text-slate-900 dark:text-white w-1/6">Défaut</th>
                  <th class="py-3 px-4 font-semibold text-slate-900 dark:text-white w-1/12">Requis</th>
                  <th class="py-3 px-4 font-semibold text-slate-900 dark:text-white">Description & Valeurs</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
                <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/10">
                  <td class="py-3 px-4 font-mono text-primary-600 dark:text-primary-400 font-semibold">severity</td>
                  <td class="py-3 px-4 font-mono text-slate-650">String</td>
                  <td class="py-3 px-4 font-mono text-slate-505">'info'</td>
                  <td class="py-3 px-4 text-slate-505">Non</td>
                  <td class="py-3 px-4 text-slate-600 dark:text-slate-400">Sévérité du message. Valeurs possibles: <code class="font-mono text-xs">'info'</code>, <code class="font-mono text-xs">'success'</code>, <code class="font-mono text-xs">'warning'</code>, <code class="font-mono text-xs">'error'</code>.</td>
                </tr>
                <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/10">
                  <td class="py-3 px-4 font-mono text-primary-600 dark:text-primary-400 font-semibold">icon</td>
                  <td class="py-3 px-4 font-mono text-slate-650">String</td>
                  <td class="py-3 px-4 font-mono text-slate-500">null</td>
                  <td class="py-3 px-4 text-slate-505">Non</td>
                  <td class="py-3 px-4 text-slate-600 dark:text-slate-400">Classe PrimeIcons personnalisée (ex: <code class="font-mono text-xs">'pi pi-exclamation-circle'</code>) permettant d'écraser l'icône automatiquement calculée selon la sévérité.</td>
                </tr>
                <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/10">
                  <td class="py-3 px-4 font-mono text-primary-600 dark:text-primary-400 font-semibold">message</td>
                  <td class="py-3 px-4 font-mono text-slate-650">String</td>
                  <td class="py-3 px-4 font-mono text-slate-500">null</td>
                  <td class="py-3 px-4 text-slate-505">Non</td>
                  <td class="py-3 px-4 text-slate-600 dark:text-slate-400">Le texte du message à afficher (ignoré si le slot par défaut est fourni).</td>
                </tr>
                <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/10">
                  <td class="py-3 px-4 font-mono text-primary-600 dark:text-primary-400 font-semibold">closable</td>
                  <td class="py-3 px-4 font-mono text-slate-650">Boolean</td>
                  <td class="py-3 px-4 font-mono text-slate-505">true</td>
                  <td class="py-3 px-4 text-slate-505">Non</td>
                  <td class="py-3 px-4 text-slate-600 dark:text-slate-400">Indique si le message peut être fermé à l'aide d'un bouton de fermeture.</td>
                </tr>
              </tbody>
            </table>
          </div>
          <div class="mt-4 pt-4 border-t border-slate-100 dark:border-slate-800/60 text-xs">
            <span class="font-semibold text-slate-800 dark:text-slate-200">Slots disponibles :</span>
            <ul class="list-disc list-inside mt-2 space-y-1 text-slate-600 dark:text-slate-400">
              <li><code class="font-mono text-primary-600 dark:text-primary-400 font-semibold">default</code> : Permet de définir un contenu HTML complexe à l'intérieur du message à la place de la prop <code class="font-mono text-xs">message</code>.</li>
            </ul>
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

      <!-- 6b. EMPTYSTATE DOCUMENTATION -->
      <div v-if="activeComponent === 'empty-state'" class="space-y-6">
        <div class="card p-6 bg-gradient-to-br from-primary-500/5 to-transparent border border-primary-500/10 rounded-2xl">
          <h2 class="text-2xl font-bold text-gray-900 dark:text-white">EmptyState</h2>
          <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
            Composant harmonisé pour signaler l'absence de données (périodes, offres, documents, résultats) avec icône centrée, typographie soignée et bouton d'action optionnel.
          </p>
        </div>

        <!-- Live Demo -->
        <Card title="Aperçu Interactif">
          <div class="bg-slate-50 dark:bg-slate-900/40 p-6 rounded-2xl border border-slate-100 dark:border-slate-800/80 mb-6">
            <EmptyState
              :title="emptyStateProps.title"
              :description="emptyStateProps.description"
              :icon="emptyStateProps.icon"
              :color="emptyStateProps.color"
              :compact="emptyStateProps.compact"
              :action-label="emptyStateProps.actionLabel"
              :action-icon="emptyStateProps.actionIcon"
              @action="logButtonAction('Clic sur le bouton d\'action EmptyState')"
            />
          </div>

          <!-- Controls -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6 bg-slate-50/50 dark:bg-slate-800/20 p-5 rounded-2xl border border-slate-100 dark:border-slate-800/40 text-xs">
            <div class="space-y-4">
              <div>
                <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider block mb-1">Titre (title)</label>
                <input v-model="emptyStateProps.title" type="text" class="w-full p-2.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm" />
              </div>
              <div>
                <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider block mb-1">Description</label>
                <input v-model="emptyStateProps.description" type="text" class="w-full p-2.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm" />
              </div>
              <div>
                <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider block mb-1">Icône (PrimeIcons)</label>
                <input v-model="emptyStateProps.icon" type="text" class="w-full p-2.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm font-mono" />
              </div>
            </div>

            <div class="space-y-4">
              <div>
                <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider block mb-1">Thème Couleur (color)</label>
                <select v-model="emptyStateProps.color" class="w-full p-2.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm">
                  <option value="gray">Gray / Slate (Défaut)</option>
                  <option value="violet">Violet</option>
                  <option value="emerald">Emerald</option>
                  <option value="amber">Amber</option>
                  <option value="blue">Blue</option>
                  <option value="indigo">Indigo</option>
                  <option value="rose">Rose</option>
                </select>
              </div>

              <div>
                <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider block mb-1">Texte du bouton d'action (actionLabel)</label>
                <input v-model="emptyStateProps.actionLabel" type="text" class="w-full p-2.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm" />
              </div>

              <div class="flex items-center gap-4 pt-2">
                <label class="flex items-center gap-2 text-sm font-semibold text-gray-700 dark:text-gray-300 cursor-pointer">
                  <input v-model="emptyStateProps.compact" type="checkbox" class="w-4 h-4 rounded text-primary-500 focus:ring-primary-500" />
                  Mode Compact (compact)
                </label>
              </div>
            </div>
          </div>
        </Card>

        <!-- Properties Table -->
        <Card title="Propriétés & Configuration">
          <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
              <thead>
                <tr class="border-b border-slate-200 dark:border-slate-700/80">
                  <th class="py-3 px-4 font-semibold text-slate-900 dark:text-white w-1/4">Propriété</th>
                  <th class="py-3 px-4 font-semibold text-slate-900 dark:text-white w-1/5">Type</th>
                  <th class="py-3 px-4 font-semibold text-slate-900 dark:text-white w-1/6">Défaut</th>
                  <th class="py-3 px-4 font-semibold text-slate-900 dark:text-white w-1/12">Requis</th>
                  <th class="py-3 px-4 font-semibold text-slate-900 dark:text-white">Description & Valeurs</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
                <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/10">
                  <td class="py-3 px-4 font-mono text-primary-600 dark:text-primary-400 font-semibold">title</td>
                  <td class="py-3 px-4 font-mono text-slate-650">String</td>
                  <td class="py-3 px-4 font-mono text-slate-500">'Aucune donnée disponible'</td>
                  <td class="py-3 px-4 text-slate-505">Non</td>
                  <td class="py-3 px-4 text-slate-600 dark:text-slate-400">Titre principal indiquant l'état vide.</td>
                </tr>
                <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/10">
                  <td class="py-3 px-4 font-mono text-primary-600 dark:text-primary-400 font-semibold">description</td>
                  <td class="py-3 px-4 font-mono text-slate-650">String</td>
                  <td class="py-3 px-4 font-mono text-slate-500">''</td>
                  <td class="py-3 px-4 text-slate-505">Non</td>
                  <td class="py-3 px-4 text-slate-600 dark:text-slate-400">Explication complémentaire. Peut aussi être passée via le slot par défaut.</td>
                </tr>
                <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/10">
                  <td class="py-3 px-4 font-mono text-primary-600 dark:text-primary-400 font-semibold">icon</td>
                  <td class="py-3 px-4 font-mono text-slate-650">String | Component</td>
                  <td class="py-3 px-4 font-mono text-slate-500">'pi pi-inbox'</td>
                  <td class="py-3 px-4 text-slate-505">Non</td>
                  <td class="py-3 px-4 text-slate-600 dark:text-slate-400">Icône PrimeVue (ex: <code class="font-mono text-xs">'pi pi-calendar-times'</code>) ou composant Heroicon.</td>
                </tr>
                <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/10">
                  <td class="py-3 px-4 font-mono text-primary-600 dark:text-primary-400 font-semibold">color</td>
                  <td class="py-3 px-4 font-mono text-slate-650">String</td>
                  <td class="py-3 px-4 font-mono text-slate-500">'gray'</td>
                  <td class="py-3 px-4 text-slate-505">Non</td>
                  <td class="py-3 px-4 text-slate-600 dark:text-slate-400">Variante de couleur (<code class="font-mono text-xs">'gray'</code>, <code class="font-mono text-xs">'violet'</code>, <code class="font-mono text-xs">'emerald'</code>, <code class="font-mono text-xs">'amber'</code>, <code class="font-mono text-xs">'blue'</code>, <code class="font-mono text-xs">'indigo'</code>, <code class="font-mono text-xs">'rose'</code>).</td>
                </tr>
                <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/10">
                  <td class="py-3 px-4 font-mono text-primary-600 dark:text-primary-400 font-semibold">compact</td>
                  <td class="py-3 px-4 font-mono text-slate-650">Boolean</td>
                  <td class="py-3 px-4 font-mono text-slate-500">false</td>
                  <td class="py-3 px-4 text-slate-505">Non</td>
                  <td class="py-3 px-4 text-slate-600 dark:text-slate-400">Réduit le padding interne et les dimensions de l'icône pour s'intégrer dans des sous-cartes.</td>
                </tr>
                <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/10">
                  <td class="py-3 px-4 font-mono text-primary-600 dark:text-primary-400 font-semibold">actionLabel</td>
                  <td class="py-3 px-4 font-mono text-slate-650">String</td>
                  <td class="py-3 px-4 font-mono text-slate-500">''</td>
                  <td class="py-3 px-4 text-slate-505">Non</td>
                  <td class="py-3 px-4 text-slate-600 dark:text-slate-400">Libellé du bouton d'action affiché. Émet un événement <code class="font-mono text-xs">@action</code>.</td>
                </tr>
              </tbody>
            </table>
          </div>
        </Card>

        <!-- Code Snippet -->
        <Card title="Code d'intégration">
          <div class="relative">
            <pre class="bg-slate-950 text-slate-200 p-5 rounded-2xl overflow-x-auto text-xs leading-relaxed font-mono"><code>{{ emptyStateCode }}</code></pre>
            <button
              @click="copyCode(emptyStateCode)"
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

        <!-- Properties Table -->
        <Card title="Propriétés des Boutons CRUD">
          <div class="space-y-6">
            <div>
              <h3 class="font-semibold text-slate-800 dark:text-slate-200 mb-2 font-mono text-xs">&lt;ButtonDelete&gt;</h3>
              <table class="w-full text-left border-collapse text-xs mb-2">
                <thead>
                  <tr class="border-b border-slate-200 dark:border-slate-700/80">
                    <th class="py-2 px-3 font-semibold text-slate-900 dark:text-white w-1/4">Propriété</th>
                    <th class="py-2 px-3 font-semibold text-slate-900 dark:text-white w-1/5">Type</th>
                    <th class="py-2 px-3 font-semibold text-slate-900 dark:text-white w-1/6">Défaut</th>
                    <th class="py-2 px-3 font-semibold text-slate-900 dark:text-white w-1/12">Requis</th>
                    <th class="py-2 px-3 font-semibold text-slate-900 dark:text-white">Description</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
                  <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/10">
                    <td class="py-2 px-3 font-mono text-primary-600 dark:text-primary-400 font-semibold">tooltip</td>
                    <td class="py-2 px-3 font-mono text-slate-655">String</td>
                    <td class="py-2 px-3 font-mono text-slate-500">—</td>
                    <td class="py-2 px-3 text-red-500 font-semibold">Oui</td>
                    <td class="py-2 px-3 text-slate-600 dark:text-slate-400">Texte de l'infobulle affiché au survol.</td>
                  </tr>
                  <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/10">
                    <td class="py-2 px-3 font-mono text-primary-600 dark:text-primary-400 font-semibold">label</td>
                    <td class="py-2 px-3 font-mono text-slate-655">String</td>
                    <td class="py-2 px-3 font-mono text-slate-500">''</td>
                    <td class="py-2 px-3 text-slate-505">Non</td>
                    <td class="py-2 px-3 text-slate-600 dark:text-slate-400">Libellé textuel optionnel affiché à côté de l'icône de corbeille.</td>
                  </tr>
                </tbody>
              </table>
              <p class="text-[10px] text-slate-500"><span class="font-semibold text-slate-700 dark:text-slate-350">Événement émis :</span> <code class="font-mono text-primary-600 dark:text-primary-400 font-semibold">@confirm-delete</code> déclenché après confirmation de la boîte de dialogue.</p>
            </div>

            <div class="border-t border-slate-100 dark:border-slate-800/60 pt-4">
              <h3 class="font-semibold text-slate-800 dark:text-slate-200 mb-2 font-mono text-xs">&lt;ButtonEdit&gt;</h3>
              <table class="w-full text-left border-collapse text-xs mb-2">
                <thead>
                  <tr class="border-b border-slate-200 dark:border-slate-700/80">
                    <th class="py-2 px-3 font-semibold text-slate-900 dark:text-white w-1/4">Propriété</th>
                    <th class="py-2 px-3 font-semibold text-slate-900 dark:text-white w-1/5">Type</th>
                    <th class="py-2 px-3 font-semibold text-slate-900 dark:text-white w-1/6">Défaut</th>
                    <th class="py-2 px-3 font-semibold text-slate-900 dark:text-white w-1/12">Requis</th>
                    <th class="py-2 px-3 font-semibold text-slate-900 dark:text-white">Description</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
                  <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/10">
                    <td class="py-2 px-3 font-mono text-primary-600 dark:text-primary-400 font-semibold">tooltip</td>
                    <td class="py-2 px-3 font-mono text-slate-655">String</td>
                    <td class="py-2 px-3 font-mono text-slate-500">—</td>
                    <td class="py-2 px-3 text-red-500 font-semibold">Oui</td>
                    <td class="py-2 px-3 text-slate-600 dark:text-slate-400">Texte de l'infobulle affiché au survol.</td>
                  </tr>
                </tbody>
              </table>
            </div>

            <div class="border-t border-slate-100 dark:border-slate-800/60 pt-4">
              <h3 class="font-semibold text-slate-800 dark:text-slate-200 mb-2 font-mono text-xs">&lt;ButtonSave&gt;</h3>
              <table class="w-full text-left border-collapse text-xs mb-2">
                <thead>
                  <tr class="border-b border-slate-200 dark:border-slate-700/80">
                    <th class="py-2 px-3 font-semibold text-slate-900 dark:text-white w-1/4">Propriété</th>
                    <th class="py-2 px-3 font-semibold text-slate-900 dark:text-white w-1/5">Type</th>
                    <th class="py-2 px-3 font-semibold text-slate-900 dark:text-white w-1/6">Défaut</th>
                    <th class="py-2 px-3 font-semibold text-slate-900 dark:text-white w-1/12">Requis</th>
                    <th class="py-2 px-3 font-semibold text-slate-900 dark:text-white">Description</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
                  <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/10">
                    <td class="py-2 px-3 font-mono text-primary-600 dark:text-primary-400 font-semibold">tooltip</td>
                    <td class="py-2 px-3 font-mono text-slate-655">String</td>
                    <td class="py-2 px-3 font-mono text-slate-500">—</td>
                    <td class="py-2 px-3 text-red-500 font-semibold">Oui</td>
                    <td class="py-2 px-3 text-slate-600 dark:text-slate-400">Texte de l'infobulle affiché au survol.</td>
                  </tr>
                </tbody>
              </table>
              <p class="text-[10px] text-slate-500"><span class="font-semibold text-slate-700 dark:text-slate-350">Événement émis :</span> <code class="font-mono text-primary-600 dark:text-primary-400 font-semibold">@confirm-save</code> déclenché après confirmation de la boîte de dialogue.</p>
            </div>

            <div class="border-t border-slate-100 dark:border-slate-800/60 pt-4">
              <h3 class="font-semibold text-slate-800 dark:text-slate-200 mb-2 font-mono text-xs">&lt;ButtonInfo&gt;</h3>
              <table class="w-full text-left border-collapse text-xs mb-2">
                <thead>
                  <tr class="border-b border-slate-200 dark:border-slate-700/80">
                    <th class="py-2 px-3 font-semibold text-slate-900 dark:text-white w-1/4">Propriété</th>
                    <th class="py-2 px-3 font-semibold text-slate-900 dark:text-white w-1/5">Type</th>
                    <th class="py-2 px-3 font-semibold text-slate-900 dark:text-white w-1/6">Défaut</th>
                    <th class="py-2 px-3 font-semibold text-slate-900 dark:text-white w-1/12">Requis</th>
                    <th class="py-2 px-3 font-semibold text-slate-900 dark:text-white">Description</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
                  <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/10">
                    <td class="py-2 px-3 font-mono text-primary-600 dark:text-primary-400 font-semibold">tooltip</td>
                    <td class="py-2 px-3 font-mono text-slate-655">String</td>
                    <td class="py-2 px-3 font-mono text-slate-500">—</td>
                    <td class="py-2 px-3 text-red-500 font-semibold">Oui</td>
                    <td class="py-2 px-3 text-slate-600 dark:text-slate-400">Texte de l'infobulle affiché au survol.</td>
                  </tr>
                  <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/10">
                    <td class="py-2 px-3 font-mono text-primary-600 dark:text-primary-400 font-semibold">icon</td>
                    <td class="py-2 px-3 font-mono text-slate-655">String</td>
                    <td class="py-2 px-3 font-mono text-slate-500">'pi pi-info'</td>
                    <td class="py-2 px-3 text-slate-550">Non</td>
                    <td class="py-2 px-3 text-slate-600 dark:text-slate-400">Icône PrimeIcons à afficher dans le bouton.</td>
                  </tr>
                </tbody>
              </table>
            </div>

            <div class="border-t border-slate-100 dark:border-slate-800/60 pt-4">
              <h3 class="font-semibold text-slate-800 dark:text-slate-200 mb-2 font-mono text-xs">&lt;ButtonDuplicate&gt;</h3>
              <table class="w-full text-left border-collapse text-xs mb-2">
                <thead>
                  <tr class="border-b border-slate-200 dark:border-slate-700/80">
                    <th class="py-2 px-3 font-semibold text-slate-900 dark:text-white w-1/4">Propriété</th>
                    <th class="py-2 px-3 font-semibold text-slate-900 dark:text-white w-1/5">Type</th>
                    <th class="py-2 px-3 font-semibold text-slate-900 dark:text-white w-1/6">Défaut</th>
                    <th class="py-2 px-3 font-semibold text-slate-900 dark:text-white w-1/12">Requis</th>
                    <th class="py-2 px-3 font-semibold text-slate-900 dark:text-white">Description</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
                  <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/10">
                    <td class="py-2 px-3 font-mono text-primary-600 dark:text-primary-400 font-semibold">tooltip</td>
                    <td class="py-2 px-3 font-mono text-slate-655">String</td>
                    <td class="py-2 px-3 font-mono text-slate-505">—</td>
                    <td class="py-2 px-3 text-red-500 font-semibold">Oui</td>
                    <td class="py-2 px-3 text-slate-600 dark:text-slate-400">Texte de l'infobulle affiché au survol.</td>
                  </tr>
                </tbody>
              </table>
              <p class="text-[10px] text-slate-500"><span class="font-semibold text-slate-700 dark:text-slate-350">Événement émis :</span> <code class="font-mono text-primary-600 dark:text-primary-400 font-semibold">@confirm-duplicate</code> déclenché après confirmation de la boîte de dialogue.</p>
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

        <!-- Properties Table ValidatedInput -->
        <Card title="Propriétés de ValidatedInput">
          <div class="overflow-x-auto max-h-[350px] overflow-y-auto">
            <table class="w-full text-left border-collapse text-xs">
              <thead class="sticky top-0 bg-white dark:bg-slate-800 z-10 shadow-[0_1px_0_0_rgba(0,0,0,0.1)] dark:shadow-[0_1px_0_0_rgba(255,255,255,0.05)]">
                <tr>
                  <th class="py-2 px-3 font-semibold text-slate-900 dark:text-white bg-slate-50 dark:bg-slate-900 w-1/4">Propriété</th>
                  <th class="py-2 px-3 font-semibold text-slate-900 dark:text-white bg-slate-50 dark:bg-slate-900 w-1/5">Type</th>
                  <th class="py-2 px-3 font-semibold text-slate-900 dark:text-white bg-slate-50 dark:bg-slate-900 w-1/6">Défaut</th>
                  <th class="py-2 px-3 font-semibold text-slate-900 dark:text-white bg-slate-50 dark:bg-slate-900 w-1/12">Requis</th>
                  <th class="py-2 px-3 font-semibold text-slate-900 dark:text-white bg-slate-50 dark:bg-slate-900">Description</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
                <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/10">
                  <td class="py-2 px-3 font-mono text-primary-600 dark:text-primary-400 font-semibold">modelValue / v-model</td>
                  <td class="py-2 px-3 font-mono text-slate-655">any</td>
                  <td class="py-2 px-3 font-mono text-slate-500">null</td>
                  <td class="py-2 px-3 text-slate-505">Non</td>
                  <td class="py-2 px-3 text-slate-600 dark:text-slate-400">Liaison bidirectionnelle pour la valeur du champ.</td>
                </tr>
                <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/10">
                  <td class="py-2 px-3 font-mono text-primary-600 dark:text-primary-400 font-semibold">type</td>
                  <td class="py-2 px-3 font-mono text-slate-655">String</td>
                  <td class="py-2 px-3 font-mono text-slate-505">'text'</td>
                  <td class="py-2 px-3 text-slate-505">Non</td>
                  <td class="py-2 px-3 text-slate-600 dark:text-slate-400">Type de saisie. Valeurs gérées : <code class="font-mono text-[10px]">'text', 'number', 'password', 'select', 'multiselect', 'date', 'textarea', 'radio', 'address', 'file'</code>.</td>
                </tr>
                <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/10">
                  <td class="py-2 px-3 font-mono text-primary-600 dark:text-primary-400 font-semibold">rules</td>
                  <td class="py-2 px-3 font-mono text-slate-655">Array | Object | String</td>
                  <td class="py-2 px-3 font-mono text-slate-500">null</td>
                  <td class="py-2 px-3 text-slate-505">Non</td>
                  <td class="py-2 px-3 text-slate-600 dark:text-slate-400">Règles de validation (ex: <code class="font-mono text-[10px]">'required'</code>, <code class="font-mono text-[10px]">'required|email'</code>, etc.).</td>
                </tr>
                <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/10">
                  <td class="py-2 px-3 font-mono text-primary-600 dark:text-primary-400 font-semibold">name</td>
                  <td class="py-2 px-3 font-mono text-slate-655">String</td>
                  <td class="py-2 px-3 font-mono text-slate-505">''</td>
                  <td class="py-2 px-3 text-slate-505">Non</td>
                  <td class="py-2 px-3 text-slate-600 dark:text-slate-400">Identifiant et attribut <code class="font-mono text-xs">name</code> du champ.</td>
                </tr>
                <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/10">
                  <td class="py-2 px-3 font-mono text-primary-600 dark:text-primary-400 font-semibold">label</td>
                  <td class="py-2 px-3 font-mono text-slate-655">String</td>
                  <td class="py-2 px-3 font-mono text-slate-505">''</td>
                  <td class="py-2 px-3 text-slate-505">Non</td>
                  <td class="py-2 px-3 text-slate-600 dark:text-slate-400">Libellé textuel. Affiche une astérisque rouge si la règle <code class="font-mono text-[10px]">required</code> est présente.</td>
                </tr>
                <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/10">
                  <td class="py-2 px-3 font-mono text-primary-600 dark:text-primary-400 font-semibold">placeholder</td>
                  <td class="py-2 px-3 font-mono text-slate-655">String</td>
                  <td class="py-2 px-3 font-mono text-slate-505">''</td>
                  <td class="py-2 px-3 text-slate-505">Non</td>
                  <td class="py-2 px-3 text-slate-600 dark:text-slate-400">Texte d'aide interne.</td>
                </tr>
                <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/10">
                  <td class="py-2 px-3 font-mono text-primary-600 dark:text-primary-400 font-semibold">helpText</td>
                  <td class="py-2 px-3 font-mono text-slate-655">String</td>
                  <td class="py-2 px-3 font-mono text-slate-505">''</td>
                  <td class="py-2 px-3 text-slate-505">Non</td>
                  <td class="py-2 px-3 text-slate-600 dark:text-slate-400">Texte informatif sous le champ (masqué si erreur).</td>
                </tr>
                <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/10">
                  <td class="py-2 px-3 font-mono text-primary-600 dark:text-primary-400 font-semibold">disabled</td>
                  <td class="py-2 px-3 font-mono text-slate-655">Boolean</td>
                  <td class="py-2 px-3 font-mono text-slate-505">false</td>
                  <td class="py-2 px-3 text-slate-505">Non</td>
                  <td class="py-2 px-3 text-slate-600 dark:text-slate-400">Désactive le composant de saisie.</td>
                </tr>
                <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/10">
                  <td class="py-2 px-3 font-mono text-primary-600 dark:text-primary-400 font-semibold">options</td>
                  <td class="py-2 px-3 font-mono text-slate-655">Array</td>
                  <td class="py-2 px-3 font-mono text-slate-500">() => []</td>
                  <td class="py-2 px-3 text-slate-505">Non</td>
                  <td class="py-2 px-3 text-slate-600 dark:text-slate-400">Tableau d'options pour <code class="font-mono text-xs">select</code> / <code class="font-mono text-xs">multiselect</code> sous le format <code class="font-mono text-[10px]">{ label: string, value: any }</code>.</td>
                </tr>
                <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/10">
                  <td class="py-2 px-3 font-mono text-primary-600 dark:text-primary-400 font-semibold">filter / showClear</td>
                  <td class="py-2 px-3 font-mono text-slate-655">Boolean</td>
                  <td class="py-2 px-3 font-mono text-slate-505">false</td>
                  <td class="py-2 px-3 text-slate-505">Non</td>
                  <td class="py-2 px-3 text-slate-600 dark:text-slate-400">Recherche active / croix de vidage sur le select/multiselect.</td>
                </tr>
                <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/10">
                  <td class="py-2 px-3 font-mono text-primary-600 dark:text-primary-400 font-semibold">min / max</td>
                  <td class="py-2 px-3 font-mono text-slate-655">Number | String</td>
                  <td class="py-2 px-3 font-mono text-slate-500">null</td>
                  <td class="py-2 px-3 text-slate-505">Non</td>
                  <td class="py-2 px-3 text-slate-600 dark:text-slate-400">Bornes minimales/maximales pour le type <code class="font-mono text-xs">number</code>.</td>
                </tr>
                <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/10">
                  <td class="py-2 px-3 font-mono text-primary-600 dark:text-primary-400 font-semibold">minfractiondigits / maxfractiondigits</td>
                  <td class="py-2 px-3 font-mono text-slate-655">Number</td>
                  <td class="py-2 px-3 font-mono text-slate-500">null</td>
                  <td class="py-2 px-3 text-slate-505">Non</td>
                  <td class="py-2 px-3 text-slate-600 dark:text-slate-400">Chiffres après la virgule pour le type <code class="font-mono text-xs">number</code>.</td>
                </tr>
                <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/10">
                  <td class="py-2 px-3 font-mono text-primary-600 dark:text-primary-400 font-semibold">feedback / toggleMask</td>
                  <td class="py-2 px-3 font-mono text-slate-655">Boolean</td>
                  <td class="py-2 px-3 font-mono text-slate-500">false / true</td>
                  <td class="py-2 px-3 text-slate-505">Non</td>
                  <td class="py-2 px-3 text-slate-600 dark:text-slate-400">Options pour le type <code class="font-mono text-xs">password</code> (force du MDP / œil de masquage).</td>
                </tr>
                <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/10">
                  <td class="py-2 px-3 font-mono text-primary-600 dark:text-primary-400 font-semibold">selectionMode</td>
                  <td class="py-2 px-3 font-mono text-slate-655">String</td>
                  <td class="py-2 px-3 font-mono text-slate-505">'single'</td>
                  <td class="py-2 px-3 text-slate-505">Non</td>
                  <td class="py-2 px-3 text-slate-600 dark:text-slate-400">Mode de sélection de date (<code class="font-mono text-xs">'single' | 'range' | 'multiple'</code>).</td>
                </tr>
                <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/10">
                  <td class="py-2 px-3 font-mono text-primary-600 dark:text-primary-400 font-semibold">minDate / maxDate</td>
                  <td class="py-2 px-3 font-mono text-slate-655">Date</td>
                  <td class="py-2 px-3 font-mono text-slate-500">null</td>
                  <td class="py-2 px-3 text-slate-505">Non</td>
                  <td class="py-2 px-3 text-slate-600 dark:text-slate-400">Bornes de dates sélectionnables pour le type <code class="font-mono text-xs">date</code>.</td>
                </tr>
                <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/10">
                  <td class="py-2 px-3 font-mono text-primary-600 dark:text-primary-400 font-semibold">multiple / accept / maxFileSize</td>
                  <td class="py-2 px-3 font-mono text-slate-655">Boolean / String / Number</td>
                  <td class="py-2 px-3 font-mono text-slate-500">false / '*' / null</td>
                  <td class="py-2 px-3 text-slate-505">Non</td>
                  <td class="py-2 px-3 text-slate-600 dark:text-slate-400">Options de chargement de fichiers pour le type <code class="font-mono text-xs">file</code>.</td>
                </tr>
                <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/10">
                  <td class="py-2 px-3 font-mono text-primary-600 dark:text-primary-400 font-semibold">country</td>
                  <td class="py-2 px-3 font-mono text-slate-655">String</td>
                  <td class="py-2 px-3 font-mono text-slate-505">'fr'</td>
                  <td class="py-2 px-3 text-slate-505">Non</td>
                  <td class="py-2 px-3 text-slate-600 dark:text-slate-400">Code pays pour l'autocomplétion géographique (type <code class="font-mono text-xs">address</code>).</td>
                </tr>
              </tbody>
            </table>
          </div>
          <div class="mt-4 pt-4 border-t border-slate-100 dark:border-slate-800/60 text-xs">
            <span class="font-semibold text-slate-800 dark:text-slate-200">Événements émis :</span>
            <ul class="list-disc list-inside mt-2 space-y-1 text-slate-600 dark:text-slate-400">
              <li><code class="font-mono text-primary-600 dark:text-primary-400 font-semibold">@update:modelValue</code> : Déclenché lors de la modification de la valeur du champ.</li>
              <li><code class="font-mono text-primary-600 dark:text-primary-400 font-semibold">@blur</code> : Déclenché à la perte de focus.</li>
              <li><code class="font-mono text-primary-600 dark:text-primary-400 font-semibold">@validation</code> : Déclenché après chaque validation avec le statut <code class="font-mono text-xs">{ isValid: boolean, errorMessage: string }</code>.</li>
            </ul>
          </div>
        </Card>

        <!-- Properties Table FormValidator & AddressAutocomplete -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <Card title="Propriétés de AddressAutocomplete">
            <div class="overflow-x-auto">
              <table class="w-full text-left border-collapse text-xs">
                <thead>
                  <tr class="border-b border-slate-200 dark:border-slate-700/80">
                    <th class="py-2 px-3 font-semibold text-slate-900 dark:text-white">Propriété</th>
                    <th class="py-2 px-3 font-semibold text-slate-900 dark:text-white">Type</th>
                    <th class="py-2 px-3 font-semibold text-slate-900 dark:text-white">Défaut</th>
                    <th class="py-2 px-3 text-slate-900 dark:text-white">Description</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
                  <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/10">
                    <td class="py-2 px-3 font-mono text-primary-600 dark:text-primary-400 font-semibold">modelValue</td>
                    <td class="py-2 px-3 font-mono text-slate-655">Object</td>
                    <td class="py-2 px-3 font-mono text-slate-500">Structuré*</td>
                    <td class="py-2 px-3 text-slate-600 dark:text-slate-400">Objet : `{ adresse, complement1, complement2, ville, codePostal, pays: 'France' }`.</td>
                  </tr>
                  <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/10">
                    <td class="py-2 px-3 font-mono text-primary-600 dark:text-primary-400 font-semibold">placeholder</td>
                    <td class="py-2 px-3 font-mono text-slate-655">String</td>
                    <td class="py-2 px-3 font-mono text-slate-505">'Entrez une adresse...'</td>
                    <td class="py-2 px-3 text-slate-600 dark:text-slate-400">Texte d'aide interne.</td>
                  </tr>
                  <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/10">
                    <td class="py-2 px-3 font-mono text-primary-600 dark:text-primary-400 font-semibold">label</td>
                    <td class="py-2 px-3 font-mono text-slate-655">String</td>
                    <td class="py-2 px-3 font-mono text-slate-505">'Adresse'</td>
                    <td class="py-2 px-3 text-slate-600 dark:text-slate-400">Texte du libellé au-dessus.</td>
                  </tr>
                  <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/10">
                    <td class="py-2 px-3 font-mono text-primary-600 dark:text-primary-400 font-semibold">required / disabled</td>
                    <td class="py-2 px-3 font-mono text-slate-655">Boolean</td>
                    <td class="py-2 px-3 font-mono text-slate-500">false</td>
                    <td class="py-2 px-3 text-slate-600 dark:text-slate-400">Champs obligatoires ou désactivés.</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </Card>

          <Card title="Propriétés de FormValidator">
            <div class="overflow-x-auto">
              <table class="w-full text-left border-collapse text-xs">
                <thead>
                  <tr class="border-b border-slate-200 dark:border-slate-700/80">
                    <th class="py-2 px-3 font-semibold text-slate-900 dark:text-white">Propriété</th>
                    <th class="py-2 px-3 font-semibold text-slate-900 dark:text-white">Type</th>
                    <th class="py-2 px-3 font-semibold text-slate-900 dark:text-white">Défaut</th>
                    <th class="py-2 px-3 text-slate-900 dark:text-white">Description</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
                  <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/10">
                    <td class="py-2 px-3 font-mono text-primary-600 dark:text-primary-400 font-semibold">modelValue</td>
                    <td class="py-2 px-3 font-mono text-slate-655">any</td>
                    <td class="py-2 px-3 font-mono text-slate-500">null</td>
                    <td class="py-2 px-3 text-slate-600 dark:text-slate-400">Valeur à observer et valider.</td>
                  </tr>
                  <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/10">
                    <td class="py-2 px-3 font-mono text-primary-600 dark:text-primary-400 font-semibold">rules</td>
                    <td class="py-2 px-3 font-mono text-slate-655">Array | Object | String</td>
                    <td class="py-2 px-3 font-mono text-slate-505">null</td>
                    <td class="py-2 px-3 text-slate-600 dark:text-slate-400">Liste ou objet de règles à appliquer.</td>
                  </tr>
                  <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/10">
                    <td class="py-2 px-3 font-mono text-primary-600 dark:text-primary-400 font-semibold">validateOnInput</td>
                    <td class="py-2 px-3 font-mono text-slate-655">Boolean</td>
                    <td class="py-2 px-3 font-mono text-slate-505">false</td>
                    <td class="py-2 px-3 text-slate-600 dark:text-slate-400">Valide en temps réel pendant la saisie.</td>
                  </tr>
                  <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/10">
                    <td class="py-2 px-3 font-mono text-primary-600 dark:text-primary-400 font-semibold">validateOnBlur</td>
                    <td class="py-2 px-3 font-mono text-slate-655">Boolean</td>
                    <td class="py-2 px-3 font-mono text-slate-505">true</td>
                    <td class="py-2 px-3 text-slate-600 dark:text-slate-400">Valide lors de la perte de focus.</td>
                  </tr>
                </tbody>
              </table>
            </div>
            <div class="mt-3 pt-3 border-t border-slate-100 dark:border-slate-800/60 text-xs">
              <span class="font-semibold text-slate-800 dark:text-slate-200">Slot Props exposés :</span>
              <div class="font-mono text-[10px] text-slate-600 mt-1 space-y-0.5">
                <div>- <code class="text-primary-600 font-semibold">validate()</code> : Fonction de validation manuelle.</div>
                <div>- <code class="text-primary-600 font-semibold">isValid</code> : Boolean de validité.</div>
                <div>- <code class="text-primary-600 font-semibold">errorMessage</code> : Message d'erreur calculé.</div>
                <div>- <code class="text-primary-600 font-semibold">handleBlur</code> : Callback blur interne.</div>
                <div>- <code class="text-primary-600 font-semibold">showError</code> : Affichage de l'erreur.</div>
              </div>
            </div>
          </Card>
        </div>

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
