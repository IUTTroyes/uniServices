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
            <div class="text-surface-900 dark:text-surface-0 text-3xl font-medium mb-4 uppercase">Mot de passe perdu ?</div>
            <span class="text-muted-color font-medium">Cette fonctionnalité est disponible pour les utilisateurs invités ou les vacataires qui ne dépendent pas de l'URCA.</span>
          </div>
          <Divider></Divider>
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
</style>
