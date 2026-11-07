<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useToast } from 'primevue/usetoast';
import { useConfirm } from 'primevue/useconfirm';
import { VueDraggableNext as draggable } from 'vue-draggable-next';
import { HeaderComponent, EmptyState } from '@components';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import Textarea from 'primevue/textarea';
import Select from 'primevue/select';
import Checkbox from 'primevue/checkbox';
import ToggleSwitch from 'primevue/toggleswitch';
import Message from 'primevue/message';
import Tag from 'primevue/tag';
import ProgressBar from 'primevue/progressbar';
import adminService from '../../requests/adminService';
import { messageErreur } from '../../requests/traceService';
import { TYPES_CRITERE, PORTEES, CRITERE_IA, nouveauCritere, formatDate } from '../../components/grilles/options';

const route = useRoute();
const router = useRouter();
const toast = useToast();
const confirm = useConfirm();

const grille = ref(null);
const chargement = ref(true);
const occupe = ref(false);
const modifie = ref(false);
const meta = reactive({ libelle: '', description: '' });
const contenu = reactive({ echelons: [], criteres: [] });

const enEdition = computed(() => grille.value?.modifiable && grille.value?.contenuBrouillon);
const affiche = computed(() => (enEdition.value ? contenu : grille.value?.contenuPublie ?? grille.value?.contenuBrouillon));

const cloner = (v) => JSON.parse(JSON.stringify(v));

const appliquer = (data) => {
  grille.value = data;
  meta.libelle = data.libelle;
  meta.description = data.description ?? '';
  const source = data.contenuBrouillon ?? data.contenuPublie;
  contenu.echelons = cloner(source?.echelons ?? []);
  contenu.criteres = cloner(source?.criteres ?? []).map((c) => ({ ...c, description: c.description ?? '', aide: c.aide ?? '' }));
  modifie.value = false;
};

const action = async (fn, succes) => {
  occupe.value = true;
  try {
    const { data } = await fn();
    if (data) appliquer(data);
    if (succes) toast.add({ severity: 'success', summary: succes, life: 3000 });
    return true;
  } catch (e) {
    toast.add({ severity: 'error', summary: 'Action impossible', detail: messageErreur(e), life: 8000 });
    return false;
  } finally {
    occupe.value = false;
  }
};

onMounted(async () => {
  try {
    const { data } = await adminService.grille(route.params.uuid);
    appliquer(data);
  } catch (e) {
    toast.add({ severity: 'error', summary: 'Grille introuvable', detail: messageErreur(e), life: 6000 });
  } finally {
    chargement.value = false;
  }
});

const marquer = () => { modifie.value = true; };

// ---------- Échelle ----------
const ajouterEchelon = () => {
  const max = Math.max(-1, ...contenu.echelons.map((e) => Number(e.valeur) || 0));
  contenu.echelons.push({ libelle: '', valeur: max + 1, couleur: '#16a34a', descripteur: '' });
  marquer();
};
const retirerEchelon = (i) => { contenu.echelons.splice(i, 1); marquer(); };

// ---------- Critères ----------
const critereIa = computed({
  get: () => contenu.criteres.some((c) => c.source === 'declaration_ia'),
  set: (actif) => {
    if (actif && !critereIa.value) contenu.criteres.push(cloner(CRITERE_IA));
    if (!actif) contenu.criteres = contenu.criteres.filter((c) => c.source !== 'declaration_ia');
    marquer();
  },
});
const ajouterCritere = () => { contenu.criteres.push(nouveauCritere()); marquer(); };
const retirerCritere = (i) => { contenu.criteres.splice(i, 1); marquer(); };

const poidsTotal = computed(() => contenu.criteres.filter((c) => c.type !== 'texte').reduce((t, c) => t + (Number(c.poids) || 0), 0));
const part = (c) => (c.type === 'texte' || !poidsTotal.value ? null : Math.round(((Number(c.poids) || 0) * 100) / poidsTotal.value));

// ---------- Actions ----------
const charge = () => ({
  echelons: contenu.echelons.map(({ libelle, valeur, couleur, descripteur }) => ({ libelle, valeur, couleur, descripteur })),
  criteres: contenu.criteres.map(({ id, ...c }) => c),
});

