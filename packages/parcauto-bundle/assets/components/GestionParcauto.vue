<script setup>
import { computed, ref, watch } from 'vue';
import { useRoute } from 'vue-router';
import { Card, EmptyState, ListSkeleton, PermissionGuard } from '@components';
import { hasPermission } from '@utils/permissions';
import { champEuros, champLitres, enCentimes, enMillilitres } from './parcautoUnites';
import FormulaireParcauto from './FormulaireParcauto.vue';
import { enregistrerParcautoService, getSuiviService } from '../service/parcautoService';
import { dateHeure, jourLocal, messageErreur } from './parcautoDates';

const props = defineProps({ vehicules: Array, equipements: Array, referentiels: Array, gestion: Boolean, entretien: Boolean });
const emit = defineEmits(['actualiser']);
const route = useRoute();
const selection = ref(null);
const filtre = ref('');
const suivi = ref(null);
const chargement = ref(false);
const sauvegarde = ref(false);
const erreur = ref('');
const dialogue = ref(null);
const formulaire = ref({});

const options = groupe => props.referentiels.filter(v => v.groupe === groupe).map(v => ({ value: v.code, label: v.libelle }));
const libelle = (groupe, code) => props.referentiels.find(v => v.groupe === groupe && v.code === code)?.libelle ?? code;
const choixEquipement = (type) => props.equipements.filter((e) => e.type === type && e.actif).map((e) => ({ value: e.id, label: e.libelle }));
const choixVehicule = computed(() => props.vehicules.map((v) => ({ value: v.id, label: v.libelle + ' · ' + v.immatriculation })));
const vehicule = computed(() => props.vehicules.find((v) => v.id === selection.value));
const visibles = computed(() => props.vehicules.filter((v) => `${v.libelle} ${v.site} ${v.immatriculation}`.toLowerCase().includes(filtre.value.toLowerCase())));
const peutGerer = computed(() => props.gestion && hasPermission('canManageParcauto'));
const peutEntretenir = computed(() => props.entretien && hasPermission('canMaintainParcauto'));
const suiviUrl = computed(() => `/vehicules/${selection.value}`);

const texte = (nom, label, max, requis = true) => ({ nom, label, max, requis });
const nombre = (nom, label, requis = false, min = 0) => ({ nom, label, requis, min, type: 'number' });
const date = (nom, label, requis = false) => ({ nom, label, requis, type: 'native-date' });
const liste = (nom, label, valeurs, requis = false) => ({ nom, label, requis, options: valeurs });
const actif = { nom: 'actif', label: 'Actif', type: 'boolean' };
const condition = (champ, visible) => ({ ...champ, visible });

let requete = 0;
async function chargerSuivi() {
  const version = ++requete;
  if (!selection.value || !peutEntretenir.value) return;
  chargement.value = true;
  try {
    const resultat = await getSuiviService(selection.value);
    if (version === requete) suivi.value = resultat;
  } catch (e) {
    if (version === requete) erreur.value = messageErreur(e);
  } finally {
    if (version === requete) chargement.value = false;
  }
}

watch(selection, () => {
  erreur.value = '';
  suivi.value = null;
  chargerSuivi();
});

watch(() => route.query.vehicule, (id) => {
  if (peutEntretenir.value && typeof id === 'string' && props.vehicules.some(v => String(v.id) === id)) {
    selection.value = Number(id);
  }
}, { immediate: true });

function ouvrir(titre, chemin, champs, valeurs, modifier = false) {
  dialogue.value = { titre, chemin, champs, modifier };
  formulaire.value = { ...valeurs };
  erreur.value = '';
}

function ouvrirVehicule(item = null) {
  const champs = [
    { ...texte('immatriculation', 'Immatriculation', 24), lecture: Boolean(item) },
    texte('libelle', 'Marque / modèle', 150),
    texte('site', 'Site d’affectation', 120),
    liste('energie', 'Énergie', options('energie'), true),
    { ...nombre('kilometrage', 'Kilométrage au compteur', true), lecture: Boolean(item) },
    date('misEnServiceLe', 'Date de mise en service'),
    date('reformePrevueLe', 'Date cible de réforme'),
    nombre('reformeKm', 'Kilométrage cible de réforme', false, 1),
    { nom: 'achatEuros', label: 'Prix d’achat (€)', ...champEuros },
    nombre('consommationReference', 'Consommation de référence (dixièmes de L/100 km)', false, 1),
    { ...actif, lecture: Boolean(item) },
  ];
  ouvrir(item ? 'Modifier le véhicule' : 'Ajouter un véhicule', item ? `/vehicules/${item.id}` : '/vehicules',
    champs, item ? { ...item, achatEuros: item.achatCentimes == null ? null : item.achatCentimes / 100 } : { actif: true }, Boolean(item));
}

