<template>
  <div class="relative">
    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
      <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
      </svg>
    </div>
    <input
      v-model="searchQuery"
      @input="handleSearch"
      type="text"
      placeholder="Rechercher des documents..."
      class="block w-full pl-10 pr-4 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-primary-500 focus:border-primary-500"
    />
    <div
      v-if="searchQuery"
      class="absolute inset-y-0 right-0 pr-3 flex items-center"
    >
      <button
        @click="clearSearch"
        class="h-5 w-5 text-gray-400 hover:text-gray-600"
      >
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
        </svg>
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue';

interface Props {
  modelValue: string;
}

const props = defineProps<Props>();

const emit = defineEmits<{
  'update:modelValue': [value: string];
  search: [query: string];
}>();

const searchQuery = ref(props.modelValue);

watch(() => props.modelValue, (newValue) => {
  searchQuery.value = newValue;
});

const handleSearch = () => {
  emit('update:modelValue', searchQuery.value);
  emit('search', searchQuery.value);
};

const clearSearch = () => {
  searchQuery.value = '';
  emit('update:modelValue', '');
  emit('search', '');
};
</script>