<script setup>
/**
 * Habillage des e-mails de l'établissement : couleur, expéditeur, signature et mentions légales imposées dans le pied de
 * page de tous les e-mails (l'éditeur des modèles ne peut pas les retirer). Le lien vers les préférences de notification
 * est ajouté automatiquement aux seules notifications.
 */
import { computed, onMounted, ref } from 'vue';
import { useToast } from 'primevue/usetoast';
import Button from 'primevue/button';
import ColorPicker from 'primevue/colorpicker';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import { CheckIcon, EyeIcon } from '@heroicons/vue/24/outline';
import { ActionButtonVertical, Card, EmptyState, HeaderComponent, ListSkeleton } from '@components';
import { getEmailHabillageService, saveEmailHabillageService, previewEmailHabillageService } from '@requests';

const toast = useToast();

const chargement = ref(true);
const enCours = ref('');
const infos = ref({ etablissement: '', universite: '', logo: false });
const reglages = ref({ couleur: '#1a3a5c', expediteur_nom: '', reply_to: '', signature: '', mentions_legales: '', contact_rgpd: '' });
const enregistres = ref('');
const erreur = ref('');
const apercu = ref('');

const modifie = computed(() => JSON.stringify(reglages.value) !== enregistres.value);
/** ColorPicker manipule « 1a3a5c » ; l'API attend « #1a3a5c ». */
const couleurSansDiese = computed({
  get: () => reglages.value.couleur.replace(/^#/, ''),
  set: (v) => { reglages.value.couleur = `#${String(v).replace(/^#/, '')}`; }
});

const appliquer = (r) => {
  reglages.value = {
    couleur: r.couleur || '#1a3a5c',
    expediteur_nom: r.expediteur_nom || '',
    reply_to: r.reply_to || '',
    signature: r.signature || '',
    mentions_legales: r.mentions_legales || '',
    contact_rgpd: r.contact_rgpd || ''
  };
  enregistres.value = JSON.stringify(reglages.value);
};
const messageErreur = (e, defaut) => e?.response?.data?.error || defaut;

const afficherApercu = async () => {
  erreur.value = '';
  enCours.value = 'apercu';
  try {
    apercu.value = (await previewEmailHabillageService(reglages.value)).html;
  } catch (e) {
    erreur.value = messageErreur(e, 'Aperçu impossible.');
  } finally {
    enCours.value = '';
  }
};

const enregistrer = async () => {
  erreur.value = '';
  enCours.value = 'enregistrer';
  try {
    appliquer((await saveEmailHabillageService(reglages.value)).reglages);
    toast.add({ severity: 'success', summary: 'Habillage enregistré', detail: 'Il s\'applique à tous les e-mails de l\'établissement.', life: 3000 });
    await afficherApercu();
  } catch (e) {
    erreur.value = messageErreur(e, 'L\'enregistrement a échoué.');
  } finally {
    enCours.value = '';
  }
};

onMounted(async () => {
  try {
    const data = await getEmailHabillageService();
    infos.value = { etablissement: data.etablissement, universite: data.universite, logo: data.logo };
    appliquer(data.reglages);
    await afficherApercu();
  } catch {
    toast.add({ severity: 'error', summary: 'Erreur', detail: 'Impossible de charger l\'habillage des e-mails.', life: 4000 });
  } finally {
    chargement.value = false;
  }
});
</script>

<template>
  <div class="space-y-6">
    <HeaderComponent titre="Habillage des e-mails" icon="pi pi-palette" color="teal" :back-url="{ name: 'emails-configuration' }"
      description="Couleur, expéditeur, signature et mentions imposées dans tous les e-mails de l'établissement. Les modèles personnalisés ne peuvent pas les retirer.">
      <template #actions>
        <div class="flex items-center gap-2">
          <ActionButtonVertical :icon="EyeIcon" label="Aperçu" :disabled="!!enCours" @click="afficherApercu" />
          <ActionButtonVertical :icon="CheckIcon" label="Enregistrer" severity="primary" :disabled="!!enCours || !modifie" @click="enregistrer" />
        </div>
      </template>
    </HeaderComponent>

    <ListSkeleton v-if="chargement" />

    <template v-else>
      <div v-if="erreur" class="q-callout q-callout-danger text-sm whitespace-pre-line" role="alert">
        <i class="pi pi-exclamation-triangle" aria-hidden="true"></i><span>{{ erreur }}</span>
      </div>

      <div class="grid grid-cols-1 xl:grid-cols-12 gap-6">
        <div class="xl:col-span-5 space-y-6">
          <Card title="Identité" icon="pi pi-id-card" color="teal" body-class="q-form">
            <p class="q-hint !mt-0">
              En-tête : {{ infos.logo ? 'logo' : 'nom' }} de l'établissement ({{ infos.etablissement }}{{ infos.universite ? ` – ${infos.universite}` : '' }}),
              modifiables dans « Données de l'établissement ».
            </p>
            <div>
              <label for="habillage-couleur" class="q-label">Couleur principale</label>
              <div class="flex items-center gap-2">
                <ColorPicker v-model="couleurSansDiese" input-id="habillage-couleur" />
                <InputText v-model="reglages.couleur" aria-label="Couleur (format #RRGGBB)" class="w-32 font-mono" />
              </div>
              <p class="q-hint">Un département qui a sa propre couleur l'utilise pour ses e-mails.</p>
            </div>
            <div>
              <label for="habillage-expediteur" class="q-label">Nom de l'expéditeur</label>
              <InputText id="habillage-expediteur" v-model="reglages.expediteur_nom" :placeholder="infos.etablissement" class="w-full" />
            </div>
            <div>
              <label for="habillage-reply" class="q-label">Adresse de réponse</label>
              <InputText id="habillage-reply" v-model="reglages.reply_to" type="email" placeholder="(aucune : ne pas répondre)" class="w-full" />
            </div>
          </Card>

          <Card title="Signature et mentions" icon="pi pi-shield" color="teal" body-class="q-form">
            <div>
              <label for="habillage-signature" class="q-label">Signature</label>
              <Textarea id="habillage-signature" v-model="reglages.signature" rows="3" maxlength="500" auto-resize class="w-full"
                placeholder="Le service de la scolarité de l'IUT…" />
            </div>
            <div>
              <label for="habillage-mentions" class="q-label">Mentions légales</label>
              <Textarea id="habillage-mentions" v-model="reglages.mentions_legales" rows="4" maxlength="2000" auto-resize class="w-full"
                placeholder="Adresse, statut de l'établissement, confidentialité du message…" />
            </div>
            <div>
              <label for="habillage-rgpd" class="q-label">Contact pour les données personnelles</label>
              <InputText id="habillage-rgpd" v-model="reglages.contact_rgpd" placeholder="dpo@univ-….fr" class="w-full" />
              <p class="q-hint">Ajoute la mention RGPD (exercice des droits) au pied de page.</p>
            </div>
            <div class="q-callout q-callout-info">
              <i class="pi pi-info-circle" aria-hidden="true"></i>
              <span>Le lien « gérer mes préférences de notification » est ajouté automatiquement aux notifications ;
                les e-mails transactionnels (mot de passe, invitations, liens à usage unique) n'en ont pas.</span>
            </div>
          </Card>
        </div>

        <Card class="xl:col-span-7" title="Aperçu" icon="pi pi-eye" color="teal" subtitle="Une notification type, avec les réglages en cours (non enregistrés).">
          <template #header-actions>
            <Button label="Actualiser" icon="pi pi-refresh" size="small" severity="secondary" :loading="enCours === 'apercu'" @click="afficherApercu" />
          </template>
          <iframe v-if="apercu" :srcdoc="apercu" sandbox="" title="Aperçu de l'habillage" class="w-full h-[70vh] bg-white rounded-lg border border-slate-200 dark:border-slate-700"></iframe>
          <EmptyState v-else compact icon="pi pi-eye" title="Pas encore d'aperçu" />
        </Card>
      </div>
    </template>
  </div>
</template>
