<script setup>
import { Alert } from '@components';
import { showSuccess } from '@helpers/toast';
import { computed, reactive, ref, watch } from 'vue';
import Dialog from 'primevue/dialog';
import Button from 'primevue/button';
import SelectButton from 'primevue/selectbutton';
import { portfolioService, messagesErreur } from '../../requests/portfolioService';
import { COINS, ENTETES, LARGEURS, PALETTES, POLICES, THEME_DEFAUT, couleurLisible, texteSur } from './theme';

const props = defineProps({
  visible: { type: Boolean, default: false },
  portfolio: { type: Object, required: true },
});
const emit = defineEmits(['update:visible', 'change']);

const form = reactive({ ...THEME_DEFAUT });
const erreurs = ref([]);
const envoi = ref(false);
const logoEnCours = ref(false);
const versionLogo = ref(0);
const champFichier = ref(null);

watch(() => props.visible, (v) => {
  if (v) {
    Object.assign(form, THEME_DEFAUT, props.portfolio.apparence ?? {});
    erreurs.value = [];
  }
}, { immediate: true });

const lisible = computed(() => couleurLisible(form.couleur));
const modifie = computed(() => Object.keys(THEME_DEFAUT).some((k) => form[k] !== (props.portfolio.apparence ?? THEME_DEFAUT)[k]));
const urlLogo = computed(() => (props.portfolio.logo ? `${portfolioService.urlLogo(props.portfolio.uuid)}?v=${versionLogo.value}` : null));

// Aperçu : mêmes variables que la page publiée.
const styleApercu = computed(() => ({
  '--pf-accent': lisible.value ? form.couleur : THEME_DEFAUT.couleur,
  '--pf-contraste': texteSur(lisible.value ? form.couleur : THEME_DEFAUT.couleur),
  '--pf-police': POLICES[form.police]?.pile,
  '--pf-rayon': form.coins === 'droits' ? '0' : '0.75rem',
}));

const enregistrer = async () => {
  envoi.value = true;
  erreurs.value = [];
  try {
    const { data } = await portfolioService.apparence(props.portfolio.uuid, { ...form });
    emit('change', data);
    showSuccess('Republiez le portfolio pour l\'appliquer à la version en ligne.', 'Apparence enregistrée', 5000);
    emit('update:visible', false);
  } catch (e) {
    erreurs.value = messagesErreur(e);
  } finally {
    envoi.value = false;
  }
};

const choisirLogo = async (event) => {
  const fichier = event.target.files?.[0];
  event.target.value = '';
  if (!fichier) return;
  logoEnCours.value = true;
  erreurs.value = [];
  try {
    const { data } = await portfolioService.definirLogo(props.portfolio.uuid, fichier);
    versionLogo.value++;
    emit('change', data);
  } catch (e) {
    erreurs.value = messagesErreur(e, 'Logo refusé.');
  } finally {
    logoEnCours.value = false;
  }
};

const retirerLogo = async () => {
  logoEnCours.value = true;
  try {
    const { data } = await portfolioService.retirerLogo(props.portfolio.uuid);
    emit('change', data);
  } catch (e) {
    erreurs.value = messagesErreur(e);
  } finally {
    logoEnCours.value = false;
  }
};
</script>

