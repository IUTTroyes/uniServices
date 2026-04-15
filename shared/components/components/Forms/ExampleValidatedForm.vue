<script setup>
import { ref } from 'vue';
import ValidatedInput from './ValidatedInput.vue';
import { validationRules } from '@components';
import Button from 'primevue/button';
import Message from 'primevue/message';

const formData = ref({
  name: '',
  email: '',
  phone: '',
  website: '',
  postalCode: ''
});

const formValid = ref(true);
const formSubmitted = ref(false);
const formErrors = ref({});

const handleValidation = (field, result) => {
  formErrors.value = {
    ...formErrors.value,
    [field]: result.isValid ? null : result.errorMessage
  };

  // Check if all fields are valid
  formValid.value = Object.values(formErrors.value).every(error => error === null);
};

const submitForm = () => {
  formSubmitted.value = true;

  if (formValid.value) {
    // Here you would typically send the data to an API
    console.log('Form submitted successfully:', formData.value);
  } else {
    console.log('Form has validation errors');
  }
};
</script>

<template>
  <div class="example-form p-4">
    <h2 class="text-2xl font-bold mb-4">Exemple de formulaire validé</h2>
    <p class="mb-4">Ce formulaire démontre l'utilisation du système de validation des formulaires.</p>

    <form @submit.prevent="submitForm" class="flex flex-col gap-4">
      <ValidatedInput
          v-model="formData.name"
          name="name"
          label="Nom"
          :rules="validationRules.required"
          @validation="result => handleValidation('name', result)"
          help-text="Entrez votre nom complet"
      />

      <ValidatedInput
          v-model="formData.email"
          name="email"
          label="Email"
          :rules="['required', 'email']"
          @validation="result => handleValidation('email', result)"
          help-text="Nous ne partagerons jamais votre email"
      />

      <ValidatedInput
          v-model="formData.phone"
          name="phone"
          label="Téléphone"
          :rules="validationRules.phone"
          @validation="result => handleValidation('phone', result)"
          help-text="Format: +33 1 23 45 67 89 ou 01 23 45 67 89"
      />

      <ValidatedInput
          v-model="formData.website"
          name="website"
          label="Site web"
          :rules="validationRules.url"
          @validation="result => handleValidation('website', result)"
          help-text="Exemple: https://mon-site.com"
      />

      <ValidatedInput
          v-model="formData.postalCode"
          name="postalCode"
          label="Code postal"
          :rules="validationRules.postalCode"
          @validation="result => handleValidation('postalCode', result)"
          help-text="5 chiffres"
      />

      <div class="mt-4">
        <Button type="submit" label="Soumettre" severity="primary" />
      </div>

      <Message v-if="formSubmitted && !formValid" severity="error" text="Veuillez corriger les erreurs dans le formulaire" />
      <Message v-if="formSubmitted && formValid" severity="success" text="Formulaire soumis avec succès !" />
    </form>

    <div class="mt-8 p-4 border rounded bg-gray-50">
      <h3 class="text-lg font-bold mb-2">Comment utiliser le système de validation</h3>
      <p class="mb-2">1. Importez le composant ValidatedInput et les règles de validation :</p>
      <pre class="bg-gray-100 p-2 rounded">
import ValidatedInput from '@/components/Forms/ValidatedInput.vue';
import { validationRules } from '@/utils/formValidation';
      </pre>

      <p class="mb-2 mt-4">2. Utilisez le composant dans votre template :</p>
      <pre class="bg-gray-100 p-2 rounded">
&lt;ValidatedInput
  v-model="formData.email"
  name="email"
  label="Email"
  :rules="['required', 'email']"
  @validation="result => handleValidation('email', result)"
/&gt;
      </pre>

      <p class="mb-2 mt-4">3. Vous pouvez utiliser des règles prédéfinies ou créer les vôtres :</p>
      <pre class="bg-gray-100 p-2 rounded">
// Règle prédéfinie
:rules="validationRules.email"

// Plusieurs règles
:rules="['required', 'email']"

// Règle personnalisée
:rules="{
  validate: value => value.length > 5,
  message: 'Doit contenir plus de 5 caractères'
}"
      </pre>
    </div>
  </div>
</template>

<style scoped>
.example-form {
  max-width: 800px;
  margin: 0 auto;
}

pre {
  white-space: pre-wrap;
  word-break: break-word;
}
</style>
