<script setup>
/**
 * Formulaire public d'évaluation du stagiaire par son tuteur en entreprise (sans compte).
 * Ouvert par le lien à usage unique reçu par e-mail : /app/stage-evaluation/<jeton>.
 */
import { computed, onMounted, ref } from 'vue';
import { useRoute } from 'vue-router';
import api from '@helpers/axios';
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

onMounted(async () => {
  try {
    const { data } = await api.get(`/api/stage/evaluation-entreprise/${encodeURIComponent(jeton)}`);
    statut.value = data.statut;
    if (data.statut === 'ouverte') {
      formulaire.value = data;
      evaluateurNom.value = data.evaluateurNom || '';
      data.criteres.forEach(c => { reponses.value[c.id] = { niveau: null, commentaire: '' }; });
    }
  } catch (e) {
    statut.value = 'invalide';
  } finally {
    chargement.value = false;
  }
});

const envoyer = async () => {
  erreur.value = '';
  envoi.value = true;
  try {
    await api.post(`/api/stage/evaluation-entreprise/${encodeURIComponent(jeton)}`, {
      evaluateurNom: evaluateurNom.value,
      reponses: reponses.value,
      appreciation: appreciation.value,
      noteProposee: noteProposee.value === '' ? null : noteProposee.value
    });
    statut.value = 'soumise';
    window.scrollTo({ top: 0 });
  } catch (e) {
    erreur.value = messageErreurApi(e, 'L\'évaluation n\'a pas pu être enregistrée. Réessayez.');
  } finally {
    envoi.value = false;
  }
};
</script>

<template>
  <main class="min-h-screen bg-slate-50 dark:bg-slate-950 text-slate-800 dark:text-slate-100 px-4 py-8">
    <div class="max-w-3xl mx-auto space-y-6">
      <header>
        <h1 class="text-2xl font-black">Évaluation du stagiaire</h1>
        <p v-if="formulaire" class="text-sm text-slate-500 dark:text-slate-400 mt-1">
          {{ formulaire.etudiant }} — {{ formulaire.entreprise }} — {{ formulaire.periode }} ({{ formulaire.dates }})
        </p>
      </header>

      <p v-if="chargement" class="text-sm text-slate-400">Chargement…</p>

      <section v-else-if="statut === 'soumise'" class="p-5 rounded-2xl bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-900 text-sm">
        <h2 class="font-bold text-emerald-900 dark:text-emerald-200">Merci, votre évaluation a bien été transmise.</h2>
        <p class="mt-1 text-emerald-800 dark:text-emerald-300">Elle est désormais consultable par le tuteur universitaire du stagiaire. Vous pouvez fermer cette page.</p>
      </section>

      <section v-else-if="statut !== 'ouverte'" class="p-5 rounded-2xl bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-900 text-sm">
        <h2 class="font-bold text-amber-900 dark:text-amber-200">Ce lien n'est plus utilisable.</h2>
        <p class="mt-1 text-amber-800 dark:text-amber-300">
          Il a expiré, a déjà servi ou n'est pas valide. Contactez le tuteur universitaire du stagiaire pour recevoir un nouveau lien.
        </p>
      </section>

      <form v-else class="space-y-5" @submit.prevent="envoyer">
        <fieldset v-for="(c, i) in formulaire.criteres" :key="c.id" class="p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 space-y-3">
          <legend class="sr-only">{{ c.libelle }}</legend>
          <div>
            <h2 class="font-bold">{{ i + 1 }}. {{ c.libelle }}</h2>
            <p v-if="c.description" class="text-sm text-slate-500 dark:text-slate-400 whitespace-pre-line">{{ c.description }}</p>
          </div>
          <div class="flex flex-wrap gap-2" role="radiogroup" :aria-label="c.libelle">
            <label v-for="(libelle, niveau) in formulaire.niveaux" :key="niveau"
              class="px-3 py-2 rounded-xl border text-sm cursor-pointer"
              :class="Number(reponses[c.id].niveau) === Number(niveau) ? 'border-violet-600 bg-violet-50 dark:bg-violet-950/40 font-bold' : 'border-slate-200 dark:border-slate-700'">
              <input v-model="reponses[c.id].niveau" type="radio" :name="`critere-${c.id}`" :value="Number(niveau)" class="sr-only" />
              {{ niveau }} — {{ libelle }}
            </label>
          </div>
          <textarea v-model="reponses[c.id].commentaire" rows="2" maxlength="2000" placeholder="Commentaire (facultatif)" :aria-label="`Commentaire : ${c.libelle}`"
            class="w-full p-2 text-sm border border-slate-200 dark:border-slate-700 rounded-lg bg-white dark:bg-slate-900"></textarea>
        </fieldset>

        <div class="p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 space-y-3 text-sm">
          <label class="block">
            <span class="font-bold">Appréciation générale</span>
            <textarea v-model="appreciation" rows="4" maxlength="5000" class="mt-1 w-full p-2 border border-slate-200 dark:border-slate-700 rounded-lg bg-white dark:bg-slate-900"></textarea>
          </label>
          <div class="grid sm:grid-cols-2 gap-3">
            <label class="block">
              <span class="font-bold">Note proposée sur 20 (facultatif)</span>
              <input v-model="noteProposee" type="number" min="0" max="20" step="0.5" class="mt-1 w-full p-2 border border-slate-200 dark:border-slate-700 rounded-lg bg-white dark:bg-slate-900" />
            </label>
            <label class="block">
              <span class="font-bold">Votre nom</span>
              <input v-model="evaluateurNom" type="text" required maxlength="150" autocomplete="name" class="mt-1 w-full p-2 border border-slate-200 dark:border-slate-700 rounded-lg bg-white dark:bg-slate-900" />
            </label>
          </div>
        </div>

        <p v-if="erreur" class="text-sm text-rose-700 dark:text-rose-300 whitespace-pre-line" role="alert">{{ erreur }}</p>
        <div class="flex justify-end">
          <button type="submit" :disabled="envoi || !complet" class="px-4 py-2 rounded-xl bg-violet-600 hover:bg-violet-700 text-white font-bold disabled:opacity-50 disabled:cursor-not-allowed">
            {{ envoi ? 'Envoi…' : 'Transmettre l\'évaluation' }}
          </button>
        </div>
        <p class="text-xs text-slate-400 text-right">Une fois transmise, l'évaluation ne peut plus être modifiée.</p>
      </form>
    </div>
  </main>
</template>
