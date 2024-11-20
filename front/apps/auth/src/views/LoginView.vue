<script setup>
import { ref } from 'vue';
import axios from 'axios';

const props = defineProps({
  logoUrl: {
    type: String,
    required: true
  }
});

const username = ref('');
const password = ref('');
const checked = ref(false);
const handleSubmit = async () => {
  try {
    const response = await axios.post('http://localhost:8000/api/login', {
      username: username.value,
      password: password.value
    });
    console.log('Login successful:', response.data);
    localStorage.setItem('token', response.data.token);
    document.cookie = `token=${response.data.token}; Secure; SameSite=None`;
    location.reload();
    location.href = '/auth/portail';
  } catch (error) {
    console.error('Login failed:', error.response.data);
    // Handle login error (e.g., show error message)
  }
};
</script>

<template>
  <div class="bg-surface-50 dark:bg-surface-950 flex items-center justify-center min-h-screen min-w-[100vw] overflow-hidden">
    <div class="flex flex-col items-center justify-center form">
      <div style="border-radius: 56px; padding: 0.3rem; background: linear-gradient(180deg, var(--primary-color) 10%, rgba(33, 150, 243, 0) 30%)">
        <div class="w-full bg-surface-0 dark:bg-surface-900 py-20 px-8 sm:px-20" style="border-radius: 53px">
          <div class="text-center mb-8">
            <img :src="logoUrl" alt="logo de l'iut" class="logo" />
            <div class="text-surface-900 dark:text-surface-0 text-3xl font-medium mb-4 uppercase">Connexion</div>
            <span class="text-muted-color font-medium">Etudiants, personnels de l'Université et vacataires, connectez-vous avec l'authentification de l'Université.</span>
          </div>
          <Button label="Connexion URCA" class="w-full" as="router-link" to="/"></Button>

          <hr>

          <form @submit.prevent="handleSubmit" class="flex flex-col gap-8">
            <IftaLabel>
              <InputText id="username" class="w-full md:w-[30rem] mb-8" v-model="username" />
              <label for="username">Login</label>
            </IftaLabel>
            <IftaLabel>
              <InputText id="password" :toggleMask="true" fluid :feedback="false" class="w-full md:w-[30rem] mb-8" v-model="password" />
              <label for="password">Mot de passe</label>
            </IftaLabel>

            <div class="flex items-center justify-between mt-2 mb-8 gap-8">
              <div class="flex items-center">
                <Checkbox v-model="checked" id="rememberme1" binary class="mr-2"></Checkbox>
                <label for="rememberme1">Se souvenir de moi</label>
              </div>
              <span class="font-medium no-underline ml-2 text-right cursor-pointer text-primary">Mot de passe oublié ?</span>
            </div>
            <Button label='Connexion invité' class="w-full" type="submit"></Button>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.logo {
  width: 150px;
  margin: 0 auto 2rem auto;
}

.form {
  width: 100%;
  max-width: 40rem;
}

hr {
  border: 0;
  border-top: 1px solid lightgray;
  margin: 2rem 0;
}

.pi-eye {
  transform: scale(1.6);
  margin-right: 1rem;
}

.pi-eye-slash {
  transform: scale(1.6);
  margin-right: 1rem;
}
</style>
