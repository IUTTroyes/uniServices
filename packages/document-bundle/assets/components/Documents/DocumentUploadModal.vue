<template>
  <Dialog
    :visible="visible"
    modal
    header="Ajouter un nouveau document"
    :style="{ width: '50rem' }"
    :breakpoints="{ '960px': '75vw', '641px': '90vw' }"
    @update:visible="$emit('update:visible', $event)"
  >
    <form @submit.prevent="handleSubmit" class="space-y-4 pt-2">
      <!-- Title -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Titre du document *</label>
        <input
          v-model="form.titre"
          type="text"
          required
          placeholder="ex: Guide de rentrée 2024-2025"
          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500"
        />
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <!-- Type -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Type de fichier</label>
          <select
            v-model="form.type"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500 bg-white"
          >
            <option value="pdf">📄 PDF</option>
            <option value="word">📝 Document Word</option>
            <option value="excel">📊 Tableur Excel</option>
            <option value="powerpoint">📊 Présentation PowerPoint</option>
            <option value="image">🖼️ Image</option>
            <option value="archive">📦 Archive ZIP</option>
            <option value="text">📄 Texte</option>
          </select>
        </div>

        <!-- Category -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Catégorie</label>
          <select
            v-model="form.categoryId"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500 bg-white"
          >
            <option value="">-- Sans catégorie --</option>
            <option v-for="cat in flatCategories" :key="cat.id" :value="cat.id">
              {{ cat.name }}
            </option>
          </select>
        </div>
      </div>

      <!-- Description -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
        <textarea
          v-model="form.description"
          rows="3"
          placeholder="Brève description du contenu..."
          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500"
        ></textarea>
      </div>

      <!-- Tags -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Tags (séparés par des virgules)</label>
        <input
          v-model="tagInput"
          type="text"
          placeholder="important, RH, stage"
          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500"
        />
      </div>

      <!-- Drag and Drop Dropzone Mock -->
      <div
        class="border-2 border-dashed border-gray-300 hover:border-primary-400 rounded-lg p-6 text-center cursor-pointer transition-colors bg-gray-50 hover:bg-gray-100"
      >
        <div class="text-3xl mb-2">📁</div>
        <p class="text-sm font-medium text-gray-700">Glissez-déposez votre fichier ici, ou parcourez vos dossiers</p>
        <p class="text-xs text-gray-500 mt-1">Formats acceptés : PDF, DOCX, XLSX, PNG, ZIP (Max 50 Mo)</p>
      </div>

      <!-- Actions -->
      <div class="flex justify-end space-x-3 pt-4 border-t border-gray-200">
        <button
          type="button"
          @click="$emit('update:visible', false)"
          class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50"
        >
          Annuler
        </button>
        <button
          type="submit"
          :disabled="loading"
          class="px-4 py-2 text-sm font-medium text-white bg-primary-600 rounded-md hover:bg-primary-700 disabled:opacity-50 flex items-center space-x-2"
        >
          <span v-if="loading" class="animate-spin">⏳</span>
          <span>Enregistrer le document</span>
        </button>
      </div>
    </form>
  </Dialog>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import type { Category } from '@types';

interface Props {
  visible: boolean;
  categories: Category[];
}

const props = defineProps<Props>();

const emit = defineEmits<{
  'update:visible': [value: boolean];
  submit: [docData: { titre: string; description?: string; type: string; categoryId?: string; tags?: string[] }];
}>();

const loading = ref(false);
const tagInput = ref('');

const form = ref({
  titre: '',
  description: '',
  type: 'pdf',
  categoryId: ''
});

const flatCategories = computed(() => {
  const result: Category[] = [];
  const walk = (cats: Category[]) => {
    for (const c of cats) {
      result.push(c);
      if (c.children) walk(c.children);
    }
  };
  walk(props.categories);
  return result;
});

const handleSubmit = async () => {
  if (!form.value.titre.trim()) return;

  loading.value = true;
  const tags = tagInput.value
    .split(',')
    .map(t => t.trim())
    .filter(t => t.length > 0);

  emit('submit', {
    ...form.value,
    tags
  });

  loading.value = false;
  form.value = { titre: '', description: '', type: 'pdf', categoryId: '' };
  tagInput.value = '';
  emit('update:visible', false);
};
</script>
