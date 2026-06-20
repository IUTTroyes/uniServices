<script setup>
import { ref, watch, computed, onMounted } from 'vue';
import { searchAddresses } from '@requests';

const props = defineProps({
  modelValue: {
    type: Object,
    default: () => ({
      adresse: '',
      complement1: '',
      complement2: '',
      ville: '',
      codePostal: '',
      pays: 'France'
    })
  },
  placeholder: {
    type: String,
    default: 'Entrez une adresse...'
  },
  label: {
    type: String,
    default: 'Adresse'
  },
  required: {
    type: Boolean,
    default: false
  },
  disabled: {
    type: Boolean,
    default: false
  }
});

const emit = defineEmits(['update:modelValue']);

// ─── État interne ───────────────────────────────────────────────────────────
const searchQuery = ref('');
const suggestions = ref([]);
const isSearching = ref(false);
const isManualMode = ref(false);
const addressSelected = ref(false);
let debounceTimer = null;

// Valeur interne (copie mutable du modelValue)
const internalAddress = ref({ ...props.modelValue });

// ─── Computed ────────────────────────────────────────────────────────────────
const displaySearch = computed(() => {
  if (addressSelected.value && internalAddress.value.adresse) {
    const parts = [internalAddress.value.adresse, internalAddress.value.codePostal, internalAddress.value.ville].filter(Boolean);
    return parts.join(', ');
  }
  return searchQuery.value;
});

// ─── Watchers ────────────────────────────────────────────────────────────────
watch(() => props.modelValue, (val) => {
  if (val) {
    internalAddress.value = { ...val };
    if (val.adresse || val.ville) {
      const parts = [val.adresse, val.codePostal, val.ville].filter(Boolean);
      searchQuery.value = parts.join(', ');
      addressSelected.value = true;
    }
  }
}, { deep: true, immediate: true });

// ─── Méthodes ────────────────────────────────────────────────────────────────
const onInput = (e) => {
  searchQuery.value = e.target.value;
  addressSelected.value = false;
  suggestions.value = [];

  clearTimeout(debounceTimer);
  if (searchQuery.value.length >= 3) {
    debounceTimer = setTimeout(async () => {
      isSearching.value = true;
      try {
        const results = await searchAddresses(searchQuery.value);
        suggestions.value = results.map(item => ({
          ...item,
          displayLabel: [item.address, item.postalCode, item.city].filter(Boolean).join(', ')
        }));
      } catch {
        suggestions.value = [];
      } finally {
        isSearching.value = false;
      }
    }, 300);
  }
};

const selectSuggestion = (item) => {
  internalAddress.value = {
    adresse: item.address || '',
    complement1: '',
    complement2: '',
    ville: item.city || '',
    codePostal: item.postalCode || '',
    pays: 'France'
  };
  addressSelected.value = true;
  suggestions.value = [];
  emit('update:modelValue', { ...internalAddress.value });
};

const switchToManual = () => {
  isManualMode.value = true;
  suggestions.value = [];
};

const clearAddress = () => {
  searchQuery.value = '';
  addressSelected.value = false;
  isManualMode.value = false;
  internalAddress.value = { adresse: '', complement1: '', complement2: '', ville: '', codePostal: '', pays: 'France' };
  emit('update:modelValue', { ...internalAddress.value });
};

const updateManualField = (field, value) => {
  internalAddress.value[field] = value;
  emit('update:modelValue', { ...internalAddress.value });
};

const confirmManual = () => {
  addressSelected.value = true;
  isManualMode.value = false;
  const parts = [internalAddress.value.adresse, internalAddress.value.codePostal, internalAddress.value.ville].filter(Boolean);
  searchQuery.value = parts.join(', ');
  emit('update:modelValue', { ...internalAddress.value });
};

const editAddress = () => {
  isManualMode.value = true;
  addressSelected.value = false;
};
</script>

