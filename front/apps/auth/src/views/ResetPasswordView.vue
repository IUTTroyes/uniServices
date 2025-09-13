<script setup>
import { ref } from 'vue';
import Logo from '@components/components/Logo.vue';
import axios from 'axios';
import { tools } from '@config/uniServices.js';
import {ValidatedInput, validationRules} from "@components";

const email = ref('');

const validationState = ref({
  email: false,
});

const handleValidation = (field, result) => {
  validationState.value[field] = result.valid;
};
</script>

<template>
  <div
      class="bg bg-surface-50 dark:bg-surface-950 flex flex-wrap items-center justify-center min-h-screen overflow-hidden p-12">
    <div class="login-container flex flex-col">
      <div class="info-section bg-black bg-opacity-60 text-white backdrop-blur-sm flex justify-start gap-4 h-full">
        <div class="p-16 flex flex-col justify-center items-center w-full">
          <Logo logo-url="common-images/logo/logo_iut.png" alt="logo de l'iut" class="logo"/>
          <h2>Bienvenue sur UniServices</h2>
          <p>Plateforme de gestion centralisée des services universitaires</p>

        </div>
      </div>
      <div class="form-section flex flex-col items-center justify-center min-h-full">
        <div class="form-container w-full bg-surface-0 dark:bg-surface-900 py-10 px-8 sm:px-20 h-full">
          <div class="text-center mb-8">
            <div class="text-surface-900 dark:text-surface-0 text-3xl font-medium mb-4 uppercase">Mot de passe perdu ?</div>
            <span class="text-muted-color font-medium">Cette fonctionnalité est disponible pour les utilisateurs invités ou les vacataires qui ne dépendent pas de l'URCA.</span>
          </div>

          <hr>
          <form @submit.prevent="handleSubmit" class="flex flex-col mb-8 mt-8">
            <ValidatedInput
                v-model="email"
                name="email"
                label="Adresse mail"
                :rules="validationRules.email"
                @validation="result => handleValidation('email', result)"
            />
            <div class="mb-4 flex justify-end items-center">
            <router-link to="/login" class="font-medium ml-2 text-right cursor-pointer text-primary underline">Retour au login</router-link>
            </div>
            <Button label="Ré-initialiser le mot de passe" class="w-full" type="submit"
                    severity="secondary"></Button>
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
  border-top-right-radius: 15px;
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
    border-bottom-left-radius: 15px;
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
