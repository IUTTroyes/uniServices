<script setup>
import { ref, computed, watch } from 'vue';
import { tools } from '@config/uniServices.js';
import { bulkUpdateEtudiantApplicationsService } from '@requests';
import { useToast } from 'primevue/usetoast';
import { ApplicationBadge } from '@components';
import { getApplicationMeta, normalizeAppSlug } from '@utils';

const props = defineProps({
  isVisible: {
    type: Boolean,
    default: false,
  },
  etudiants: {
    type: Array,
    default: () => [],
  },
  annee: {
    type: Object,
    default: null,
  },
  anneesList: {
    type: Array,
    default: () => [],
  },
  departementId: {
    type: Number,
    default: null,
  },
  anneeUniversitaireId: {
    type: Number,
    default: null,
  },
  initialMode: {
    type: String,
    default: 'selection', // 'selection' | 'promo'
  },
});

const emit = defineEmits(['update:isVisible', 'saved']);

const toast = useToast();

const mode = ref('selection'); // 'selection' | 'promo'
const bulkAction = ref('set'); // 'set' | 'add' | 'remove'
const selectedApps = ref([]);
const targetAnneeId = ref(null);
const isSaving = ref(false);

const actionOptions = [
  { label: 'Remplacer toutes les applications', value: 'set', icon: 'pi pi-sync', description: 'Définit exactement la liste ci-dessous pour les étudiants ciblés.' },
  { label: 'Ajouter aux applications existantes', value: 'add', icon: 'pi pi-plus', description: 'Conserve les accès actuels des étudiants et y ajoute les applications cochées.' },
  { label: 'Retirer des applications existantes', value: 'remove', icon: 'pi pi-minus', description: 'Supprime l\'accès aux applications cochées sans modifier les autres.' },
];

const availableTools = computed(() => {
  return (tools || []).map((tool) => {
    const slug = normalizeAppSlug(tool.urlSlug || tool.name);
    return {
      ...tool,
      slug,
      meta: getApplicationMeta(slug),
    };
  });
});

const isSingleStudent = computed(() => props.etudiants.length === 1 && mode.value === 'selection');
const singleStudent = computed(() => isSingleStudent.value ? props.etudiants[0]?.etudiant || props.etudiants[0] : null);

// Sync internal state when dialog opens
watch(
  () => props.isVisible,
  (open) => {
    if (!open) return;

    mode.value = props.initialMode || (props.etudiants.length > 0 ? 'selection' : 'promo');
    bulkAction.value = 'set';

    if (props.annee?.id) {
      targetAnneeId.value = props.annee.id;
    } else if (props.anneesList?.length) {
      targetAnneeId.value = props.anneesList[0].id;
    } else {
      targetAnneeId.value = null;
    }

    if (isSingleStudent.value && singleStudent.value) {
      const studentApps = singleStudent.value.applications || ['intranet'];
      selectedApps.value = studentApps.map(normalizeAppSlug);
    } else {
      // Default selection: intranet by default
      selectedApps.value = ['intranet'];
    }
  },
  { immediate: true }
);

const isAppSelected = (slug) => selectedApps.value.includes(normalizeAppSlug(slug));

const toggleApp = (slug) => {
  const norm = normalizeAppSlug(slug);
  const current = new Set(selectedApps.value);
  if (current.has(norm)) {
    current.delete(norm);
  } else {
    current.add(norm);
  }
  selectedApps.value = Array.from(current);
};

const selectAll = () => {
  selectedApps.value = availableTools.value.map((t) => t.slug);
};

const deselectAll = () => {
  selectedApps.value = ['intranet'];
};

const closeDialog = () => {
  emit('update:isVisible', false);
};

const targetAnneeObj = computed(() => {
  return (props.anneesList || []).find((a) => a.id === targetAnneeId.value) || null;
});

const save = async () => {
  if (selectedApps.value.length === 0 && bulkAction.value !== 'remove') {
    toast.add({
      severity: 'warn',
      summary: 'Attention',
      detail: 'Veuillez sélectionner au moins une application (ex: UniTranet).',
      life: 4000,
    });
    return;
  }

  isSaving.value = true;
  try {
    let payload = {
      action: isSingleStudent.value ? 'set' : bulkAction.value,
      applications: selectedApps.value,
    };

    if (mode.value === 'promo') {
      if (!targetAnneeId.value) {
        toast.add({
          severity: 'warn',
          summary: 'Attention',
          detail: 'Veuillez choisir une année / promotion cible.',
          life: 4000,
        });
        isSaving.value = false;
        return;
      }
      payload.anneeId = targetAnneeId.value;
      if (props.departementId) payload.departementId = props.departementId;
      if (props.anneeUniversitaireId) payload.anneeUniversitaireId = props.anneeUniversitaireId;
    } else {
      const ids = props.etudiants
        .map((item) => (item?.etudiant?.id ? item.etudiant.id : item.id))
        .filter(Boolean);

      if (ids.length === 0) {
        toast.add({
          severity: 'warn',
          summary: 'Attention',
          detail: 'Aucun étudiant sélectionné.',
          life: 4000,
        });
        isSaving.value = false;
        return;
      }
      payload.etudiantIds = ids;
    }

    const response = await bulkUpdateEtudiantApplicationsService(payload, true);
    emit('saved', response);
    closeDialog();
  } catch (error) {
    console.error('Erreur lors de la mise à jour des applications :', error);
  } finally {
    isSaving.value = false;
  }
};
</script>

