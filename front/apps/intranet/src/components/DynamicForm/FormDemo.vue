<!--
FormDemo - Exemples d'utilisation du DynamicForm
==============================================

Ce composant présente deux exemples d'utilisation du générateur de formulaires dynamiques :
1. Un formulaire utilisateur avec chargement asynchrone des rôles
2. Un formulaire produit avec chargement asynchrone des catégories
-->

<script setup>
import { ref } from 'vue'
import DynamicForm from './DynamicForm.vue'

// Configuration du formulaire utilisateur
const userFormConfig = {
  layout: 'one-column',
  saveUrl: '/api/users',
  fields: [
    {
      name: 'name',
      label: 'Nom complet',
      type: 'text',
      placeholder: 'John Doe',
      rules: 'required|min:3'
    },
    {
      name: 'email',
      label: 'Email',
      type: 'text',
      placeholder: 'john@example.com',
      rules: 'required|email'
    },
    {
      name: 'password',
      label: 'Mot de passe',
      type: 'password',
      placeholder: '••••••••',
      rules: 'required|min:8'
    },
    {
      name: 'role',
      label: 'Rôle',
      type: 'select',
      rules: 'required',
      options: {
        dataUrl: '/api/roles'
      }
    }
  ]
}

// Configuration du formulaire produit
const productFormConfig = {
  layout: 'two-columns',
  saveUrl: '/api/products',
  fields: [
    {
      name: 'name',
      label: 'Nom du produit',
      type: 'text',
      placeholder: 'Super produit',
      rules: 'required|min:3'
    },
    {
      name: 'price',
      label: 'Prix',
      type: 'number',
      placeholder: '0.00',
      rules: 'required'
    },
    {
      name: 'description',
      label: 'Description',
      type: 'textarea',
      placeholder: 'Description détaillée du produit...',
      rules: 'required|min:10'
    },
    {
      name: 'category',
      label: 'Catégorie',
      type: 'select',
      rules: 'required',
      options: {
        dataUrl: '/api/categories'
      }
    },
    {
      name: 'active',
      label: 'Produit actif',
      type: 'checkbox',
      default: true
    }
  ]
}

// Gestion des événements
const handleSuccess = (data) => {
  console.log('Soumission réussie:', data)
  // Ici vous pourriez afficher une notification de succès
}

const handleError = (error) => {
  console.error('Erreur lors de la soumission:', error)
  // Ici vous pourriez afficher une notification d'erreur
}

const handleCancel = () => {
  console.log('Formulaire annulé')
  // Ici vous pourriez rediriger l'utilisateur
}
</script>

<template>
  <div class="space-y-12 max-w-4xl mx-auto py-8">
    <!-- Formulaire utilisateur -->
    <div class="bg-white shadow sm:rounded-lg p-6">
      <h2 class="text-lg font-medium text-gray-900 mb-6">
        Création d'utilisateur
      </h2>
      
      <DynamicForm
        :form-config="userFormConfig"
        @submit-success="handleSuccess"
        @submit-error="handleError"
        @cancel="handleCancel"
      >
        <template #header>
          <p class="text-sm text-gray-500">
            Remplissez les informations pour créer un nouvel utilisateur.
          </p>
        </template>
      </DynamicForm>
    </div>

    <!-- Formulaire produit -->
    <div class="bg-white shadow sm:rounded-lg p-6">
      <h2 class="text-lg font-medium text-gray-900 mb-6">
        Création de produit
      </h2>
      
      <DynamicForm
        :form-config="productFormConfig"
        @submit-success="handleSuccess"
        @submit-error="handleError"
        @cancel="handleCancel"
      >
        <template #header>
          <p class="text-sm text-gray-500">
            Remplissez les informations pour créer un nouveau produit.
          </p>
        </template>

        <!-- Exemple de personnalisation d'un champ -->
        <template #field-price="{ field, value }">
          <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
              <span class="text-gray-500 sm:text-sm">€</span>
            </div>
            <Field
              :id="field.name"
              :name="field.name"
              type="number"
              step="0.01"
              min="0"
              :placeholder="field.placeholder"
              class="form-input block w-full pl-7 pr-12 sm:text-sm border-gray-300 rounded-md"
            />
          </div>
        </template>
      </DynamicForm>
    </div>
  </div>
</template> 