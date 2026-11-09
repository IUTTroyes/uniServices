<script setup>
/**
 * Éditeur d'un e-mail (super administrateur), au niveau de l'établissement ou d'un département.
 * On part du modèle réellement envoyé aujourd'hui ; le serveur vérifie le modèle avant de l'enregistrer
 * (sandbox Twig : variables déclarées, filtres et balises autorisés uniquement).
 */
import { computed, onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useToast } from 'primevue/usetoast';
import { useConfirm } from 'primevue/useconfirm';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Tag from 'primevue/tag';
import { CheckIcon, EyeIcon, PaperAirplaneIcon, ArrowUturnLeftIcon } from '@heroicons/vue/24/outline';
import { ActionButtonVertical, Card, EmptyState, HeaderComponent, ListSkeleton } from '@components';
import {
  getEmailTemplateService,
  saveEmailTemplateService,
  deleteEmailTemplateService,
  previewEmailTemplateService,
  sendTestEmailTemplateService
} from '@requests';

const route = useRoute();
const router = useRouter();
const toast = useToast();
const confirm = useConfirm();

const emailKey = computed(() => decodeURIComponent(String(route.params.key)));
const departementId = computed(() => (route.query.departement ? Number(route.query.departement) : null));

const chargement = ref(true);
const enCours = ref('');            // action en cours : enregistrer | apercu | test | reinitialiser
const modele = ref(null);           // réponse de l'API
const subject = ref('');
const bodyHtml = ref('');
const enregistre = ref({ subject: '', bodyHtml: '' });
const erreur = ref('');
const apercu = ref(null);           // { subject, html }
const zoneCorps = ref(null);

const PERSONNALISATIONS = {
  departement: { libelle: 'Personnalisé pour ce département', severite: 'success' },
  etablissement: { libelle: 'Personnalisé pour l\'établissement', severite: 'success' },
  parent: { libelle: 'Hérite du modèle générique personnalisé', severite: 'info' },
  defaut: { libelle: 'Modèle par défaut de l\'application', severite: 'secondary' }
};
const etat = computed(() => PERSONNALISATIONS[modele.value?.personnalisation] || PERSONNALISATIONS.defaut);
const niveau = computed(() => (modele.value?.departementLibelle ? `le département ${modele.value.departementLibelle}` : 'l\'établissement'));
const modifie = computed(() => subject.value !== enregistre.value.subject || bodyHtml.value !== enregistre.value.bodyHtml);
const variables = computed(() => Object.entries(modele.value?.availableVariables || {}));
const FILTRES = ['default', 'upper', 'lower', 'trim', 'date', 'nl2br', 'length', 'e', 'raw'];

const charger = async () => {
  chargement.value = true;
  try {
    modele.value = await getEmailTemplateService(emailKey.value, departementId.value);
    subject.value = modele.value.subject;
    // Point de départ : la personnalisation de ce niveau, sinon le modèle par défaut de l'application.
    bodyHtml.value = modele.value.bodyHtml ?? modele.value.defaultBodyHtml ?? '<p>Bonjour {{ destinataire_nom }},</p>';
    enregistre.value = { subject: modele.value.isCustomized ? subject.value : '', bodyHtml: modele.value.isCustomized ? bodyHtml.value : '' };
  } catch {
    toast.add({ severity: 'error', summary: 'Erreur', detail: 'Impossible de charger ce modèle.', life: 4000 });
  } finally {
    chargement.value = false;
  }
};

const saisie = () => ({ emailKey: emailKey.value, subject: subject.value, bodyHtml: bodyHtml.value });
const messageErreur = (e, defaut) => e?.response?.data?.error || defaut;

const enregistrer = async () => {
  erreur.value = '';
  enCours.value = 'enregistrer';
  try {
    const resultat = await saveEmailTemplateService({ ...saisie(), departementId: departementId.value });
    modele.value = { ...modele.value, id: resultat.id, isCustomized: true, personnalisation: departementId.value ? 'departement' : 'etablissement' };
    enregistre.value = { subject: subject.value, bodyHtml: bodyHtml.value };
    toast.add({ severity: 'success', summary: 'Modèle enregistré', detail: `Il s'applique désormais pour ${niveau.value}.`, life: 3000 });
  } catch (e) {
    erreur.value = messageErreur(e, 'L\'enregistrement a échoué.');
  } finally {
    enCours.value = '';
  }
};