<template>
  <Dialog
    :visible="props.isVisible"
    :modal="true"
    :closable="true"
    :draggable="false"
    :style="{ width: '850px', maxWidth: '96vw' }"
    :breakpoints="{ '1199px': '90vw', '575px': '98vw' }"
    @update:visible="closeDialog"
  >
    <template #header>
      <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-primary-100 dark:bg-primary-950/60 border border-primary-200 dark:border-primary-800 flex items-center justify-center text-primary-600 dark:text-primary-400">
          <i class="pi pi-th-large text-xl" />
        </div>
        <div>
          <h3 class="m-0 text-lg font-bold text-surface-900 dark:text-surface-0">
            <template v-if="isSingleStudent && singleStudent">
              Accès aux applications — {{ singleStudent.prenom }} {{ singleStudent.nom }}
            </template>
            <template v-else-if="mode === 'promo'">
              Affectation globale par promotion
            </template>
            <template v-else>
              Gestion des applications ({{ props.etudiants.length }} étudiant(s) sélectionné(s))
            </template>
          </h3>
          <p class="m-0 text-xs text-muted-color">
            <template v-if="isSingleStudent && singleStudent">
              <span>{{ singleStudent.mailUniv || singleStudent.email || 'Étudiant' }}</span>
              <div v-if="singleStudent.applications?.length" class="flex flex-wrap items-center gap-1.5 mt-1.5">
                <span class="text-[11px] text-muted-color">Accès actuels :</span>
                <ApplicationBadge
                  v-for="app in singleStudent.applications"
                  :key="app"
                  :app="app"
                  size="xs"
                />
              </div>
            </template>
            <template v-else-if="mode === 'promo'">
              Affectez ou révoquez rapidement les applications pour l'ensemble d'une cohorte
            </template>
            <template v-else>
              Modifiez rapidement les droits d'accès aux applications pour la sélection
            </template>
          </p>
        </div>
      </div>
    </template>

    <div class="flex flex-col gap-5 py-2">
      <!-- Mode switcher if we have multiple students or list of annees -->
      <div v-if="!isSingleStudent && props.anneesList.length > 0" class="flex items-center justify-between p-3 rounded-xl bg-surface-50 dark:bg-surface-900 border border-surface-200 dark:border-surface-800">
        <div class="flex items-center gap-2">
          <span class="text-xs font-bold uppercase tracking-wider text-muted-color">Cible :</span>
        </div>
        <div class="flex gap-2">
          <Button
            size="small"
            :severity="mode === 'selection' ? 'primary' : 'secondary'"
            :outlined="mode !== 'selection'"
            icon="pi pi-users"
            :label="`Sélection en cours (${props.etudiants.length})`"
            @click="mode = 'selection'"
          />
          <Button
            size="small"
            :severity="mode === 'promo' ? 'primary' : 'secondary'"
            :outlined="mode !== 'promo'"
            icon="pi pi-graduation-cap"
            label="Toute une promotion"
            @click="mode = 'promo'"
          />
        </div>
      </div>

      <!-- Promotion target picker if mode === 'promo' -->
      <div v-if="mode === 'promo'" class="flex flex-col gap-2 p-4 rounded-xl border border-primary-200 dark:border-primary-900/40 bg-primary-50/30 dark:bg-primary-950/20">
        <label class="text-sm font-bold text-surface-800 dark:text-surface-100 flex items-center gap-2">
          <i class="pi pi-graduation-cap text-primary" />
          Sélectionner la promotion / année concernée :
        </label>
        <Select
          v-model="targetAnneeId"
          :options="props.anneesList"
          optionLabel="libelle"
          optionValue="id"
          placeholder="Sélectionnez une année (ex: BUT 2)"
          class="w-full"
        >
          <template #option="{ option }">
            <div class="flex items-center justify-between w-full">
              <span class="font-semibold">{{ option.libelle }}</span>
              <Tag
                v-if="option.etudiantsCount !== undefined"
                severity="info"
                :value="`${option.etudiantsCount} étudiant(s)`"
              />
            </div>
          </template>
        </Select>
        <p v-if="targetAnneeObj" class="m-0 text-xs text-muted-color">
          Cette action s'appliquera à <strong>{{ targetAnneeObj.etudiantsCount ?? 'tous les' }}</strong> étudiants inscrits dans <strong>{{ targetAnneeObj.libelle }}</strong>.
        </p>
      </div>

      <!-- Action mode choice for bulk (Ajouter / Retirer / Remplacer) -->
      <div v-if="!isSingleStudent" class="flex flex-col gap-2">
        <span class="text-xs font-bold uppercase tracking-wider text-muted-color">Mode d'application</span>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
          <div
            v-for="opt in actionOptions"
            :key="opt.value"
            class="flex flex-col gap-1.5 p-3 rounded-xl border cursor-pointer transition-all duration-150"
            :class="[
              bulkAction === opt.value
                ? 'border-primary-500 bg-primary-500/10 shadow-xs'
                : 'border-surface-200 dark:border-surface-800 hover:border-surface-300 dark:hover:border-surface-700 bg-surface-0 dark:bg-surface-900'
            ]"
            @click="bulkAction = opt.value"
          >
            <div class="flex items-center justify-between">
              <span class="text-xs font-bold flex items-center gap-1.5 text-surface-900 dark:text-surface-100">
                <i :class="[opt.icon, bulkAction === opt.value ? 'text-primary' : 'text-muted-color']" />
                {{ opt.label }}
              </span>
              <i
                v-if="bulkAction === opt.value"
                class="pi pi-check-circle text-primary font-bold"
              />
            </div>
            <p class="m-0 text-xs text-muted-color leading-relaxed">
              {{ opt.description }}
            </p>
          </div>
        </div>
      </div>

      <!-- Quick actions bar -->
      <div class="flex items-center justify-between pt-1">
        <span class="text-xs font-bold uppercase tracking-wider text-muted-color">
          Applications du catalogue ({{ selectedApps.length }} sélectionnée(s))
        </span>
        <div class="flex items-center gap-2">
          <Button
            label="Tout cocher"
            icon="pi pi-check"
            text
            size="small"
            severity="secondary"
            @click="selectAll"
          />
          <Button
            label="UniTranet seul"
            icon="pi pi-refresh"
            text
            size="small"
            severity="secondary"
            @click="deselectAll"
          />
        </div>
      </div>

      <!-- Tools Grid -->
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 max-h-[420px] overflow-y-auto pr-1">
        <div
          v-for="tool in availableTools"
          :key="tool.slug"
          class="flex items-start gap-3 p-3.5 rounded-xl border transition-all duration-150 cursor-pointer"
          :class="[
            isAppSelected(tool.slug)
              ? 'border-primary-500 bg-primary-50/40 dark:bg-primary-950/20 shadow-xs'
              : 'border-surface-200 dark:border-surface-800 hover:border-surface-300 dark:hover:border-surface-700 bg-surface-0 dark:bg-surface-900'
          ]"
          @click="toggleApp(tool.slug)"
        >
          <!-- Checkbox -->
          <div class="pt-0.5" @click.stop>
            <Checkbox
              :modelValue="isAppSelected(tool.slug)"
              :binary="true"
              @update:modelValue="toggleApp(tool.slug)"
            />
          </div>

          <!-- Icon badge -->
          <div
            class="w-10 h-10 rounded-xl border flex items-center justify-center shrink-0 transition-colors"
            :class="tool.meta.iconBgClasses"
          >
            <i :class="[tool.meta.icon, 'text-lg']" />
          </div>

          <!-- Info -->
          <div class="flex flex-col flex-1 min-w-0">
            <div class="flex items-center justify-between gap-2">
              <div class="flex items-center gap-2 min-w-0">
                <span class="font-bold text-sm text-surface-900 dark:text-surface-100 truncate">
                  {{ tool.name }}
                </span>
                <ApplicationBadge :app="tool.slug" size="xs" />
              </div>
              <Tag
                v-if="tool.meta.isDefault"
                value="Défaut"
                severity="secondary"
                class="text-[10px] py-0 px-1.5 shrink-0"
              />
            </div>
            <p class="m-0 text-xs text-muted-color line-clamp-2 leading-relaxed">
              {{ tool.description || 'Application universitaire' }}
            </p>
          </div>
        </div>
      </div>

      <!-- Information notice -->
      <Message severity="secondary" icon="pi pi-info-circle" class="text-xs">
        <template v-if="bulkAction === 'add'">
          Les applications cochées seront <strong>ajoutées</strong> aux accès déjà attribués à chacun des étudiants ciblés.
        </template>
        <template v-else-if="bulkAction === 'remove'">
          Les applications cochées seront <strong>retirées</strong> des accès de chacun des étudiants ciblés. L'accès de base (UniTranet) reste toujours conservé.
        </template>
        <template v-else>
          La liste exacte d'applications sélectionnée ci-dessus deviendra le nouvel ensemble d'applications actives pour ces étudiants.
        </template>
      </Message>
    </div>

    <template #footer>
      <div class="flex items-center justify-between w-full pt-2">
        <span class="text-xs text-muted-color">
          <template v-if="mode === 'promo' && targetAnneeObj">
            Cible : {{ targetAnneeObj.libelle }}
          </template>
          <template v-else>
            Cible : {{ props.etudiants.length }} étudiant(s)
          </template>
        </span>
        <div class="flex items-center gap-2">
          <Button
            label="Annuler"
            severity="secondary"
            text
            size="small"
            :disabled="isSaving"
            @click="closeDialog"
          />
          <Button
            :label="isSaving ? 'Enregistrement...' : 'Appliquer les modifications'"
            icon="pi pi-check"
            severity="primary"
            size="small"
            :loading="isSaving"
            @click="save"
          />
        </div>
      </div>
    </template>
  </Dialog>
</template>

<style scoped>
</style>
