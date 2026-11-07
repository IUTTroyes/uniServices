<script setup>
import { showDanger, showSuccess } from '@helpers/toast';
import { computed, onMounted, reactive, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useConfirmation } from '@composables/useConfirmation';
import { HeaderComponent, EmptyState, Card, Alert, LoadingBar } from '@components';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import Tag from 'primevue/tag';
import adminService from '../../requests/adminService';
import { messageErreur } from '../../requests/traceService';
import { PORTEES, formatDate } from '../../components/grilles/options';
import GrilleEchelles from '../../components/grilles/GrilleEchelles.vue';
import GrilleCriteres from '../../components/grilles/GrilleCriteres.vue';

const route = useRoute();
const router = useRouter();
const { confirmer } = useConfirmation();

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
    if (succes) showSuccess(undefined, succes, 3000);
    return true;
  } catch (e) {
    showDanger(messageErreur(e), 'Action impossible', 8000);
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
    showDanger(messageErreur(e), 'Grille introuvable', 6000);
  } finally {
    chargement.value = false;
  }
});

const marquer = () => { modifie.value = true; };

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

const abandonner = () => confirmer({
  header: 'Abandonner le brouillon',
  message: 'Abandonner les modifications de ce brouillon ?',
  icon: 'pi pi-undo',
  libelle: 'Abandonner',
  danger: true,
  accept: () => action(() => adminService.abandonnerBrouillon(grille.value.uuid), 'Brouillon abandonné'),
});

const basculerActif = () => action(() => adminService.modifierGrille(grille.value.uuid, { actif: !grille.value.actif }), grille.value.actif ? 'Grille désactivée' : 'Grille réactivée');

const supprimer = () => confirmer({
  header: 'Supprimer la grille',
  message: 'Supprimer définitivement cette grille ?',
  icon: 'pi pi-trash',
  libelle: 'Supprimer',
  danger: true,
  accept: async () => {
    try {
      await adminService.supprimerGrille(grille.value.uuid);
      router.push('/unifolio/admin/grilles');
    } catch (e) {
      showDanger(messageErreur(e), 'Suppression impossible', 6000);
    }
  },
});

const adapter = async () => {
  try {
    const { data } = await adminService.dupliquer(grille.value.uuid, { portee: 'departement', libelle: `${grille.value.libelle} (département)` });
    router.push(`/unifolio/admin/grilles/${data.uuid}`);
    appliquer(data);
  } catch (e) {
    showDanger(messageErreur(e), 'Copie impossible', 6000);
  }
};

</script>

<template>
  <LoadingBar v-if="chargement" />
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

    <Alert icon-native :closable="false" v-if="!grille.modifiable" severity="info" class="mb-4">
      Grille {{ PORTEES[grille.portee].label.toLowerCase() }} en lecture seule.
      <Button label="Adapter pour mon département" icon="pi pi-copy" size="small" text @click="adapter" />
    </Alert>

    <div class="grid gap-4 xl:grid-cols-3">
      <div class="xl:col-span-2 flex flex-col gap-4">
        <Card bare tag="section" v-if="enEdition" class="p-4 flex flex-col gap-3">
          <div class="flex flex-col gap-1">
            <label for="ge-libelle" class="font-medium">Libellé</label>
            <InputText id="ge-libelle" v-model="meta.libelle" @input="marquer" />
          </div>
          <div class="flex flex-col gap-1">
            <label for="ge-desc" class="font-medium">Description</label>
            <Textarea id="ge-desc" v-model="meta.description" rows="2" auto-resize @input="marquer" />
          </div>
        </Card>

        <GrilleEchelles :echelons="contenu.echelons" :affiche="affiche?.echelons ?? null" :en-edition="!!enEdition" @modifie="marquer" />

        <GrilleCriteres :criteres="contenu.criteres" :affiche="affiche?.criteres ?? null" :en-edition="!!enEdition" @modifie="marquer" />
      </div>

      <div class="flex flex-col gap-4">
        <Card bare tag="section" v-if="enEdition" class="p-4 flex flex-col gap-2">
          <h3 class="text-base font-semibold m-0">Version {{ grille.brouillon }} (brouillon)</h3>
          <Alert icon-native :closable="false" v-if="grille.contenuBrouillon?.erreursPublication?.length && !modifie" severity="warn">
            <ul class="m-0 pl-4">
              <li v-for="e in grille.contenuBrouillon.erreursPublication" :key="e">{{ e }}</li>
            </ul>
          </Alert>
          <small v-if="modifie" class="text-amber-600">Modifications non enregistrées.</small>
          <Button label="Enregistrer le brouillon" icon="pi pi-save" :loading="occupe" @click="enregistrer" />
          <Button label="Publier cette version" icon="pi pi-check-circle" severity="success" outlined :loading="occupe" @click="publier" />
          <Button v-if="grille.versionPubliee" label="Abandonner le brouillon" icon="pi pi-undo" severity="secondary" text @click="abandonner" />
          <small class="text-muted-color">Une version publiée ne peut plus être modifiée ; les évaluations en cours la conservent.</small>
        </Card>

        <Card bare tag="section" v-else-if="grille.modifiable" class="p-4 flex flex-col gap-2">
          <h3 class="text-base font-semibold m-0">Version {{ grille.versionPubliee }} publiée</h3>
          <Button label="Préparer une nouvelle version" icon="pi pi-plus" :loading="occupe" @click="nouvelleVersion" />
        </Card>

        <Card bare tag="section" class="p-4 flex flex-col gap-2">
          <h3 class="text-base font-semibold m-0">Historique</h3>
          <div v-for="v in grille.versions" :key="v.numero" class="flex items-center justify-between text-sm">
            <span>Version {{ v.numero }}</span>
            <Tag :value="v.publieeLe ? `publiée le ${formatDate(v.publieeLe)}` : 'brouillon'" :severity="v.publieeLe ? 'success' : 'warn'" />
          </div>
        </Card>

        <Card bare tag="section" v-if="grille.modifiable" class="p-4 flex flex-col gap-2">
          <Button
            :label="grille.actif ? 'Désactiver la grille' : 'Réactiver la grille'"
            :icon="grille.actif ? 'pi pi-eye-slash' : 'pi pi-eye'"
            severity="secondary"
            outlined
            size="small"
            @click="basculerActif"
          />
          <Button v-if="!grille.versionPubliee" label="Supprimer la grille" icon="pi pi-trash" severity="danger" text size="small" @click="supprimer" />
        </Card>
      </div>
    </div>
  </template>
</template>
