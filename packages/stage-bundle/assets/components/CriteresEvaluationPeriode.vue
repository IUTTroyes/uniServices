<script setup>
/**
 * Grille d'évaluation du stagiaire par le tuteur en entreprise, propre à une période (responsable des stages).
 * Chaque critère est noté de 1 (insuffisant) à 4 (très satisfaisant) dans le formulaire envoyé à l'entreprise.
 */
import { onMounted, ref, watch } from 'vue';
import { useToast } from 'primevue/usetoast';
import api from '@helpers/axios';
import { messageErreurApi } from '@/utils/erreurApi';

const props = defineProps({
  periodeId: { type: [Number, String], required: true },
  modifiable: { type: Boolean, default: true }
});
const toast = useToast();

const criteres = ref([]);
const chargement = ref(false);
const nouveau = ref({ libelle: '', description: '' });
const edition = ref(null); // { id, libelle, description }

const url = '/api/stage_evaluation_criteres';
const jsonld = { headers: { 'Content-Type': 'application/ld+json' } };
const patch = { headers: { 'Content-Type': 'application/merge-patch+json' } };

const charger = async () => {
  chargement.value = true;
  try {
    const { data } = await api.get(url, { params: { stagePeriode: props.periodeId, pagination: false } });
    criteres.value = Array.isArray(data) ? data : (data.member || data['hydra:member'] || []);
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Erreur', detail: messageErreurApi(error, 'Impossible de charger la grille.'), life: 4000 });
  } finally {
    chargement.value = false;
  }
};

const ajouter = async () => {
  if (!nouveau.value.libelle.trim()) return;
  try {
    await api.post(url, {
      stagePeriode: `/api/stage_periodes/${props.periodeId}`,
      libelle: nouveau.value.libelle.trim(),
      description: nouveau.value.description.trim() || null,
      ordre: criteres.value.length
    }, jsonld);
    nouveau.value = { libelle: '', description: '' };
    await charger();
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Erreur', detail: messageErreurApi(error, 'Impossible d\'ajouter le critère.'), life: 4000 });
  }
};

const enregistrer = async () => {
  const c = edition.value;
  if (!c?.libelle.trim()) return;
  try {
    await api.patch(`${url}/${c.id}`, { libelle: c.libelle.trim(), description: c.description?.trim() || null }, patch);
    edition.value = null;
    await charger();
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Erreur', detail: messageErreurApi(error, 'Impossible de modifier le critère.'), life: 4000 });
  }
};

const supprimer = async (c) => {
  if (!confirm(`Supprimer le critère « ${c.libelle} » ?`)) return;
  try {
    await api.delete(`${url}/${c.id}`);
    await charger();
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Erreur', detail: messageErreurApi(error, 'Impossible de supprimer le critère.'), life: 4000 });
  }
};

/** Échange la position de deux critères voisins. */
const deplacer = async (index, sens) => {
  const cible = index + sens;
  if (cible < 0 || cible >= criteres.value.length) return;
  const liste = [...criteres.value];
  [liste[index], liste[cible]] = [liste[cible], liste[index]];
  try {
    await Promise.all(liste.map((c, i) => (c.ordre !== i ? api.patch(`${url}/${c.id}`, { ordre: i }, patch) : null)));
    await charger();
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Erreur', detail: messageErreurApi(error, 'Impossible de réordonner la grille.'), life: 4000 });
  }
};

onMounted(charger);
watch(() => props.periodeId, charger);
</script>

<template>
  <div class="space-y-3 text-xs">
    <p class="text-slate-500 dark:text-slate-400">
      Critères notés par le tuteur en entreprise, de 1 (insuffisant) à 4 (très satisfaisant), dans le formulaire qui lui est envoyé par e-mail.
    </p>

    <p v-if="chargement" class="text-slate-400">Chargement…</p>
    <p v-else-if="criteres.length === 0" class="text-amber-700 dark:text-amber-300">
      Aucun critère : l'évaluation par l'entreprise ne peut pas encore être envoyée pour cette période.
    </p>

    <ol v-else class="space-y-2">
      <li v-for="(c, i) in criteres" :key="c.id" class="p-2.5 rounded-xl border border-slate-100 dark:border-slate-800 bg-slate-50 dark:bg-slate-900/40">
        <div v-if="edition?.id === c.id" class="space-y-2">
          <input v-model="edition.libelle" maxlength="150" aria-label="Libellé du critère" class="w-full p-2 border border-slate-200 dark:border-slate-700 rounded-lg bg-white dark:bg-slate-900" />
          <textarea v-model="edition.description" rows="2" aria-label="Description du critère" class="w-full p-2 border border-slate-200 dark:border-slate-700 rounded-lg bg-white dark:bg-slate-900"></textarea>
          <div class="flex gap-2 justify-end">
            <button type="button" class="px-2 py-1 rounded-lg border border-slate-200 dark:border-slate-700" @click="edition = null">Annuler</button>
            <button type="button" class="px-2 py-1 rounded-lg bg-violet-600 text-white font-bold" @click="enregistrer">Enregistrer</button>
          </div>
        </div>
        <div v-else class="flex items-start justify-between gap-2">
          <div class="min-w-0">
            <span class="font-bold text-slate-800 dark:text-slate-200">{{ i + 1 }}. {{ c.libelle }}</span>
            <p v-if="c.description" class="text-slate-500 dark:text-slate-400 mt-0.5 whitespace-pre-line">{{ c.description }}</p>
          </div>
          <div v-if="modifiable" class="flex gap-1 shrink-0">
            <button type="button" :disabled="i === 0" class="px-1.5 rounded border border-slate-200 dark:border-slate-700 disabled:opacity-30" title="Monter" aria-label="Monter" @click="deplacer(i, -1)">↑</button>
            <button type="button" :disabled="i === criteres.length - 1" class="px-1.5 rounded border border-slate-200 dark:border-slate-700 disabled:opacity-30" title="Descendre" aria-label="Descendre" @click="deplacer(i, 1)">↓</button>
            <button type="button" class="px-1.5 rounded border border-slate-200 dark:border-slate-700" @click="edition = { id: c.id, libelle: c.libelle, description: c.description || '' }">Modifier</button>
            <button type="button" class="px-1.5 rounded border border-rose-200 text-rose-600" @click="supprimer(c)">Supprimer</button>
          </div>
        </div>
      </li>
    </ol>

    <form v-if="modifiable" class="space-y-2 pt-2 border-t border-slate-100 dark:border-slate-800" @submit.prevent="ajouter">
      <input v-model="nouveau.libelle" maxlength="150" placeholder="Nouveau critère (ex. : autonomie, qualité du travail…)" aria-label="Libellé du nouveau critère"
        class="w-full p-2 border border-slate-200 dark:border-slate-700 rounded-lg bg-white dark:bg-slate-900" />
      <textarea v-model="nouveau.description" rows="2" placeholder="Précisions pour l'évaluateur (facultatif)" aria-label="Description du nouveau critère"
        class="w-full p-2 border border-slate-200 dark:border-slate-700 rounded-lg bg-white dark:bg-slate-900"></textarea>
      <div class="flex justify-end">
        <button type="submit" :disabled="!nouveau.libelle.trim()" class="px-3 py-1.5 rounded-xl bg-violet-600 hover:bg-violet-700 text-white font-bold disabled:opacity-50">Ajouter le critère</button>
      </div>
    </form>
  </div>
</template>
