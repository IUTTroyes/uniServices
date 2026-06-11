<script setup lang="ts">
import { ref, onMounted, watch } from 'vue';
import AutoComplete from 'primevue/autocomplete';
import { searchAddresses } from "@requests";

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
    default: 'Entrez une adresse (minimum 3 caractères)'
  },
  inputClass: {
    type: String,
    default: 'w-full'
  },
  country: {
    type: String,
    default: 'fr'
  },
  showError: {
    type: Boolean,
    default: false
  },
  disabled: {
    type: Boolean,
    default: false
  }
});

const emit = defineEmits([
  'update:modelValue',
  'select',
  'blur'
]);

const searchQuery = ref('');
const suggestions = ref([]);
const isLoading = ref(false);

const handleSearch = async (event) => {
  if (event.query && event.query.length >= 3) {
    isLoading.value = true;
    try {
      const results = await searchAddresses(event.query, props.country);
      // Ajouter un champ displayLabel pour l'affichage dans l'input
      suggestions.value = results.map(item => {
        const parts = [];
        if (item.address) parts.push(item.address);
        if (item.postalCode) parts.push(item.postalCode);
        if (item.city) parts.push(item.city);

        return {
          ...item,
          displayLabel: parts.length > 0 ? parts.join(', ') : item.label || item.value
        };
      });
    } catch (error) {
      console.error('Erreur de recherche d\'adresses:', error);
      suggestions.value = [];
    } finally {
      isLoading.value = false;
    }
  } else {
    suggestions.value = [];
  }
};

const handleSelect = (event) => {
  const selectedItem = event.value;

  if (selectedItem) {
    const updatedAddress = {
      adresse: selectedItem.address || selectedItem.label,
      complement1: '',
      complement2: '',
      ville: selectedItem.city,
      codePostal: selectedItem.postalCode,
      pays: selectedItem.country || 'France'
    };

    emit('update:modelValue', updatedAddress);
    emit('select', {
      address: updatedAddress,
      raw: selectedItem.raw,
      coordinates: {
        lat: selectedItem.lat,
        lon: selectedItem.lon
      }
    });

    // Format l'adresse complète pour l'affichage
    const fullAddress = formatAddressDisplay(updatedAddress);
    searchQuery.value = fullAddress;
  }
};

const formatAddressDisplay = (address) => {
  const parts = [];
  if (address.adresse) parts.push(address.adresse);
  if (address.codePostal) parts.push(address.codePostal);
  if (address.ville) parts.push(address.ville);
  return parts.filter(p => p).join(', ');
};

const handleBlur = (event) => {
  emit('blur', event);
};

onMounted(() => {
  if (props.modelValue && (props.modelValue.adresse || props.modelValue.ville || props.modelValue.codePostal)) {
    searchQuery.value = formatAddressDisplay(props.modelValue);
  }
});

watch(() => props.modelValue, (newValue) => {
  if (newValue && (newValue.adresse || newValue.ville || newValue.codePostal)) {
    searchQuery.value = formatAddressDisplay(newValue);
  }
}, { deep: true });
</script>

<template>
  <AutoComplete
    v-model="searchQuery"
    :suggestions="suggestions"
    :loading="isLoading"
    @complete="handleSearch"
    @item-select="handleSelect"
    @blur="handleBlur"
    :placeholder="placeholder"
    :class="[inputClass, { 'p-invalid': showError }]"
    :disabled="disabled"
    optionLabel="displayLabel"
    class="w-full"
  >
    <template #item="slotProps">
      <div class="flex flex-col gap-1 w-full">
        <span class="font-semibold text-sm">{{ slotProps.item.address }}</span>
        <span class="text-xs text-gray-500" v-if="slotProps.item.postalCode || slotProps.item.city">
          {{ slotProps.item.postalCode }} {{ slotProps.item.city }}
        </span>
      </div>
    </template>
    <template #empty>
      <div class="p-3 text-center text-sm text-gray-500">
        Aucune adresse trouvée
      </div>
    </template>
  </AutoComplete>
</template>

 <style scoped>
 /* Style pour le composant AutoComplete si nécessaire */
 </style>