<template>
  <Dialog :visible="visible" header="Apparence du portfolio" modal :style="{ width: '48rem' }" :breakpoints="{ '768px': '95vw' }" @update:visible="emit('update:visible', $event)">
    <div class="flex flex-col gap-5">
      <Alert icon-native v-if="erreurs.length" severity="error" :closable="true" @close="erreurs = []">
        <ul class="m-0 pl-4"><li v-for="e in erreurs" :key="e">{{ e }}</li></ul>
      </Alert>

      <!-- Aperçu -->
      <div class="rounded-lg border border-surface-200 overflow-hidden" :style="styleApercu" aria-label="Aperçu de l'apparence" role="img">
        <div
          class="p-4 flex items-center gap-3"
          :style="{
            fontFamily: 'var(--pf-police)',
            background: form.entete === 'couleur' ? 'var(--pf-accent)' : form.entete === 'clair' ? 'color-mix(in srgb, var(--pf-accent) 9%, #fff)' : '#fff',
            color: form.entete === 'couleur' ? 'var(--pf-contraste)' : 'inherit',
            borderBottom: form.entete === 'clair' ? '3px solid var(--pf-accent)' : form.entete === 'sobre' ? '1px solid #e4e4e7' : 'none',
          }"
        >
          <img v-if="urlLogo" :src="urlLogo" alt="" style="max-height: 2.5rem; max-width: 7rem; object-fit: contain" />
          <div>
            <div style="font-size: 0.75rem; opacity: 0.8">Prénom Nom</div>
            <div style="font-size: 1.25rem; font-weight: 600">Mon portfolio</div>
          </div>
        </div>
        <div class="p-4 flex flex-col gap-2" :style="{ fontFamily: 'var(--pf-police)' }">
          <strong>Un titre de section</strong>
          <span>Un paragraphe avec <span :style="{ color: 'var(--pf-accent)', textDecoration: 'underline' }">un lien</span>.</span>
          <span class="self-start px-3 py-1" :style="{ background: 'var(--pf-accent)', color: 'var(--pf-contraste)', borderRadius: 'var(--pf-rayon)' }">Un bouton</span>
        </div>
      </div>

      <!-- Couleur -->
      <fieldset class="border-0 p-0 m-0 flex flex-col gap-2">
        <legend class="font-semibold mb-1">Couleur d'accent</legend>
        <div class="flex flex-wrap items-center gap-2">
          <button
            v-for="p in PALETTES"
            :key="p.couleur"
            type="button"
            class="w-9 h-9 rounded-full border-2 cursor-pointer"
            :style="{ background: p.couleur, borderColor: form.couleur === p.couleur ? '#18181b' : 'transparent' }"
            :aria-label="p.nom"
            :aria-pressed="form.couleur === p.couleur"
            :title="p.nom"
            @click="form.couleur = p.couleur"
          />
          <label class="flex items-center gap-2 ml-2 text-sm">
            <input v-model="form.couleur" type="color" class="w-9 h-9 p-0 border-0 bg-transparent cursor-pointer" aria-label="Couleur personnalisée" />
            Personnalisée
          </label>
        </div>
        <small v-if="!lisible" class="text-red-600">Cette teinte est trop claire : le texte et les liens ne seraient pas lisibles sur fond blanc. Choisissez-en une plus foncée.</small>
      </fieldset>

      <!-- Police -->
      <fieldset class="border-0 p-0 m-0 flex flex-col gap-2">
        <legend class="font-semibold mb-1">Police</legend>
        <div class="grid gap-2 sm:grid-cols-3">
          <button
            v-for="(p, cle) in POLICES"
            :key="cle"
            type="button"
            class="rounded-lg border p-3 text-left cursor-pointer bg-transparent"
            :class="form.police === cle ? 'border-emerald-500 bg-emerald-50/50' : 'border-surface-200'"
            :style="{ fontFamily: p.pile }"
            :aria-pressed="form.police === cle"
            @click="form.police = cle"
          >
            <span class="block text-xl">Aa</span>
            <span class="text-sm">{{ p.libelle }}</span>
          </button>
        </div>
        <small class="text-muted-color">Polices système : aucune ressource externe n'est chargée, vos visiteurs ne sont pas suivis.</small>
      </fieldset>

      <div class="grid gap-4 sm:grid-cols-3">
        <div class="flex flex-col gap-1"><span class="font-semibold">En-tête</span><SelectButton v-model="form.entete" :options="ENTETES" option-label="l" option-value="v" :allow-empty="false" aria-label="Style de l'en-tête" /></div>
        <div class="flex flex-col gap-1"><span class="font-semibold">Largeur</span><SelectButton v-model="form.largeur" :options="LARGEURS" option-label="l" option-value="v" :allow-empty="false" aria-label="Largeur du contenu" /></div>
        <div class="flex flex-col gap-1"><span class="font-semibold">Coins</span><SelectButton v-model="form.coins" :options="COINS" option-label="l" option-value="v" :allow-empty="false" aria-label="Forme des coins" /></div>
      </div>

      <!-- Logo -->
      <section class="flex flex-wrap items-center gap-3">
        <span class="font-semibold w-full">Logo</span>
        <img v-if="urlLogo" :src="urlLogo" :alt="`Logo : ${portfolio.logo.nom}`" class="rounded border border-surface-200 p-1 bg-white" style="max-height: 3.5rem; max-width: 10rem; object-fit: contain" />
        <span v-else class="text-sm text-muted-color">Aucun logo.</span>
        <input ref="champFichier" type="file" accept="image/png,image/jpeg,image/webp" class="hidden" aria-label="Choisir un logo" @change="choisirLogo" />
        <Button :label="portfolio.logo ? 'Remplacer' : 'Ajouter un logo'" icon="pi pi-upload" size="small" severity="secondary" outlined :loading="logoEnCours" @click="champFichier.click()" />
        <Button v-if="portfolio.logo" label="Retirer" icon="pi pi-trash" size="small" severity="danger" text :loading="logoEnCours" @click="retirerLogo" />
        <small class="w-full text-muted-color">PNG, JPEG ou WebP, 2 Mo maximum (le SVG n'est pas accepté). Il s'affiche dans l'en-tête des pages publiées.</small>
      </section>
    </div>
    <template #footer>
      <Button label="Annuler" severity="secondary" outlined @click="emit('update:visible', false)" />
      <Button label="Enregistrer" icon="pi pi-check" :disabled="!modifie || !lisible" :loading="envoi" @click="enregistrer" />
    </template>
  </Dialog>
</template>