function ouvrirEquipement(item = null) {
  const champs = [
    { ...liste('type', 'Catégorie', options('equipement'), true), lecture: Boolean(item) },
    { ...texte('reference', 'Référence interne (sans numéro complet de carte)', 120), lecture: Boolean(item) },
    texte('libelle', 'Nom', 150),
    texte('coordonnees', 'Site, adresse ou coordonnées', 255, false),
    condition(liste('vehicule', 'Affectation (vide pour un équipement partagé)', choixVehicule.value), (d) => ['carte', 'pass_telepeage'].includes(d.type)),
    actif,
  ];
  ouvrir(item ? 'Modifier la fiche' : 'Ajouter une fiche', item ? `/equipements/${item.id}` : '/equipements',
    champs, item ?? { actif: true }, Boolean(item));
}

function champTrajet() {
  return liste('trajet', 'Trajet lié à l’intervention', [
    { value: 0, label: 'Sur place, sans trajet' },
    ...(suivi.value?.trajets ?? []).map(t => ({ value: t.id, label: 'Trajet n° ' + t.id + ' · ' + dateHeure(t.depart) })),
  ], true);
}

function ouvrirPlan(plan = null) {
  const champs = [
    { ...liste('type', 'Type de contrôle', options('plan'), true), lecture: Boolean(plan) },
    { ...texte('libelle', 'Opération prévue', 150), lecture: Boolean(plan) },
    { ...date('effectueLe', 'Intervention réalisée le', Boolean(plan)), requisSi: (d) => d.type === 'pneus' },
    condition(champTrajet(), d => Boolean(d.effectueLe)),
    condition(date('echeance', 'Prochaine échéance'), (d) => d.type !== 'pneus' && !(d.intervalleMois && d.effectueLe)),
    condition(nombre('intervalleMois', 'Fréquence en mois', false, 1), (d) => d.type !== 'pneus'),
    nombre('intervalleKm', 'Fréquence en km', false, 1),
    condition(nombre('echeanceKm', 'Échéance au compteur (km)', false, 1), (d) => !(d.intervalleKm && d.effectueLe)),
    liste('prestataire', 'Prestataire', choixEquipement('prestataire')),
  ];
  ouvrir(plan ? 'Enregistrer une intervention' : 'Ajouter une échéance', suiviUrl.value + '/echeances' + (plan ? `/${plan.id}` : ''),
    champs, plan ? { ...plan, effectueLe: jourLocal() } : {}, Boolean(plan));
}

function ouvrirJournal() {
  const changementEtat = d => ['hors_service', 'remise_service'].includes(d.type);
  const types = options('journal').filter(option => option.value !== (vehicule.value.actif ? 'remise_service' : 'hors_service'));
  const champs = [
    liste('type', 'Type de saisie', types, true),
    date('date', 'Date', true),
    champTrajet(),
    condition(texte('libelle', 'Motif du changement de disponibilité', 255), changementEtat),
    condition(texte('libelle', 'Référence (facture, contrôle, train de pneus ou symptôme)', 255), d => !changementEtat(d)),
    liste('prestataire', 'Prestataire', choixEquipement('prestataire')),
    condition(nombre('kilometrage', 'Kilométrage lors du plein', true), (d) => d.type === 'carburant'),
    condition({ nom: 'litres', label: 'Volume (L)', requis: true, ...champLitres }, (d) => d.type === 'carburant'),
    condition(liste('pass', 'Pass utilisé', choixEquipement('pass_telepeage')), d => d.type === 'telepeage'),
    condition(texte('numeroFacture', 'N° de facture ou de ticket', 120, false), d => ['telepeage', 'carburant'].includes(d.type)),
    condition(liste('carte', 'Carte carburant', choixEquipement('carte')), (d) => d.type === 'carburant'),
    condition({ nom: 'euros', label: 'Montant (€)', requisSi: d => d.type !== 'telepeage', ...champEuros }, (d) => ['carburant', 'facture', 'telepeage'].includes(d.type)),
    condition(nombre('usureDixiemesMm', 'Profondeur restante (dixièmes de mm)', true), (d) => d.type === 'pneus'),
  ];
  ouvrir('Ajouter au journal', suiviUrl.value + '/journal', champs, { date: jourLocal() });
}

