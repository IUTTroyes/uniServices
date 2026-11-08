<script setup>
/**
 * Formulaire public d'évaluation du stagiaire par son tuteur en entreprise (sans compte).
 * Ouvert par le lien à usage unique reçu par e-mail : /app/stage-evaluation/<jeton>.
 */
import { computed, onMounted, ref } from 'vue';
import { useRoute } from 'vue-router';
import Button from 'primevue/button';
import { Card, HeaderComponent, ListSkeleton } from '@components';
import { getFormulaireEvaluationService, soumettreEvaluationService } from '@/requests/stage_service';
import { messageErreurApi } from '@/utils/erreurApi';

const route = useRoute();
const jeton = String(route.params.jeton || '');

const chargement = ref(true);
const formulaire = ref(null);   // réponse de l'API
const statut = ref('');         // ouverte | soumise | expiree | invalide
const envoi = ref(false);
const erreur = ref('');

const reponses = ref({});       // critereId -> { niveau, commentaire }
const evaluateurNom = ref('');
const appreciation = ref('');
const noteProposee = ref('');

const complet = computed(() => formulaire.value?.criteres?.every(c => reponses.value[c.id]?.niveau) && evaluateurNom.value.trim() !== '');
const description = computed(() => (formulaire.value
  ? `${formulaire.value.etudiant} — ${formulaire.value.entreprise} — ${formulaire.value.periode} (${formulaire.value.dates})`
  : 'Formulaire destiné au tuteur en entreprise du stagiaire.'));

onMounted(async () => {
  try {
    const data = await getFormulaireEvaluationService(jeton);
    statut.value = data.statut;
    if (data.statut === 'ouverte') {
      formulaire.value = data;
      evaluateurNom.value = data.evaluateurNom || '';
      data.criteres.forEach(c => { reponses.value[c.id] = { niveau: null, commentaire: '' }; });
    }
  } catch {
    statut.value = 'invalide';
  } finally {
    chargement.value = false;
  }
});

const envoyer = async () => {
  erreur.value = '';
  envoi.value = true;
  try {
    await soumettreEvaluationService(jeton, {
      evaluateurNom: evaluateurNom.value,
      reponses: reponses.value,
      appreciation: appreciation.value,
      noteProposee: noteProposee.value === '' ? null : noteProposee.value
    });
    statut.value = 'soumise';
  } catch (e) {
    erreur.value = messageErreurApi(e, 'L\'évaluation n\'a pas pu être enregistrée. Réessayez.');
  } finally {
    envoi.value = false;
  }
};
</script>

<template>
  <main class="min-h-screen bg-slate-50 dark:bg-slate-950 px-4 py-8">
    <div class="max-w-3xl mx-auto space-y-6">
      <HeaderComponent titre="Évaluation du stagiaire" :description="description" icon="pi pi-star" color="violet" :show-back="false" />

      <ListSkeleton v-if="chargement" />

      <div v-else-if="statut === 'soumise'" class="q-callout q-callout-success text-sm" role="status">
        <i class="pi pi-check-circle" aria-hidden="true"></i>
        <div>
          <strong class="block">Merci, votre évaluation a bien été transmise.</strong>
          Elle est désormais consultable par le tuteur universitaire du stagiaire. Vous pouvez fermer cette page.
        </div>
      </div>

      <div v-else-if="statut !== 'ouverte'" class="q-callout q-callout-warning text-sm" role="alert">
        <i class="pi pi-exclamation-triangle" aria-hidden="true"></i>
        <div>
          <strong class="block">Ce lien n'est plus utilisable.</strong>
          Il a expiré, a déjà servi ou n'est pas valide. Contactez le tuteur universitaire du stagiaire pour recevoir un nouveau lien.
        </div>
      </div>

      <form v-else class="space-y-5" @submit.prevent="envoyer">
        <Card v-for="(c, i) in formulaire.criteres" :key="c.id" :title="`${i + 1}. ${c.libelle}`" :subtitle="c.description || ''" body-class="space-y-3">
          <fieldset>
            <legend class="sr-only">{{ c.libelle }}</legend>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
              <label v-for="(libelle, niveau) in formulaire.niveaux" :key="niveau" class="q-choice"
                :class="{ 'q-choice-active': Number(reponses[c.id].niveau) === Number(niveau) }">
                <input v-model="reponses[c.id].niveau" type="radio" :name="`critere-${c.id}`" :value="Number(niveau)" class="q-check mt-0.5" />
                <span>
                  <span class="q-choice-title">{{ niveau }}</span>
                  <span class="q-choice-desc block">{{ libelle }}</span>
                </span>
              </label>
            </div>
          </fieldset>
          <textarea v-model="reponses[c.id].commentaire" rows="2" maxlength="2000" placeholder="Commentaire (facultatif)"
            :aria-label="`Commentaire : ${c.libelle}`" class="q-input"></textarea>
        </Card>

        <Card title="Appréciation générale" body-class="q-form">
          <div>
            <label for="evaluation-appreciation" class="q-label">Appréciation</label>
            <textarea id="evaluation-appreciation" v-model="appreciation" rows="4" maxlength="5000" class="q-input"></textarea>
          </div>
          <div class="grid sm:grid-cols-2 gap-3">
            <div>
              <label for="evaluation-note" class="q-label">Note proposée sur 20</label>
              <input id="evaluation-note" v-model="noteProposee" type="number" min="0" max="20" step="0.5" class="q-input" />
              <p class="q-hint">Facultatif.</p>
            </div>
            <div>
              <label for="evaluation-nom" class="q-label q-label-required">Votre nom</label>
              <input id="evaluation-nom" v-model="evaluateurNom" type="text" required maxlength="150" autocomplete="name" class="q-input" />
            </div>
          </div>
        </Card>

        <div v-if="erreur" class="q-callout q-callout-danger text-sm whitespace-pre-line" role="alert">{{ erreur }}</div>
        <div class="q-actions">
          <span class="q-hint !mt-0 mr-auto">Une fois transmise, l'évaluation ne peut plus être modifiée.</span>
          <Button type="submit" label="Transmettre l'évaluation" icon="pi pi-send" :loading="envoi" :disabled="!complet" />
        </div>
      </form>
    </div>
  </main>
</template>