const enregistrer = async () => {
  const uuid = grille.value.uuid;
  const okMeta = meta.libelle !== grille.value.libelle || meta.description !== (grille.value.description ?? '')
    ? await action(() => adminService.modifierGrille(uuid, { libelle: meta.libelle, description: meta.description }))
    : true;
  return okMeta && action(() => adminService.enregistrerBrouillon(uuid, charge()), 'Brouillon enregistré');
};

const publier = async () => {
  if (!(await enregistrer())) return;
  await action(() => adminService.publier(grille.value.uuid), `Version ${grille.value.brouillon} publiée`);
};

const nouvelleVersion = () => action(() => adminService.nouvelleVersion(grille.value.uuid), 'Nouvelle version en brouillon');

const abandonner = () => confirm.require({
  message: 'Abandonner les modifications de ce brouillon ?',
  header: 'Abandonner le brouillon',
  icon: 'pi pi-undo',
  acceptProps: { label: 'Abandonner', severity: 'danger' },
  rejectProps: { label: 'Annuler', severity: 'secondary', outlined: true },
  accept: () => action(() => adminService.abandonnerBrouillon(grille.value.uuid), 'Brouillon abandonné'),
});

const basculerActif = () => action(() => adminService.modifierGrille(grille.value.uuid, { actif: !grille.value.actif }), grille.value.actif ? 'Grille désactivée' : 'Grille réactivée');

const supprimer = () => confirm.require({
  message: 'Supprimer définitivement cette grille ?',
  header: 'Supprimer la grille',
  icon: 'pi pi-trash',
  acceptProps: { label: 'Supprimer', severity: 'danger' },
  rejectProps: { label: 'Annuler', severity: 'secondary', outlined: true },
  accept: async () => {
    try {
      await adminService.supprimerGrille(grille.value.uuid);
      router.push('/unifolio/admin/grilles');
    } catch (e) {
      toast.add({ severity: 'error', summary: 'Suppression impossible', detail: messageErreur(e), life: 6000 });
    }
  },
});

const adapter = async () => {
  try {
    const { data } = await adminService.dupliquer(grille.value.uuid, { portee: 'departement', libelle: `${grille.value.libelle} (département)` });
    router.push(`/unifolio/admin/grilles/${data.uuid}`);
    appliquer(data);
  } catch (e) {
    toast.add({ severity: 'error', summary: 'Copie impossible', detail: messageErreur(e), life: 6000 });
  }
};

const libelleType = (v) => TYPES_CRITERE.find((t) => t.value === v)?.label ?? v;
</script>