function ouvrirFacturation(entree) {
  ouvrir('Facturation du télépéage', suiviUrl.value + '/journal/' + entree.id + '/facturation',
    [texte('numeroFacture', 'N° de facture', 120, false), { nom: 'euros', label: 'Montant (€)', ...champEuros }],
    { numeroFacture: entree.numeroFacture, euros: entree.montantCentimes == null ? null : entree.montantCentimes / 100 }, true);
}

async function enregistrer() {
  sauvegarde.value = true;
  erreur.value = '';
  try {
    const { chemin, modifier, champs } = dialogue.value;
    const visibles = champs.filter((champ) => !champ.visible || champ.visible(formulaire.value));
    const donnees = Object.fromEntries(visibles.map(({ nom, type }) => [nom, formulaire.value[nom] ?? (type === 'native-date' ? '' : undefined)]));
    const { achatEuros, euros, litres, ...payload } = donnees;
    if (achatEuros != null) payload.achatCentimes = enCentimes(achatEuros);
    if (euros != null) payload.montantCentimes = enCentimes(euros);
    if (litres != null) payload.volumeMillilitres = enMillilitres(litres);
    await enregistrerParcautoService(chemin, payload, modifier);
    dialogue.value = null;
    emit('actualiser');
    await chargerSuivi();
  } catch (e) {
    erreur.value = messageErreur(e);
  } finally {
    sauvegarde.value = false;
  }
}
</script>

