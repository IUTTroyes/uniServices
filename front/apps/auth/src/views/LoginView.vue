<script setup>
import { onMounted, ref, computed } from 'vue';
import Logo from '@components/components/Logo.vue';
import axios from 'axios';
import { tools } from '@config/uniServices.js';
import {ValidatedInput, validationRules} from "@components";

const username = ref('');
const password = ref('');
const checked = ref(false);
const errorMessage = ref('');
const isLoading = ref(false);
const formErrors = ref({});
const formValid = ref(true);

// Pagination variables
const currentPage = ref(0);
const itemsPerPage = 4;

// Compute paginated tools
const paginatedTools = computed(() => {
  const start = currentPage.value * itemsPerPage;
  const end = start + itemsPerPage;
  return tools.slice(start, end);
});

// Compute total pages
const totalPages = computed(() => Math.ceil(tools.length / itemsPerPage));

// Navigation methods
const nextPage = () => {
  if (currentPage.value < totalPages.value - 1) {
    currentPage.value++;
  }
};

const prevPage = () => {
  if (currentPage.value > 0) {
    currentPage.value--;
  }
};

const handleSubmit = async () => {
  if (!username.value || !password.value) {
    errorMessage.value = 'Veuillez remplir tous les champs';
    return;
  }

  isLoading.value = true;
  errorMessage.value = '';

  try {
    const response = await axios.post('https://127.0.0.1:8000/api/login', {
      username: username.value,
      password: password.value
    });

    console.log('API response:', response.data); // Log de la réponse de l'API

    const token = response.data.token;
    if (!token) {
      throw new Error('Token non valide');
    }

    localStorage.setItem('token', token);
    document.cookie = `token=${token}; path=/; domain=.localhost; secure; SameSite=Lax`;

    location.reload();
    location.href = '/auth/portail';
  } catch (error) {
    console.error('Login failed:', error);
    if (error.response) {
      console.error('Error response data:', error.response.data);
      console.error('Error response status:', error.response.status);
      console.error('Error response headers:', error.response.headers);
    }
    if (error.request) {
      console.error('Error request:', error.request);
    }
    errorMessage.value = error.response && error.response.status === 401
        ? 'Login incorrect ou mot de passe incorrect'
        : 'Une erreur est survenue, veuillez contacter l\'administrateur du site';
  } finally {
    isLoading.value = false;
  }
};

const handleValidation = (field, result) => {
  formErrors.value = {
    ...formErrors.value,
    [field]: result.isValid ? null : result.errorMessage
  };
  formValid.value = Object.values(formErrors.value).every(error => error === null);
};
</script>

<template>
  <div class="bg w-full h-screen fixed top-0 left-0 -z-10">
  </div>
  <div class="w-full min-h-screen flex justify-center items-center md:py-16 p-4">
    <div class="md:w-3/4 w-full h-full flex md:flex-row flex-col shadow-xl">
      <div class="bg-black bg-opacity-60 text-white backdrop-blur-sm p-12 md:rounded-tl-xl md:rounded-bl-xl rounded-tl-xl rounded-tr-xl w-full flex flex-col gap-4">
        <div class="flex items-center w-full gap-4">
          <Logo logo-url="common-images/logo/logo_iut.png" alt="logo de l'iut" class="w-1/4 rounded-md"/>
          <div>
            <div class="text-2xl font-bold">Bienvenue sur UniServices</div>
            <div>Plateforme de gestion centralisée des services universitaires</div>
          </div>
        </div>
        <div class="hidden md:block md:h-full">
          <Divider></Divider>
          <div class="flex flex-col justify-between h-full">
            <ul class="h-full flex flex-col justify-start gap-6 py-4">
              <li v-for="tool in paginatedTools" :key="tool.name" class="w-full p-0 flex items-center gap-4">
                <Logo :logo-url="tool.logo" alt="" class="logo_login"/>
                <div>
              <span class="font-bold text-lg">
                {{ tool.name }}
              </span>
                  <p>{{ tool.description }}</p>
                </div>
              </li>
            </ul>
            <div v-if="totalPages > 1" class="flex justify-center items-center gap-2 mt-4">
              <button
                  @click="prevPage"
                  :disabled="currentPage === 0"
                  class="pagination-btn"
                  :class="{ 'disabled': currentPage === 0 }"
              >
                &lt;
              </button>
              <span class="text-white">{{ currentPage + 1 }} / {{ totalPages }}</span>
              <button
                  @click="nextPage"
                  :disabled="currentPage === totalPages - 1"
                  class="pagination-btn"
                  :class="{ 'disabled': currentPage === totalPages - 1 }"
              >
                &gt;
              </button>
            </div>
          </div>
        </div>
      </div>
      <div class="bg-white p-12 md:rounded-tr-xl md:rounded-br-xl md:rounded-bl-none rounded-br-xl rounded-bl-xl w-full">
        <div class="text-center mb-8">
          <div class="text-surface-900 dark:text-surface-0 text-3xl font-medium mb-4 uppercase">Connexion</div>
          <span class="text-muted-color font-medium">Etudiants, personnels de l'Université et vacataires, connectez-vous avec l'authentification de l'Université.</span>
        </div>
        <Button label="Connexion URCA" class="w-full" as="router-link" to="/"></Button>

        <Divider></Divider>

        <p class="text-center">Compte invité</p>
        <form @submit.prevent="handleSubmit" class="flex flex-col">
          <ValidatedInput
              v-model="username"
              name="username"
              label="Login"
              :rules="validationRules.required"
              @validation="result => handleValidation('username', result)"
          />
          <ValidatedInput
              class="pwd"
              v-model="password"
              name="password"
              label="Mot de passe"
              type="password"
              :feedback="false"
              toggleMask
              :rules="validationRules.required"
              @validation="result => handleValidation('password', result)"
          />
          <div class="flex flex-col justify-between mb-4 gap-4">
            <div class="flex items-center">
              <Checkbox v-model="checked" id="rememberme1" binary class="mr-2"></Checkbox>
              <label for="rememberme1">Se souvenir de moi</label>
            </div>
            <router-link to="/reset-password" class="font-medium underline ml-2 text-right cursor-pointer text-primary">Mot de passe oublié ?</router-link>
          </div>
          <div class="w-full flex flex-col gap-2">
            <Message v-if="!formValid" severity="error">
              Veuillez corriger les erreurs dans le formulaire avant de soumettre
            </Message>
            <Button :label="isLoading ? 'Connexion...' : 'Connexion invité'" class="w-full" type="submit"
                    severity="secondary" :disabled="isLoading || !formValid"></Button>
          </div>
        </form>
        <small class="text-muted-color">En cas de problème de connexion, contactez le support à cette adresse :
          intranet.iut-troyes@univ-reims.fr</small>
      </div>
    </div>

  </div>
</template>

<style scoped>
.bg {
  background-image: url("@/assets/iut.jpg");
  background-size: cover;
  background-position: center;
  background-repeat: no-repeat;
}

.pagination-btn {
  width: 30px;
  height: 30px;
  border-radius: 50%;
  background-color: rgba(255, 255, 255, 0.2);
  color: white;
  display: flex;
  justify-content: center;
  align-items: center;
  cursor: pointer;
  transition: background-color 0.3s;
}

.pagination-btn:hover:not(.disabled) {
  background-color: rgba(255, 255, 255, 0.4);
}

.pagination-btn.disabled {
  opacity: 0.5;
  cursor: not-allowed;
}
</style>