<template>
  <ProgressBar v-if="chargement" mode="indeterminate" style="height: 4px" />
  <EmptyState v-else-if="!grille" icon="pi pi-exclamation-triangle" color="rose" title="Grille introuvable" />

  <template v-else>
    <HeaderComponent
      icon="pi pi-table"
      :titre="grille.libelle"
      :description="`${grille.granularite.libelle} · ${grille.libellePortee}`"
      :show-back="true"
      back-url="/unifolio/admin/grilles"
      color="emerald"
    >
      <template #actions>
        <Tag :value="PORTEES[grille.portee].label" :severity="PORTEES[grille.portee].severity" />
        <Tag v-if="grille.versionPubliee" :value="`v${grille.versionPubliee} publiée`" severity="success" />
        <Tag v-if="grille.brouillon" :value="`v${grille.brouillon} brouillon`" severity="warn" />
      </template>
    </HeaderComponent>

    <Message v-if="!grille.modifiable" severity="info" class="mb-4">
      Grille {{ PORTEES[grille.portee].label.toLowerCase() }} en lecture seule.
      <Button label="Adapter pour mon département" icon="pi pi-copy" size="small" text @click="adapter" />
    </Message>

    <div class="grid gap-4 xl:grid-cols-3">
      <div class="xl:col-span-2 flex flex-col gap-4">
        <section v-if="enEdition" class="card p-4 flex flex-col gap-3">
          <div class="flex flex-col gap-1">
            <label for="ge-libelle" class="font-medium">Libellé</label>
            <InputText id="ge-libelle" v-model="meta.libelle" @input="marquer" />
          </div>
          <div class="flex flex-col gap-1">
            <label for="ge-desc" class="font-medium">Description</label>
            <Textarea id="ge-desc" v-model="meta.description" rows="2" auto-resize @input="marquer" />
          </div>
        </section>

        <section class="card p-4 flex flex-col gap-3">
          <div class="flex items-center justify-between">
            <div>
              <h3 class="text-base font-semibold m-0">Échelle</h3>
              <p class="text-sm text-muted-color m-0">Commune à tous les critères « échelle » de la grille.</p>
            </div>
            <Button v-if="enEdition" label="Niveau" icon="pi pi-plus" size="small" outlined @click="ajouterEchelon" />
          </div>

          <draggable v-model="contenu.echelons" handle=".poignee" :disabled="!enEdition" class="flex flex-col gap-2" @end="marquer">
            <div v-for="(e, i) in (enEdition ? contenu.echelons : affiche?.echelons ?? [])" :key="i" class="flex flex-wrap items-center gap-2">
              <i v-if="enEdition" class="pi pi-bars poignee cursor-move text-muted-color" />
              <template v-if="enEdition">
                <input v-model="e.couleur" type="color" class="w-9 h-9 rounded cursor-pointer border-0 p-0" aria-label="Couleur" @input="marquer" />
                <InputText v-model="e.libelle" placeholder="Libellé" class="w-48" @input="marquer" />
                <InputNumber v-model="e.valeur" :min-fraction-digits="0" :max-fraction-digits="2" input-class="w-20" aria-label="Valeur" @input="marquer" />
                <InputText v-model="e.descripteur" placeholder="Descripteur (facultatif)" class="flex-1 min-w-48" @input="marquer" />
                <Button icon="pi pi-trash" severity="danger" text rounded size="small" aria-label="Retirer le niveau" @click="retirerEchelon(i)" />
              </template>
              <template v-else>
                <span class="text-sm font-semibold px-2 py-1 rounded text-white" :style="{ background: e.couleur || 'var(--p-primary-color)' }">{{ e.libelle }}</span>
                <span class="text-sm text-muted-color">valeur {{ e.valeur }}<template v-if="e.descripteur"> — {{ e.descripteur }}</template></span>
              </template>
            </div>
          </draggable>
        </section>

        <section class="card p-4 flex flex-col gap-3">
          <div class="flex flex-wrap items-center justify-between gap-2">
            <h3 class="text-base font-semibold m-0">Critères</h3>
            <div v-if="enEdition" class="flex items-center gap-4">
              <label class="flex items-center gap-2 text-sm cursor-pointer">
                <ToggleSwitch v-model="critereIa" /> Évaluer l'usage de l'IA
              </label>
              <Button label="Critère" icon="pi pi-plus" size="small" outlined @click="ajouterCritere" />
            </div>
          </div>

          <draggable v-model="contenu.criteres" handle=".poignee" :disabled="!enEdition" class="flex flex-col gap-3" @end="marquer">
            <div
              v-for="(c, i) in (enEdition ? contenu.criteres : affiche?.criteres ?? [])"
              :key="i"
              class="border rounded-lg p-3 flex flex-col gap-3"
              :class="c.source === 'declaration_ia' ? 'border-violet-300 bg-violet-50/40 dark:bg-violet-950/20' : 'border-surface-200'"
            >
              <template v-if="enEdition">
                <div class="flex flex-wrap items-center gap-2">
                  <i class="pi pi-bars poignee cursor-move text-muted-color" />
                  <Tag v-if="c.source === 'declaration_ia'" value="IA" severity="contrast" icon="pi pi-sparkles" />
                  <InputText v-model="c.libelle" placeholder="Libellé du critère" class="flex-1 min-w-48" @input="marquer" />
                  <Select v-model="c.type" :options="TYPES_CRITERE" option-label="label" option-value="value" class="w-48" @change="marquer" />
                  <Button icon="pi pi-trash" severity="danger" text rounded size="small" aria-label="Retirer le critère" @click="retirerCritere(i)" />
                </div>
                <div class="grid gap-3 md:grid-cols-2">
                  <Textarea v-model="c.description" rows="2" auto-resize placeholder="Description (vue par l'étudiant si visible)" @input="marquer" />
                  <Textarea v-model="c.aide" rows="2" auto-resize placeholder="Indicateurs observables (aide à l'évaluateur)" @input="marquer" />
                </div>
                <div class="flex flex-wrap items-center gap-4 text-sm">
                  <label v-if="c.type !== 'texte'" class="flex items-center gap-2">
                    Poids <InputNumber v-model="c.poids" :min="0" :max="100" :max-fraction-digits="2" input-class="w-16" @input="marquer" />
                    <span v-if="part(c) !== null" class="text-muted-color">({{ part(c) }} %)</span>
                  </label>
                  <label v-if="c.type === 'note'" class="flex items-center gap-2">
                    Sur <InputNumber v-model="c.noteMax" :min="1" input-class="w-16" @input="marquer" />
                  </label>
                  <label v-if="c.type !== 'texte'" class="flex items-center gap-2 cursor-pointer"><Checkbox v-model="c.obligatoire" binary @change="marquer" /> Obligatoire</label>
                  <span v-else class="text-muted-color">Une appréciation libre est toujours facultative.</span>
                  <label class="flex items-center gap-2 cursor-pointer"><Checkbox v-model="c.visibleEtudiant" binary @change="marquer" /> Visible par l'étudiant</label>
                  <label class="flex items-center gap-2 cursor-pointer"><Checkbox v-model="c.utiliseAutoEval" binary @change="marquer" /> Utilisé en auto-évaluation</label>
                </div>
              </template>
              <template v-else>
                <div class="flex flex-wrap items-center gap-2">
                  <Tag v-if="c.source === 'declaration_ia'" value="IA" severity="contrast" icon="pi pi-sparkles" />
                  <span class="font-medium flex-1">{{ c.libelle }}</span>
                  <Tag :value="libelleType(c.type)" severity="secondary" />
                  <Tag v-if="c.type !== 'texte'" :value="`poids ${c.poids}`" severity="secondary" />
                </div>
                <p v-if="c.description" class="text-sm m-0">{{ c.description }}</p>
                <p v-if="c.aide" class="text-sm text-muted-color m-0">{{ c.aide }}</p>
              </template>
            </div>
          </draggable>
        </section>
      </div>

      <div class="flex flex-col gap-4">
        <section v-if="enEdition" class="card p-4 flex flex-col gap-2">
          <h3 class="text-base font-semibold m-0">Version {{ grille.brouillon }} (brouillon)</h3>
          <Message v-if="grille.contenuBrouillon?.erreursPublication?.length && !modifie" severity="warn">
            <ul class="m-0 pl-4">
              <li v-for="e in grille.contenuBrouillon.erreursPublication" :key="e">{{ e }}</li>
            </ul>
          </Message>
          <small v-if="modifie" class="text-amber-600">Modifications non enregistrées.</small>
          <Button label="Enregistrer le brouillon" icon="pi pi-save" :loading="occupe" @click="enregistrer" />
          <Button label="Publier cette version" icon="pi pi-check-circle" severity="success" outlined :loading="occupe" @click="publier" />
          <Button v-if="grille.versionPubliee" label="Abandonner le brouillon" icon="pi pi-undo" severity="secondary" text @click="abandonner" />
          <small class="text-muted-color">Une version publiée ne peut plus être modifiée ; les évaluations en cours la conservent.</small>
        </section>

        <section v-else-if="grille.modifiable" class="card p-4 flex flex-col gap-2">
          <h3 class="text-base font-semibold m-0">Version {{ grille.versionPubliee }} publiée</h3>
          <Button label="Préparer une nouvelle version" icon="pi pi-plus" :loading="occupe" @click="nouvelleVersion" />
        </section>

        <section class="card p-4 flex flex-col gap-2">
          <h3 class="text-base font-semibold m-0">Historique</h3>
          <div v-for="v in grille.versions" :key="v.numero" class="flex items-center justify-between text-sm">
            <span>Version {{ v.numero }}</span>
            <Tag :value="v.publieeLe ? `publiée le ${formatDate(v.publieeLe)}` : 'brouillon'" :severity="v.publieeLe ? 'success' : 'warn'" />
          </div>
        </section>

        <section v-if="grille.modifiable" class="card p-4 flex flex-col gap-2">
          <Button
            :label="grille.actif ? 'Désactiver la grille' : 'Réactiver la grille'"
            :icon="grille.actif ? 'pi pi-eye-slash' : 'pi pi-eye'"
            severity="secondary"
            outlined
            size="small"
            @click="basculerActif"
          />
          <Button v-if="!grille.versionPubliee" label="Supprimer la grille" icon="pi pi-trash" severity="danger" text size="small" @click="supprimer" />
        </section>
      </div>
    </div>
  </template>
</template>