<template>
  <div class="grid gap-5">
    <Message v-if="erreur && !dialogue" severity="error">{{ erreur }}</Message>
    <Card title="Véhicules" icon="pi pi-car">
      <template #header-actions>
        <div class="flex flex-wrap gap-2">
          <InputText v-model="filtre" placeholder="Immatriculation, modèle, site" aria-label="Rechercher un véhicule" />
          <PermissionGuard v-if="gestion" permission="canManageParcauto">
            <Button label="Ajouter un véhicule" icon="pi pi-plus" @click="ouvrirVehicule()" />
          </PermissionGuard>
        </div>
      </template>
      <DataTable :value="visibles" data-key="id" paginator :rows="10">
        <template #empty><EmptyState title="Aucun véhicule" icon="pi pi-car" compact /></template>
        <Column field="libelle" header="Véhicule" sortable />
        <Column field="immatriculation" header="Immatriculation" />
        <Column field="site" header="Site" sortable />
        <Column header="Énergie"><template #body="{ data }">{{ libelle('energie', data.energie) }}</template></Column>
        <Column field="kilometrage" header="Kilométrage" />
        <Column header="État">
          <template #body="{ data }"><Tag :value="data.blocage || 'Réservable'" :severity="data.blocage ? 'danger' : 'success'" /></template>
        </Column>
        <Column v-if="peutEntretenir" header="Actions">
          <template #body="{ data }">
            <div class="flex flex-wrap gap-1">
              <Button label="Suivi" size="small" severity="secondary" text @click="selection = data.id" />
              <Button v-if="peutGerer" label="Modifier" size="small" severity="secondary" text @click="ouvrirVehicule(data)" />
            </div>
          </template>
        </Column>
      </DataTable>
    </Card>
    <PermissionGuard v-if="vehicule" :permission="peutEntretenir">
      <Card :title="'Suivi · ' + vehicule.libelle" :subtitle="vehicule.immatriculation" icon="pi pi-wrench">
        <template #header-actions>
          <div class="flex flex-wrap gap-2">
            <Button label="Ajouter une échéance" :disabled="!suivi" size="small" @click="ouvrirPlan()" />
            <Button label="Ajouter au journal" :disabled="!suivi" size="small" severity="secondary" @click="ouvrirJournal" />
          </div>
        </template>
        <ListSkeleton v-if="chargement" />
        <div v-else-if="suivi" class="grid gap-5">
          <DataTable :value="suivi.echeances" data-key="id">
            <template #empty><EmptyState title="Aucune échéance renseignée" icon="pi pi-wrench" compact /></template>
            <Column field="libelle" header="Plan d’entretien" />
            <Column header="Type"><template #body="{ data }">{{ libelle('plan', data.type) }}</template></Column>
            <Column field="effectueLe" header="Dernière intervention" />
            <Column field="echeance" header="Prochaine échéance" />
            <Column field="echeanceKm" header="Échéance (km)" />
            <Column header="Action"><template #body="{ data }"><Button label="Intervention" size="small" text @click="ouvrirPlan(data)" /></template></Column>
          </DataTable>
          <DataTable :value="suivi.journal" data-key="id" paginator :rows="10">
            <template #empty><EmptyState title="Aucune entrée dans le journal" icon="pi pi-list" compact /></template>
            <Column field="date" header="Date" />
            <Column field="trajet" header="N° de trajet" />
            <Column field="libelle" header="Journal (100 dernières entrées)" />
            <Column header="Type"><template #body="{ data }">{{ libelle('journal', data.type) }}</template></Column>
            <Column field="prestataire" header="Prestataire" />
            <Column field="carte" header="Carte" />
            <Column field="pass" header="Pass" />
            <Column field="numeroFacture" header="N° de facture / ticket" />
            <Column header="Facturation"><template #body="{ data }">
              <Button v-if="data.type === 'telepeage'" label="Renseigner" text size="small" @click="ouvrirFacturation(data)" />
            </template></Column>
            <Column field="kilometrage" header="Km" />
            <Column header="Montant (€)"><template #body="{ data }">{{ data.montantCentimes === null ? '' : data.montantCentimes / 100 }}</template></Column>
            <Column header="Volume (L)"><template #body="{ data }">{{ data.volumeMillilitres === null ? '' : data.volumeMillilitres / 1000 }}</template></Column>
            <Column header="Pneus (mm)"><template #body="{ data }">{{ data.usureDixiemesMm === null ? '' : data.usureDixiemesMm / 10 }}</template></Column>
          </DataTable>
        </div>
      </Card>
    </PermissionGuard>
    <Card :title="peutEntretenir ? 'Prestataires, cartes, pass et bornes' : 'Bornes de recharge URCA'" icon="pi pi-map-marker">
      <template #header-actions>
        <PermissionGuard v-if="gestion" permission="canManageParcauto">
          <Button label="Ajouter une fiche" icon="pi pi-plus" @click="ouvrirEquipement()" />
        </PermissionGuard>
      </template>
      <DataTable :value="equipements" data-key="id" paginator :rows="10">
        <template #empty><EmptyState title="Aucune fiche" icon="pi pi-map-marker" compact /></template>
        <Column header="Catégorie"><template #body="{ data }">{{ libelle('equipement', data.type) }}</template></Column>
        <Column field="libelle" header="Nom" />
        <Column field="coordonnees" header="Site ou coordonnées" />
        <Column v-if="peutGerer" field="reference" header="Référence" />
        <Column v-if="peutGerer" header="Affectation">
          <template #body="{ data }">{{ ['carte', 'pass_telepeage'].includes(data.type) ? (choixVehicule.find((v) => v.value === data.vehicule)?.label || 'Partagé') : '' }}</template>
        </Column>
        <Column v-if="peutGerer" header="État">
          <template #body="{ data }"><Tag :value="data.actif ? 'Actif' : 'Inactif'" :severity="data.actif ? 'success' : 'secondary'" /></template>
        </Column>
        <Column v-if="peutGerer" header="Action">
          <template #body="{ data }"><Button label="Modifier" size="small" text @click="ouvrirEquipement(data)" /></template>
        </Column>
      </DataTable>
    </Card>
  </div>

  <PermissionGuard :permission="peutEntretenir">
    <Dialog :visible="Boolean(dialogue)" :header="dialogue?.titre" modal :closable="!sauvegarde"
            :style="{ width: 'min(38rem, 95vw)' }" @update:visible="!$event && (dialogue = null)">
      <FormulaireParcauto v-if="dialogue" v-model="formulaire" :champs="dialogue.champs" :erreur="erreur"
                          :sauvegarde="sauvegarde" @submit="enregistrer" />
    </Dialog>
  </PermissionGuard>
</template>
