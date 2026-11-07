<script setup>
import { computed, ref, watch } from 'vue';
import { useToast } from 'primevue/usetoast';
import Drawer from 'primevue/drawer';
import Button from 'primevue/button';
import Textarea from 'primevue/textarea';
import Tag from 'primevue/tag';
import Message from 'primevue/message';
import ProgressBar from 'primevue/progressbar';
import GrilleSaisie from './GrilleSaisie.vue';
import RetourEnseignant from '../evaluation/RetourEnseignant.vue';
import autoEvaluationService, { pourcentage } from '../../requests/autoEvaluationService';
import { messageErreur } from '../../requests/traceService';
import { couleurCompetence, texteSurCompetence } from '../traces/options';

/**
 * Panneau latéral d'auto-évaluation. `cible` : { rattachement } ou { scolarite, type, id }.
 * Ouvre (ou crée) l'auto-évaluation correspondante.
 */
const props = defineProps({
  visible: { type: Boolean, default: false },
  cible: { type: Object, default: null },
  declarationIa: { type: Object, default: null },
});
const emit = defineEmits(['update:visible', 'change']);

const toast = useToast();
const auto = ref(null);
const reponses = ref({});
const reflexion = ref('');
const chargement = ref(false);
const occupe = ref(false);
const erreur = ref(null);
const modifie = ref(false);

const appliquer = (data) => {
  auto.value = data;
  reponses.value = Object.fromEntries(data.reponses.map((r) => [r.critere, r]));
  reflexion.value = data.reflexion ?? '';
  modifie.value = false;
};

watch(() => [props.visible, props.cible], async ([visible]) => {
  if (!visible || !props.cible) return;
  chargement.value = true;
  erreur.value = null;
  auto.value = null;
  try {
    const { data } = await autoEvaluationService.ouvrir(props.cible);
    appliquer(data);
  } catch (e) {
    erreur.value = messageErreur(e, 'Auto-évaluation indisponible.');
  } finally {
    chargement.value = false;
  }
}, { immediate: true });

const surReponses = (v) => { reponses.value = v; modifie.value = true; };

const executer = async (fn, succes) => {
  occupe.value = true;
  try {
    const { data } = await fn();
    appliquer(data);
    emit('change');
    if (succes) toast.add({ severity: 'success', summary: succes, life: 3000 });
    return true;
  } catch (e) {
    toast.add({ severity: 'warn', summary: 'Action impossible', detail: messageErreur(e), life: 7000 });
    return false;
  } finally {
    occupe.value = false;
  }
};

const enregistrer = () => executer(
  () => autoEvaluationService.enregistrer(auto.value.uuid, { reflexion: reflexion.value, reponses: Object.values(reponses.value) }),
  'Auto-évaluation enregistrée',
);

const soumettre = async () => {
  if (!(await executer(() => autoEvaluationService.enregistrer(auto.value.uuid, { reflexion: reflexion.value, reponses: Object.values(reponses.value) })))) return;
  await executer(() => autoEvaluationService.soumettre(auto.value.uuid), 'Auto-évaluation soumise');
};

const reprendre = () => executer(() => autoEvaluationService.reprendre(auto.value.uuid), 'Vous pouvez à nouveau modifier votre auto-évaluation');

const fermer = () => emit('update:visible', false);
const score = computed(() => pourcentage(auto.value?.score));
</script>

<template>
  <Drawer
    :visible="visible"
    position="right"
    class="!w-full md:!w-[40rem]"
    :header="auto?.bilan ? 'Mon bilan' : 'M\'auto-évaluer sur cette trace'"
    @update:visible="(v) => emit('update:visible', v)"
  >
    <ProgressBar v-if="chargement" mode="indeterminate" style="height: 4px" />
    <Message v-else-if="erreur" severity="warn">{{ erreur }}</Message>

    <div v-else-if="auto" class="flex flex-col gap-5">
      <div
        class="rounded-lg p-3 border-l-[6px] flex flex-col gap-1"
        :style="{ borderLeftColor: couleurCompetence(auto.competence?.couleur), background: couleurCompetence(auto.competence?.couleur, 0.08) }"
      >
        <div class="flex items-center gap-2">
          <span
            v-if="auto.competence"
            class="text-xs font-semibold px-2 py-1 rounded"
            :style="{ background: couleurCompetence(auto.competence.couleur), color: texteSurCompetence(auto.competence.couleur) }"
          >{{ auto.competence.nomCourt }}</span>
          <Tag :value="auto.statut.libelle" :severity="auto.statut.badge" />
          <span v-if="score !== null" class="text-sm text-muted-color ml-auto">Positionnement : {{ score }} %</span>
        </div>
        <div class="text-sm"><strong v-if="auto.cible.code">{{ auto.cible.code }} </strong>{{ auto.cible.libelle }}</div>
        <div v-if="auto.trace" class="text-sm text-muted-color">Trace : {{ auto.trace.titre }}</div>
        <div v-if="auto.trace?.argumentaire" class="text-sm text-muted-color italic">« {{ auto.trace.argumentaire }} »</div>
      </div>

      <GrilleSaisie
        :grille="auto.grille"
        :model-value="reponses"
        :disabled="!auto.modifiable"
        :declaration-ia="declarationIa"
        @update:model-value="surReponses"
      />

      <div class="flex flex-col gap-2">
        <label for="autoeval-reflexion" class="font-medium">{{ auto.bilan ? 'Ma réflexion' : 'Ce que cette trace montre de ma progression' }}</label>
        <Textarea
          id="autoeval-reflexion"
          v-model="reflexion"
          rows="5"
          auto-resize
          :disabled="!auto.modifiable"
          placeholder="Points forts, difficultés rencontrées, ce que je ferais autrement, mes prochains objectifs…"
          @input="modifie = true"
        />
      </div>

      <section v-if="auto.retours?.length" class="flex flex-col gap-2">
        <h4 class="text-base font-semibold m-0">Retour de vos enseignants</h4>
        <RetourEnseignant v-for="retour in auto.retours" :key="retour.uuid" :retour="retour" />
      </section>

      <small class="text-muted-color">Grille : {{ auto.grille.libelle }} (version {{ auto.grille.version }})</small>
    </div>

    <template #footer>
      <div v-if="auto" class="flex flex-wrap justify-end gap-2">
        <Button label="Fermer" severity="secondary" text @click="fermer" />
        <template v-if="auto.modifiable">
          <Button label="Enregistrer" icon="pi pi-save" outlined :loading="occupe" @click="enregistrer" />
          <Button label="Soumettre" icon="pi pi-send" :loading="occupe" @click="soumettre" />
        </template>
        <Button v-else label="Modifier à nouveau" icon="pi pi-undo" severity="secondary" outlined :loading="occupe" @click="reprendre" />
      </div>
    </template>
  </Drawer>
</template>
