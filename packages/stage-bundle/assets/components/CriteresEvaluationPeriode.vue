<script setup>
/**
 * Grille d'évaluation du stagiaire par le tuteur en entreprise, propre à une période (responsable des stages).
 * Chaque critère est noté de 1 (insuffisant) à 4 (très satisfaisant) dans le formulaire envoyé à l'entreprise.
 */
import { onMounted, ref, watch } from 'vue';
import { useToast } from 'primevue/usetoast';
import Button from 'primevue/button';
import { EmptyState, ListSkeleton } from '@components';
import {
  getCriteresEvaluationService,
  createCritereEvaluationService,
  updateCritereEvaluationService,
  deleteCritereEvaluationService
} from '@/requests/stage_service';
import { useConfirmation } from '@/composables/useConfirmation';
import { messageErreurApi } from '@/utils/erreurApi';

const props = defineProps({
  periodeId: { type: [Number, String], required: true },
  modifiable: { type: Boolean, default: true }
});
const toast = useToast();
const { confirmer } = useConfirmation();

const criteres = ref([]);
const chargement = ref(false);
const nouveau = ref({ libelle: '', description: '' });
const edition = ref(null); // { id, libelle, description }

const erreur = (error, defaut) => toast.add({ severity: 'error', summary: 'Erreur', detail: messageErreurApi(error, defaut), life: 4000 });

const charger = async () => {
  chargement.value = true;
  try {
    criteres.value = await getCriteresEvaluationService(props.periodeId);
  } catch (error) {
    erreur(error, 'Impossible de charger la grille.');
  } finally {
    chargement.value = false;
  }
};

const ajouter = async () => {
  if (!nouveau.value.libelle.trim()) return;
  try {
    await createCritereEvaluationService(props.periodeId, {
      libelle: nouveau.value.libelle.trim(),
      description: nouveau.value.description.trim() || null,
      ordre: criteres.value.length
    });
    nouveau.value = { libelle: '', description: '' };
    await charger();
  } catch (error) {
    erreur(error, 'Impossible d\'ajouter le critère.');
  }
};

const enregistrer = async () => {
  const c = edition.value;
  if (!c?.libelle.trim()) return;
  try {
    await updateCritereEvaluationService(c.id, { libelle: c.libelle.trim(), description: c.description?.trim() || null });
    edition.value = null;
    await charger();
  } catch (error) {
    erreur(error, 'Impossible de modifier le critère.');
  }
};

const supprimer = async (c) => {
  if (!await confirmer({ message: `Supprimer le critère « ${c.libelle} » ?`, titre: 'Supprimer le critère', libelle: 'Supprimer', danger: true })) return;
  try {
    await deleteCritereEvaluationService(c.id);
    await charger();
  } catch (error) {
    erreur(error, 'Impossible de supprimer le critère.');
  }
};

/** Échange la position de deux critères voisins. */
const deplacer = async (index, sens) => {
  const cible = index + sens;
  if (cible < 0 || cible >= criteres.value.length) return;
  const liste = [...criteres.value];
  [liste[index], liste[cible]] = [liste[cible], liste[index]];
  try {
    await Promise.all(liste.map((c, i) => (c.ordre !== i ? updateCritereEvaluationService(c.id, { ordre: i }) : null)));
    await charger();
  } catch (error) {
    erreur(error, 'Impossible de réordonner la grille.');
  }
};

onMounted(charger);
watch(() => props.periodeId, charger);
</script>

<template>
  <div class="space-y-3 text-sm">
    <p class="q-hint">
      Critères notés par le tuteur en entreprise, de 1 (insuffisant) à 4 (très satisfaisant), dans le formulaire qui lui est envoyé par e-mail.
    </p>

    <ListSkeleton v-if="chargement" />
    <EmptyState v-else-if="criteres.length === 0" compact color="amber" icon="pi pi-list-check" title="Aucun critère"
      description="L'évaluation par l'entreprise ne pourra pas être envoyée tant que la grille est vide." />

    <ol v-else class="space-y-2">
      <li v-for="(c, i) in criteres" :key="c.id" class="q-section !p-3 !space-y-2">
        <form v-if="edition?.id === c.id" class="space-y-2" @submit.prevent="enregistrer">
          <input v-model="edition.libelle" maxlength="150" aria-label="Libellé du critère" class="q-input" />
          <textarea v-model="edition.description" rows="2" aria-label="Description du critère" class="q-input"></textarea>
          <div class="flex gap-2 justify-end">
            <Button type="button" label="Annuler" severity="secondary" text size="small" @click="edition = null" />
            <Button type="submit" label="Enregistrer" icon="pi pi-check" size="small" />
          </div>
        </form>
        <div v-else class="flex items-start justify-between gap-2">
          <div class="min-w-0">
            <span class="font-semibold text-slate-800 dark:text-slate-200">{{ i + 1 }}. {{ c.libelle }}</span>
            <p v-if="c.description" class="q-hint whitespace-pre-line">{{ c.description }}</p>
          </div>
          <div v-if="modifiable" class="flex gap-1 shrink-0">
            <Button icon="pi pi-arrow-up" text rounded size="small" severity="secondary" :disabled="i === 0" aria-label="Monter" v-tooltip.top="'Monter'" @click="deplacer(i, -1)" />
            <Button icon="pi pi-arrow-down" text rounded size="small" severity="secondary" :disabled="i === criteres.length - 1" aria-label="Descendre" v-tooltip.top="'Descendre'" @click="deplacer(i, 1)" />
            <Button icon="pi pi-pencil" text rounded size="small" severity="secondary" aria-label="Modifier" v-tooltip.top="'Modifier'" @click="edition = { id: c.id, libelle: c.libelle, description: c.description || '' }" />
            <Button icon="pi pi-trash" text rounded size="small" severity="danger" aria-label="Supprimer" v-tooltip.top="'Supprimer'" @click="supprimer(c)" />
          </div>
        </div>
      </li>
    </ol>

    <form v-if="modifiable" class="space-y-2 pt-3 border-t border-slate-100 dark:border-slate-800" @submit.prevent="ajouter">
      <label for="critere-nouveau" class="q-label">Nouveau critère</label>
      <input id="critere-nouveau" v-model="nouveau.libelle" maxlength="150" placeholder="Ex. : autonomie, qualité du travail…" class="q-input" />
      <textarea v-model="nouveau.description" rows="2" placeholder="Précisions pour l'évaluateur (facultatif)" aria-label="Description du nouveau critère" class="q-input"></textarea>
      <div class="flex justify-end">
        <Button type="submit" label="Ajouter le critère" icon="pi pi-plus" size="small" :disabled="!nouveau.libelle.trim()" />
      </div>
    </form>
  </div>
</template>
