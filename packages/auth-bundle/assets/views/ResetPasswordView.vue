<script setup>
import { onMounted, ref } from 'vue';
import axios from 'axios';
import Logo from '@components/components/Logo.vue';
import LogoIut from '@images/logo/logo_iut.png';
import { Card, ValidatedInput, validationRules } from '@components';
import { updateUserPasswordService } from '@requests';
import { resolveLogoEtablissementUrl } from '@helpers';

const email = ref('');
const emailValide = ref(false);
const isLoading = ref(false);
const envoye = ref(false);
const erreur = ref('');
const logoUrl = ref(LogoIut);
const urlCas = ref('');

const handleValidation = (result) => {
  emailValide.value = result.isValid;
};

const handleSubmit = async () => {
  if (!email.value || !emailValide.value) {
    erreur.value = 'Saisissez une adresse mail valide.';
    return;
  }

  isLoading.value = true;
  erreur.value = '';
  try {
    await updateUserPasswordService(email.value, false);
    envoye.value = true;
  } catch (e) {
    erreur.value = e?.response?.data?.message ?? 'La demande n’a pas pu être envoyée. Réessayez plus tard.';
  } finally {
    isLoading.value = false;
  }
};

onMounted(async () => {
  try {
    // Client sans intercepteur d'authentification : la page est publique.
    const { data } = await axios.get('/api/public/login-branding', { baseURL: import.meta.env.VITE_BASE_URL || '' });
    logoUrl.value = (data?.logo_name && resolveLogoEtablissementUrl(data.logo_name)) || LogoIut;
    urlCas.value = data?.url_mot_de_passe_cas ?? '';
  } catch (e) {
    logoUrl.value = LogoIut;
  }
});
</script>

<template>
  <div class="w-full min-h-screen flex justify-center items-center p-4 bg-surface-50 dark:bg-surface-950">
    <div class="w-full max-w-lg flex flex-col gap-4">
      <div class="flex items-center gap-4">
        <Logo :logo-url="logoUrl" alt="logo de l'établissement" class="w-16 rounded-lg shrink-0" />
        <div class="text-xl font-bold">UniServices</div>
      </div>

      <Card title="Mot de passe oublié" subtitle="Pour les comptes qui se connectent avec un mot de passe UniServices" icon="pi pi-key" color="blue">
        <div v-if="envoye" class="flex flex-col gap-4">
          <Message severity="success">
            Si cette adresse correspond à un compte, un message vient d'y être envoyé. Pensez à vérifier les indésirables.
          </Message>
          <Button as="router-link" to="/auth/login" label="Retour à la connexion" icon="pi pi-arrow-left" severity="secondary" />
        </div>

        <form v-else class="flex flex-col gap-3" @submit.prevent="handleSubmit">
          <ValidatedInput
            v-model="email"
            name="email"
            label="Adresse mail"
            :rules="[validationRules.required, validationRules.email]"
            @validation="handleValidation"
          />
          <Message v-if="erreur" severity="error">{{ erreur }}</Message>
          <Button type="submit" label="Recevoir un lien de réinitialisation" icon="pi pi-send" :loading="isLoading" :disabled="isLoading" />
          <router-link to="/auth/login" class="text-sm text-right underline text-surface-700 dark:text-surface-200">Retour à la connexion</router-link>
        </form>
      </Card>

      <Card v-if="urlCas" title="Compte universitaire" subtitle="Votre mot de passe est géré par votre université" icon="pi pi-building" color="amber">
        <Button as="a" :href="urlCas" rel="noopener noreferrer" label="Gérer mon mot de passe universitaire" icon="pi pi-external-link" severity="secondary" class="w-full" />
      </Card>
    </div>
  </div>
</template>
