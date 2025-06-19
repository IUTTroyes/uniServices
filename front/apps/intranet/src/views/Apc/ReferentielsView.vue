<template>
  <div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
      <h1 class="text-2xl font-bold">Référentiels de compétences</h1>
      <button
        @click="showAddModal = true"
        class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700"
      >
        Ajouter un référentiel
      </button>
    </div>

    <!-- Liste des référentiels -->
    <div class="bg-white shadow rounded-lg">
      <div v-if="loading" class="p-4 text-center">
        Chargement...
      </div>
      <div v-else-if="error" class="p-4 text-center text-red-600">
        {{ error }}
      </div>
      <div v-else class="divide-y">
        <div v-for="referentiel in referentiels" :key="referentiel.id" class="p-4">
          <h3 class="text-lg font-semibold">{{ referentiel.libelle }}</h3>
          <p v-if="referentiel.description" class="text-gray-600 mt-1">
            {{ referentiel.description }}
          </p>
        </div>
      </div>
    </div>

    <!-- Modal d'ajout -->
    <Modal v-model="showAddModal" title="Ajouter un référentiel">
      <form @submit.prevent="handleSubmit" class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700">Libellé</label>
          <input
            v-model="newReferentiel.libelle"
            type="text"
            required
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
          />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700">Description</label>
          <textarea
            v-model="newReferentiel.description"
            rows="3"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
          ></textarea>
        </div>
        <div class="flex justify-end space-x-3">
          <button
            type="button"
            @click="showAddModal = false"
            class="px-4 py-2 border rounded-md hover:bg-gray-50"
          >
            Annuler
          </button>
          <button
            type="submit"
            class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700"
          >
            Ajouter
          </button>
        </div>
      </form>
    </Modal>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'

const referentiels = ref([])
const loading = ref(true)
const error = ref(null)
const showAddModal = ref(false)
const newReferentiel = ref({
  libelle: '',
  description: ''
})
import { useToast } from 'primevue/usetoast';
const toast = useToast();

const fetchReferentiels = async () => {
  try {
    const response = await fetch('/api/apc/referentiel')
    if (!response.ok) throw new Error('Erreur lors du chargement des référentiels')
    referentiels.value = await response.json()
  } catch (e) {
    error.value = e.message
  } finally {
    loading.value = false
  }
}

const handleSubmit = async () => {
  try {
    const response = await fetch('/api/apc/referentiel', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify(newReferentiel.value)
    })

    if (!response.ok) throw new Error('Erreur lors de la création du référentiel')

    const data = await response.json()
    referentiels.value.push(data)
    showAddModal.value = false
    newReferentiel.value = { libelle: '', description: '' }
    toast.success('Référentiel créé avec succès')
  } catch (e) {
    toast.error(e.message)
  }
}

onMounted(fetchReferentiels)
</script> 