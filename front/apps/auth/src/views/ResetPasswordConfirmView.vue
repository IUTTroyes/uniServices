<script setup>
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import Logo from '@components/components/Logo.vue';
import { ValidatedInput, validationRules } from "@components";
import { resetPasswordService } from "@requests";
import { useToast } from "primevue/usetoast";

const toast = useToast();
const route = useRoute();
const router = useRouter();

const password = ref('');
const confirmPassword = ref('');
const token = ref('');
const formValid = ref(false);
const formErrors = ref({});
const errorMessage = ref('');
const successMessage = ref('');
const loading = ref(false);

const validationState = ref({
  password: false,
  confirmPassword: false,
});

onMounted(() => {
  token.value = route.query.token;
  if (!token.value) {
    errorMessage.value = 'Token manquant. Veuillez utiliser le lien fourni dans l\'email.';
  }
});

const handleValidation = (field, result) => {
  formErrors.value = {
    ...formErrors.value,
    [field]: result.isValid ? null : result.errorMessage
  };
  validationState.value[field] = result.isValid;
  formValid.value = Object.values(validationState.value).every(isValid => isValid);
};

const handleSubmit = async () => {
  if (!formValid.value || !token.value) {
    return;
  }

  loading.value = true;
  errorMessage.value = '';
  successMessage.value = '';

  try {
    await resetPasswordService(token.value, password.value);
    successMessage.value = 'Votre mot de passe a été réinitialisé avec succès.';

    // Rediriger vers la page de connexion après 3 secondes
    setTimeout(() => {
      router.push('/login');
    }, 3000);
  } catch (error) {
    console.error('Error:', error);
    errorMessage.value = error.response?.data?.error || 'Une erreur est survenue lors de la réinitialisation du mot de passe.';
  } finally {
    loading.value = false;
  }
};
</script>

<template>
  <div class="bg bg-surface-50 dark:bg-surface-950 flex flex-wrap items-center justify-center min-h-screen overflow-hidden md:py-16 p-4">
    <div class="login-container flex flex-col md:w-3/4 w-full h-full">
      <div class="bg-black bg-opacity-60 text-white backdrop-blur-sm p-12 rounded-tl-xl rounded-tr-xl w-full flex flex-col gap-4">
        <div class="flex items-center w-full gap-4">
          <Logo logo-url="common-images/logo/logo_iut.png" alt="logo de l'iut" class="w-1/4 rounded-md"/>
          <div>
            <div class="text-2xl font-bold">Bienvenue sur UniServices</div>
            <div>Plateforme de gestion centralisée des services universitaires</div>
          </div>
        </div>
      </div>
      <div class="form-section flex flex-col items-center justify-center min-h-full">
        <div class="form-container w-full bg-surface-0 dark:bg-surface-900 py-10 px-8 sm:px-20 h-full rounded-bl-xl rounded-br-xl">
          <div class="text-center mb-8">
            <div class="text-surface-900 dark:text-surface-0 text-3xl font-medium mb-4 uppercase">Réinitialisation de mot de passe</div>
            <span class="text-muted-color font-medium">Veuillez entrer votre nouveau mot de passe</span>
          </div>
          <Divider></Divider>
          <form @submit.prevent="handleSubmit" class="flex flex-col mb-8 mt-8">
            <ValidatedInput
                v-model="password"
                name="password"
                type="password"
                label="Nouveau mot de passe"
                :rules="[validationRules.required, validationRules.minLength(8)]"
                @validation="result => handleValidation('password', result)"
            />
            <ValidatedInput
                v-model="confirmPassword"
                name="confirmPassword"
                type="password"
                label="Confirmer le mot de passe"
                :rules="[validationRules.required, validationRules.match(password, 'Les mots de passe ne correspondent pas')]"
                @validation="result => handleValidation('confirmPassword', result)"
            />
            <div class="mb-4 flex justify-end items-center">
              <router-link to="/login" class="font-medium ml-2 text-right cursor-pointer text-primary underline">Retour au login</router-link>
            </div>
            <div class="w-full flex flex-col gap-2">
              <Message v-if="!formValid" severity="error">
                Veuillez corriger les erreurs dans le formulaire avant de soumettre
              </Message>
              <Message v-else-if="errorMessage" severity="error">
                {{ errorMessage }}
              </Message>
              <Message v-else-if="successMessage" severity="success">
                {{ successMessage }}
              </Message>
              <Button label="Réinitialiser le mot de passe" class="w-full" type="submit" :disabled="!formValid || loading"
                      severity="secondary"></Button>
            </div>
          </form>
          <small class="text-muted-color">En cas de problème de connexion, contactez le support à cette adresse :
            intranet.iut-troyes@univ-reims.fr</small>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.bg {
  background-image: url("../assets/iut.jpg");
  background-size: cover;
  background-position: center;
  background-repeat: no-repeat;
}
</style>