<template>
  <div class="space-y-3">

    <!-- ── Recherche principale ─────────────────────────────────────────── -->
    <div v-if="!isManualMode" class="relative">
      <!-- Input de recherche ou affichage de l'adresse sélectionnée -->
      <div v-if="addressSelected" class="flex items-center gap-2">
        <div class="flex-1 flex items-center gap-2 p-3 border border-emerald-300 dark:border-emerald-700 rounded-xl bg-emerald-50 dark:bg-emerald-950/20">
          <i class="pi pi-map-marker text-emerald-600 text-sm shrink-0"></i>
          <span class="text-xs font-semibold text-slate-800 dark:text-slate-200 flex-1 truncate">
            {{ internalAddress.adresse }}
            <span class="font-normal text-slate-500 dark:text-slate-400"> — {{ internalAddress.codePostal }} {{ internalAddress.ville }}</span>
          </span>
        </div>
        <button
          type="button"
          @click="editAddress"
          class="p-2 text-slate-500 hover:text-violet-600 hover:bg-violet-50 dark:hover:bg-violet-950/20 rounded-lg transition-all"
          title="Modifier l'adresse"
        >
          <i class="pi pi-pencil text-xs"></i>
        </button>
        <button
          type="button"
          @click="clearAddress"
          class="p-2 text-slate-500 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/20 rounded-lg transition-all"
          title="Effacer l'adresse"
        >
          <i class="pi pi-times text-xs"></i>
        </button>
      </div>

      <!-- Champ de recherche -->
      <div v-else class="relative">
        <div class="relative">
          <i class="pi pi-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs pointer-events-none"></i>
          <input
            type="text"
            :value="searchQuery"
            @input="onInput"
            :placeholder="placeholder"
            :disabled="disabled"
            class="w-full pl-9 pr-10 p-3 border border-slate-200 dark:border-slate-700 rounded-xl bg-slate-50 dark:bg-slate-900 text-xs text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-violet-500 transition-all"
          />
          <i v-if="isSearching" class="pi pi-spin pi-spinner absolute right-3 top-1/2 -translate-y-1/2 text-violet-500 text-xs"></i>
        </div>

        <!-- Dropdown de suggestions -->
        <div
          v-if="suggestions.length > 0"
          class="absolute z-50 w-full mt-1 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl shadow-xl overflow-hidden"
        >
          <ul class="max-h-52 overflow-y-auto divide-y divide-slate-100 dark:divide-slate-700/60">
            <li
              v-for="(item, idx) in suggestions"
              :key="idx"
              @click="selectSuggestion(item)"
              class="flex items-start gap-2 px-4 py-3 cursor-pointer hover:bg-violet-50 dark:hover:bg-violet-950/20 transition-colors"
            >
              <i class="pi pi-map-marker text-violet-500 text-xs mt-0.5 shrink-0"></i>
              <div>
                <p class="text-xs font-semibold text-slate-800 dark:text-slate-200">{{ item.address }}</p>
                <p class="text-[10px] text-slate-500 dark:text-slate-400">{{ item.postalCode }} {{ item.city }}</p>
              </div>
            </li>
          </ul>

          <!-- Option saisie manuelle au bas de la liste -->
          <div
            @click="switchToManual"
            class="flex items-center gap-2 px-4 py-3 cursor-pointer bg-slate-50 dark:bg-slate-700/30 hover:bg-slate-100 dark:hover:bg-slate-700/50 transition-colors border-t border-slate-200 dark:border-slate-700"
          >
            <i class="pi pi-pencil text-amber-500 text-xs"></i>
            <span class="text-xs text-amber-700 dark:text-amber-400 font-semibold">Saisir l'adresse manuellement</span>
          </div>
        </div>

        <!-- Message si aucun résultat et requête assez longue -->
        <div
          v-else-if="searchQuery.length >= 3 && !isSearching"
          class="absolute z-50 w-full mt-1 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl shadow-xl overflow-hidden"
        >
          <div class="px-4 py-3 text-center text-xs text-slate-500">
            Aucune adresse trouvée pour « {{ searchQuery }} »
          </div>
          <div
            @click="switchToManual"
            class="flex items-center gap-2 px-4 py-3 cursor-pointer bg-slate-50 dark:bg-slate-700/30 hover:bg-slate-100 dark:hover:bg-slate-700/50 transition-colors border-t border-slate-200 dark:border-slate-700"
          >
            <i class="pi pi-pencil text-amber-500 text-xs"></i>
            <span class="text-xs text-amber-700 dark:text-amber-400 font-semibold">Saisir l'adresse manuellement</span>
          </div>
        </div>
      </div>
    </div>

    <!-- ── Mode saisie manuelle ──────────────────────────────────────────── -->
    <div v-if="isManualMode" class="border border-amber-200 dark:border-amber-800/50 rounded-xl p-4 bg-amber-50/60 dark:bg-amber-950/10 space-y-3">
      <div class="flex items-center justify-between mb-1">
        <span class="text-[10px] font-bold uppercase tracking-wider text-amber-700 dark:text-amber-400 flex items-center gap-1.5">
          <i class="pi pi-pencil"></i> Saisie manuelle de l'adresse
        </span>
        <button
          type="button"
          @click="switchToManual = false; isManualMode = false;"
          class="text-[10px] text-slate-500 hover:text-slate-700"
        >Annuler</button>
      </div>

      <input
        type="text"
        :value="internalAddress.adresse"
        @input="updateManualField('adresse', $event.target.value)"
        placeholder="Numéro et nom de rue *"
        class="w-full p-2.5 border border-slate-200 dark:border-slate-700 rounded-lg bg-white dark:bg-slate-900 text-xs text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-amber-400"
      />
      <input
        type="text"
        :value="internalAddress.complement1"
        @input="updateManualField('complement1', $event.target.value)"
        placeholder="Complément d'adresse (bâtiment, appartement…)"
        class="w-full p-2.5 border border-slate-200 dark:border-slate-700 rounded-lg bg-white dark:bg-slate-900 text-xs text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-amber-400"
      />
      <div class="grid grid-cols-2 gap-3">
        <input
          type="text"
          :value="internalAddress.codePostal"
          @input="updateManualField('codePostal', $event.target.value)"
          placeholder="Code postal *"
          maxlength="5"
          class="w-full p-2.5 border border-slate-200 dark:border-slate-700 rounded-lg bg-white dark:bg-slate-900 text-xs text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-amber-400"
        />
        <input
          type="text"
          :value="internalAddress.ville"
          @input="updateManualField('ville', $event.target.value)"
          placeholder="Ville *"
          class="w-full p-2.5 border border-slate-200 dark:border-slate-700 rounded-lg bg-white dark:bg-slate-900 text-xs text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-amber-400"
        />
      </div>
      <input
        type="text"
        :value="internalAddress.pays"
        @input="updateManualField('pays', $event.target.value)"
        placeholder="Pays"
        class="w-full p-2.5 border border-slate-200 dark:border-slate-700 rounded-lg bg-white dark:bg-slate-900 text-xs text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-amber-400"
      />
      <button
        type="button"
        @click="confirmManual"
        :disabled="!internalAddress.adresse || !internalAddress.ville || !internalAddress.codePostal"
        class="w-full py-2 rounded-lg text-xs font-bold bg-amber-500 hover:bg-amber-600 disabled:opacity-40 disabled:cursor-not-allowed text-white transition-all"
      >
        <i class="pi pi-check mr-1.5"></i>Valider cette adresse
      </button>
    </div>

    <!-- Hint -->
    <p v-if="!addressSelected && !isManualMode" class="text-[10px] text-slate-400">
      Tapez au moins 3 caractères pour lancer la recherche.
      <button type="button" @click="switchToManual" class="text-violet-600 hover:underline ml-1">Saisir manuellement</button>
    </p>
  </div>
</template>
