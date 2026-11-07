<script setup>
import { showWarn } from '@helpers/toast';
import { formatDateFr } from '@helpers/date';
import { computed, onMounted, ref, watch } from 'vue';
import Button from 'primevue/button';
import Textarea from 'primevue/textarea';
import Select from 'primevue/select';
import Tag from 'primevue/tag';
import { commentaireService } from '../../requests/evaluationService';
import { messageErreur } from '../../requests/traceService';

/** Fil de discussion d'une trace entre l'étudiant et ses évaluateurs. */
const props = defineProps({
  traceUuid: { type: String, required: true },
  /** Rattachements de la trace, proposés comme ancrage d'un commentaire. */
  rattachements: { type: Array, default: () => [] },
});

const commentaires = ref([]);
const nouveau = ref('');
const ancrage = ref(null);
const reponseA = ref(null);
const reponse = ref('');
const envoi = ref(false);
const chargement = ref(true);

const charger = async () => {
  try {
    const { data } = await commentaireService.lister(props.traceUuid);
    commentaires.value = data;
  } catch (e) {
    showWarn(messageErreur(e), 'Commentaires indisponibles', 5000);
  } finally {
    chargement.value = false;
  }
};
onMounted(charger);
watch(() => props.traceUuid, charger);

const racines = computed(() => commentaires.value.filter((c) => !c.parent));
const reponsesDe = (uuid) => commentaires.value.filter((c) => c.parent === uuid);
const options = computed(() => props.rattachements.map((r) => ({ id: r.id, label: `${r.code ? `${r.code} · ` : ''}${r.libelle}`.slice(0, 80) })));
const libelleAncrage = (id) => options.value.find((o) => o.id === id)?.label ?? null;

const envoyer = async (contenu, extra = {}) => {
  if (!contenu.trim()) return false;
  envoi.value = true;
  try {
    const { data } = await commentaireService.ajouter(props.traceUuid, { contenu, ...extra });
    commentaires.value.push(data);
    return true;
  } catch (e) {
    showWarn(messageErreur(e), 'Commentaire non envoyé', 6000);
    return false;
  } finally {
    envoi.value = false;
  }
};

const publier = async () => {
  if (await envoyer(nouveau.value, { rattachement: ancrage.value })) {
    nouveau.value = '';
    ancrage.value = null;
  }
};
const repondre = async (racine) => {
  if (await envoyer(reponse.value, { parent: racine.uuid })) {
    reponse.value = '';
    reponseA.value = null;
  }
};

const date = (iso) => formatDateFr(iso, 'jourMoisHeure', '');
</script>

<template>
  <section class="flex flex-col gap-4" aria-label="Discussion">
    <h3 class="text-base font-semibold m-0">Discussion</h3>
    <p v-if="!chargement && racines.length === 0" class="text-sm text-muted-color m-0">Aucun message pour l'instant.</p>

    <div v-for="c in racines" :key="c.uuid" class="flex flex-col gap-2">
      <article class="rounded-lg p-3 flex flex-col gap-1 text-sm" :class="c.auteur.type === 'etudiant' ? 'bg-surface-100' : 'bg-emerald-50 dark:bg-emerald-950/30'">
        <div class="flex flex-wrap items-center gap-2">
          <strong>{{ c.auteur.moi ? 'Moi' : c.auteur.nom }}</strong>
          <Tag :value="c.auteur.type === 'etudiant' ? 'Étudiant' : 'Enseignant'" :severity="c.auteur.type === 'etudiant' ? 'secondary' : 'success'" />
          <span class="text-muted-color">{{ date(c.creeLe) }}</span>
          <Tag v-if="libelleAncrage(c.rattachementId)" :value="libelleAncrage(c.rattachementId)" severity="info" icon="pi pi-link" />
        </div>
        <p class="m-0 whitespace-pre-line">{{ c.contenu }}</p>
      </article>

      <article
        v-for="r in reponsesDe(c.uuid)"
        :key="r.uuid"
        class="rounded-lg p-3 ml-6 flex flex-col gap-1 text-sm"
        :class="r.auteur.type === 'etudiant' ? 'bg-surface-100' : 'bg-emerald-50 dark:bg-emerald-950/30'"
      >
        <div class="flex flex-wrap items-center gap-2">
          <strong>{{ r.auteur.moi ? 'Moi' : r.auteur.nom }}</strong>
          <Tag :value="r.auteur.type === 'etudiant' ? 'Étudiant' : 'Enseignant'" :severity="r.auteur.type === 'etudiant' ? 'secondary' : 'success'" />
          <span class="text-muted-color">{{ date(r.creeLe) }}</span>
        </div>
        <p class="m-0 whitespace-pre-line">{{ r.contenu }}</p>
      </article>

      <div class="ml-6">
        <div v-if="reponseA === c.uuid" class="flex flex-col gap-2">
          <Textarea v-model="reponse" rows="2" auto-resize placeholder="Votre réponse" />
          <div class="flex justify-end gap-2">
            <Button label="Annuler" severity="secondary" text size="small" @click="reponseA = null" />
            <Button label="Répondre" icon="pi pi-send" size="small" :disabled="!reponse.trim()" :loading="envoi" @click="repondre(c)" />
          </div>
        </div>
        <Button v-else label="Répondre" icon="pi pi-reply" text size="small" @click="reponseA = c.uuid; reponse = ''" />
      </div>
    </div>

    <div class="flex flex-col gap-2">
      <Select
        v-if="options.length"
        v-model="ancrage"
        :options="options"
        option-label="label"
        option-value="id"
        show-clear
        placeholder="À propos de… (facultatif)"
        aria-label="Ancrer le commentaire sur un rattachement"
      />
      <Textarea v-model="nouveau" rows="3" auto-resize placeholder="Écrire un message" aria-label="Nouveau message" />
      <Button label="Publier" icon="pi pi-send" class="self-end" :disabled="!nouveau.trim()" :loading="envoi" @click="publier" />
    </div>
  </section>
</template>
