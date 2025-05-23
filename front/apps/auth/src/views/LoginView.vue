<script setup>
import { onMounted, ref } from 'vue';
import Logo from '@components/components/Logo.vue';
import axios from 'axios';
import { tools } from '@config/uniServices.js';

const username = ref('');
const password = ref('');
const checked = ref(false);
const errorMessage = ref('');
const isLoading = ref(false);

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
</script>

<template>
  <div
      class="bg bg-surface-50 dark:bg-surface-950 flex flex-wrap items-center justify-center min-h-screen overflow-hidden">
    <div class="login-container flex">
      <div class="info-section bg-black bg-opacity-60 text-white backdrop-blur-sm flex justify-start gap-4 h-full">
        <div class="p-16">
          <Logo logo-url="common-images/logo/logo_iut.png" alt="logo de l'iut" class="logo"/>
          <h2>Bienvenue sur UniServices</h2>
          <p>Plateforme de gestion centralisée des services universitaires</p>

          <ul>
            <li v-for="tool in tools" :key="tool.name">
              <Logo :logo-url="tool.logo" alt="" class="logo_login"/>
              <div>
                <span>
                  {{ tool.name }}
                </span>
                <p>
                  {{ tool.description }}
                </p>
              </div>
            </li>
          </ul>
        </div>
      </div>
      <div class="form-section flex flex-col items-center justify-center min-h-full">
        <div class="form-container w-full bg-surface-0 dark:bg-surface-900 py-10 px-8 sm:px-20 h-full">
          <div class="text-center mb-8">
            <div class="text-surface-900 dark:text-surface-0 text-3xl font-medium mb-4 uppercase">Connexion</div>
            <span class="text-muted-color font-medium">Etudiants, personnels de l'Université et vacataires, connectez-vous avec l'authentification de l'Université.</span>
          </div>
          <Button label="Connexion URCA" class="w-full" as="router-link" to="/"></Button>

          <hr>

          <p class="text-center">Compte invité</p>
          <form @submit.prevent="handleSubmit" class="flex flex-col mb-8 mt-8">
            <IftaLabel>
              <InputText id="username" class="w-full mb-4" v-model="username"/>
              <label for="username">Login</label>
            </IftaLabel>
            <IftaLabel>
              <Password v-model="password" inputId="password" :feedback="false" class="w-full mb-2 pwd" toggleMask/>
              <label for="password">Password</label>
            </IftaLabel>

            <div class="flex flex-col justify-between mb-4 gap-4">
              <div v-if="errorMessage" class="error-message">{{ errorMessage }}</div>
              <div class="flex items-center">
                <Checkbox v-model="checked" id="rememberme1" binary class="mr-2"></Checkbox>
                <label for="rememberme1">Se souvenir de moi</label>
              </div>
              <span
                  class="font-medium no-underline ml-2 text-right cursor-pointer text-primary">Mot de passe oublié ?</span>
            </div>
            <Button :label="isLoading ? 'Connexion...' : 'Connexion invité'" class="w-full" type="submit"
                    severity="secondary" :disabled="isLoading"></Button>
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
  background-image: url("@/assets/iut.jpg");
  background-size: cover;
  background-position: center;
  background-repeat: no-repeat;
}

.logo {
  width: 100px;
  border-radius: 10px;
}

.login-container {
  display: flex;
  width: 100%;
  max-width: 70%;
  height: 100%;
}

.info-section {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: start;
  justify-content: start;
  gap: 1rem;
  border-bottom-left-radius: 15px;
  border-top-left-radius: 15px;

  h2 {
    font-size: 1.5rem;
    font-weight: 900;
    color: whitesmoke;
  }

  ul {

    li {
      display: flex;
      align-items: center;
      gap: 1rem;
      margin: 3rem 0;

      .logo {
        width: 50px;
        height: 50px;
        border-radius: 10px;
        background-color: rgb(255, 255, 255);
      }

      span {
        font-size: 1.25rem;
        font-weight: 700;
      }
    }
  }
}

.form-section {
  flex: 1;
  display: flex;
  justify-content: center;
  align-items: center;

  .form-container {
    border-bottom-right-radius: 15px;
    border-top-right-radius: 15px;
  }
}

hr {
  border: 0;
  border-top: 1px solid lightgray;
  margin: 2rem 0;
}

.p-password {
  flex-direction: column;
  justify-content: flex-start;
}

.error-message {
  color: red;
  margin-top: 1rem;
  text-align: center;
}
</style>