const afficherApercu = async () => {
  erreur.value = '';
  enCours.value = 'apercu';
  try {
    apercu.value = await previewEmailTemplateService(saisie());
  } catch (e) {
    erreur.value = messageErreur(e, 'Aperçu impossible.');
  } finally {
    enCours.value = '';
  }
};

const envoyerTest = async () => {
  erreur.value = '';
  enCours.value = 'test';
  try {
    const { envoyeA } = await sendTestEmailTemplateService(saisie());
    toast.add({ severity: 'success', summary: 'E-mail de test envoyé', detail: `Envoyé à ${envoyeA}.`, life: 4000 });
  } catch (e) {
    erreur.value = messageErreur(e, 'L\'e-mail de test n\'a pas pu être envoyé.');
  } finally {
    enCours.value = '';
  }
};

const partirDuDefaut = () => {
  subject.value = modele.value.defaultSubject;
  bodyHtml.value = modele.value.defaultBodyHtml || '';
};

const reinitialiser = () => confirm.require({
  header: 'Réinitialiser le modèle',
  message: `Supprimer la personnalisation pour ${niveau.value} ? Le niveau supérieur (établissement, modèle générique ou modèle par défaut) reprendra la main.`,
  icon: 'pi pi-exclamation-triangle',
  rejectProps: { label: 'Annuler', severity: 'secondary', outlined: true },
  acceptProps: { label: 'Réinitialiser', severity: 'danger' },
  accept: async () => {
    enCours.value = 'reinitialiser';
    try {
      await deleteEmailTemplateService(modele.value.id);
      toast.add({ severity: 'info', summary: 'Modèle réinitialisé', detail: 'La personnalisation a été supprimée.', life: 3000 });
      await charger();
    } catch (e) {
      erreur.value = messageErreur(e, 'La réinitialisation a échoué.');
    } finally {
      enCours.value = '';
    }
  }
});

/** Insère un fragment à la position du curseur dans le corps. */
const inserer = (fragment) => {
  const el = zoneCorps.value;
  const debut = el?.selectionStart ?? bodyHtml.value.length;
  const fin = el?.selectionEnd ?? debut;
  bodyHtml.value = bodyHtml.value.slice(0, debut) + fragment + bodyHtml.value.slice(fin);
  requestAnimationFrame(() => {
    el?.focus();
    if (el) el.selectionStart = el.selectionEnd = debut + fragment.length;
  });
};
const variable = (nom) => `{{ ${nom} }}`;
const FRAGMENTS = [
  { libelle: 'Paragraphe', code: '\n<p></p>' },
  { libelle: 'Bouton', code: '\n<a href="{{ urlNotification }}" class="btn">Ouvrir</a>' },
  { libelle: 'Encadré', code: '\n<div class="alert">Message important</div>' },
  { libelle: 'Séparateur', code: '\n<hr class="divider">' },
  { libelle: 'Condition', code: '\n{% if variable %}\n  <p></p>\n{% endif %}' }
];

const retour = () => router.push({ name: 'emails-configuration', query: departementId.value ? { departement: departementId.value } : {} });

onMounted(charger);
</script>

<template>
  <div class="space-y-6">
    <HeaderComponent :titre="modele?.label || 'Modèle de mail'" icon="pi pi-envelope" color="teal" :back-url="{ name: 'emails-configuration', query: departementId ? { departement: departementId } : {} }"
      :description="modele ? `${modele.description || modele.emailKey} — personnalisation pour ${niveau}.` : 'Chargement…'">
      <template #actions>
        <div class="flex items-center gap-2">
          <ActionButtonVertical :icon="EyeIcon" label="Aperçu" :disabled="!!enCours" @click="afficherApercu" />
          <ActionButtonVertical :icon="PaperAirplaneIcon" label="M'envoyer un test" :disabled="!!enCours" @click="envoyerTest" />
          <ActionButtonVertical v-if="modele?.isCustomized" :icon="ArrowUturnLeftIcon" label="Réinitialiser" severity="danger" :disabled="!!enCours" @click="reinitialiser" />
          <ActionButtonVertical :icon="CheckIcon" label="Enregistrer" severity="primary" :disabled="!!enCours || (!modifie && modele?.isCustomized)" @click="enregistrer" />
        </div>
      </template>
    </HeaderComponent>

    <ListSkeleton v-if="chargement" />

    <template v-else-if="modele">
      <div class="flex flex-wrap items-center gap-2">
        <Tag :value="etat.libelle" :severity="etat.severite" />
        <Tag v-if="modele.categorie === 'notification'" :value="modele.desactivable ? 'Notification — désactivable par le destinataire' : 'Notification — ne peut pas être désactivée'" :severity="modele.desactivable ? 'secondary' : 'warn'" />
        <Tag v-else value="Transactionnel — toujours envoyé" severity="secondary" />
        <span class="text-sm text-slate-500 font-mono">{{ modele.emailKey }}</span>
        <span v-if="modele.parentKey && !modele.isCustomized" class="q-hint !mt-0">
          Tant qu'il n'est pas personnalisé, ce message reprend le modèle générique « {{ modele.parentKey }} ».
        </span>
      </div>

      <div v-if="erreur" class="q-callout q-callout-danger text-sm whitespace-pre-line" role="alert">
        <i class="pi pi-exclamation-triangle" aria-hidden="true"></i><span>{{ erreur }}</span>
      </div>

      <div class="grid grid-cols-1 xl:grid-cols-12 gap-6">
        <Card class="xl:col-span-8" title="Message" icon="pi pi-pencil" color="teal" body-class="q-form">
          <template #header-actions>
            <Button label="Repartir du modèle par défaut" icon="pi pi-replay" size="small" severity="secondary" text @click="partirDuDefaut" />
          </template>
          <div>
            <label for="email-objet" class="q-label q-label-required">Objet</label>
            <InputText id="email-objet" v-model="subject" class="w-full" />
          </div>
          <div>
            <label for="email-corps" class="q-label q-label-required">Corps (HTML, inséré dans la mise en page commune)</label>
            <div class="flex flex-wrap gap-1.5 mb-2">
              <Button v-for="f in FRAGMENTS" :key="f.libelle" :label="f.libelle" size="small" severity="secondary" outlined @click="inserer(f.code)" />
            </div>
            <textarea id="email-corps" ref="zoneCorps" v-model="bodyHtml" rows="18" spellcheck="false" class="q-input font-mono !text-xs leading-relaxed"></textarea>
          </div>
        </Card>

        <div class="xl:col-span-4 space-y-6">
          <Card title="Variables" icon="pi pi-code" color="teal" subtitle="Cliquez pour insérer à la position du curseur.">
            <EmptyState v-if="variables.length === 0" compact title="Aucune variable" />
            <ul v-else class="space-y-2">
              <li v-for="[nom, description] in variables" :key="nom">
                <Button :label="variable(nom)" size="small" severity="secondary" outlined class="!font-mono" @click="inserer(variable(nom))" />
                <p class="q-hint !mt-0.5">{{ description }}</p>
              </li>
            </ul>
          </Card>

          <Card title="Syntaxe autorisée" icon="pi pi-shield" color="teal">
            <p class="text-sm text-slate-600 dark:text-slate-300">
              Balises <code>if</code>, <code>for</code>, <code>set</code>. Filtres :
              <code v-for="f in FILTRES" :key="f" class="mr-1">{{ f }}</code>.
              Les valeurs sont échappées automatiquement ; les liens relatifs (« /app/… ») deviennent absolus à l'envoi.
              L'en-tête, la signature et les mentions du pied de page sont ajoutés automatiquement (voir « Habillage »).
            </p>
          </Card>
        </div>
      </div>

      <Card title="Aperçu" icon="pi pi-eye" color="teal" :subtitle="apercu ? `Objet : ${apercu.subject}` : 'Avec des valeurs d\'exemple [nom_variable].'">
        <iframe v-if="apercu" :srcdoc="apercu.html" sandbox="" title="Aperçu de l'e-mail" class="w-full h-[60vh] bg-white rounded-lg border border-slate-200 dark:border-slate-700"></iframe>
        <EmptyState v-else compact icon="pi pi-eye" title="Pas encore d'aperçu" description="Cliquez sur « Aperçu » pour voir le message mis en page." />
      </Card>
    </template>

    <EmptyState v-else icon="pi pi-envelope" title="Modèle introuvable" action-label="Retour à la liste" @action="retour" />
  </div>
</template>
