<script setup>
import { computed, reactive, ref, watch } from 'vue'
import api from '@helpers/axios'

const props = defineProps({
  isVisible: Boolean,
})

const emit = defineEmits(['update:isVisible', 'created'])

const emptyForm = () => ({
  nom: '',
  prenom: '',
  username: '',
  mailUniv: '',
  numeroHarpege: null,
})

const form = reactive(emptyForm())
const isSaving = ref(false)
const isSearching = ref(false)
const errorMessage = ref('')
const infoMessage = ref('')
// 'recherche' : saisie du code RH + interrogation LDAP ; 'formulaire' : saisie / validation des champs
const step = ref('recherche')
const fromLdap = ref(false)

watch(() => props.isVisible, (visible) => {
  if (visible) {
    Object.assign(form, emptyForm())
    errorMessage.value = ''
    infoMessage.value = ''
    step.value = 'recherche'
    fromLdap.value = false
  }
})

const manualMode = () => {
  fromLdap.value = false
  step.value = 'formulaire'
}

const searchLdap = async () => {
  const code = String(form.numeroHarpege ?? '').trim()
  if (!code) return

  isSearching.value = true
  errorMessage.value = ''
  infoMessage.value = ''

  try {
    const { data } = await api.get(`/api/ldap/personnel/${encodeURIComponent(code)}`)
    form.nom = data.nom ?? ''
    form.prenom = data.prenom ?? ''
    form.username = data.login ?? ''
    form.mailUniv = data.mail ?? ''
    fromLdap.value = true
    infoMessage.value = 'Personnel trouvé dans le LDAP. Vérifiez les informations avant de valider.'
    step.value = 'formulaire'
  } catch (error) {
    const status = error?.response?.status
    if (status === 409) {
      // Doublon : on reste sur la recherche, la création est impossible
      errorMessage.value = error.response.data?.message ?? 'Un personnel existe déjà avec ce code RH.'
      return
    }
    infoMessage.value = status === 404
      ? 'Aucun personnel trouvé dans le LDAP pour ce code RH : saisie manuelle.'
      : 'Le LDAP est indisponible : saisie manuelle.'
    fromLdap.value = false
    step.value = 'formulaire'
  } finally {
    isSearching.value = false
  }
}

const isValid = computed(() =>
  form.nom.trim() && form.prenom.trim() && form.username.trim() && form.mailUniv.trim()
)

const closeDialog = () => emit('update:isVisible', false)

const submit = async () => {
  if (!isValid.value) return

  isSaving.value = true
  errorMessage.value = ''

  try {
    const payload = {
      nom: form.nom.trim(),
      prenom: form.prenom.trim(),
      username: form.username.trim(),
      mailUniv: form.mailUniv.trim(),
      numeroHarpege: form.numeroHarpege === null || form.numeroHarpege === '' ? null : Number(form.numeroHarpege),
    }
    const response = await api.post('/api/personnels', payload, {
      headers: { 'Content-Type': 'application/ld+json' },
    })
    emit('created', response.data)
    closeDialog()
  } catch (error) {
    const data = error?.response?.data
    const violations = (data?.violations ?? []).map((v) => v.message).filter(Boolean)
    errorMessage.value = violations.length
      ? violations.join(' ')
      : (data?.detail ?? data?.['hydra:description'] ?? 'Impossible de créer le personnel.')
  } finally {
    isSaving.value = false
  }
}
</script>

<template>
  <Dialog
    :visible="isVisible"
    modal
    header="Nouveau personnel"
    :style="{ width: '32rem' }"
    @update:visible="closeDialog"
  >
    <form v-if="step === 'recherche'" class="flex flex-col gap-4" @submit.prevent="searchLdap">
      <Message v-if="errorMessage" severity="error" :closable="false">{{ errorMessage }}</Message>
      <p class="m-0 text-sm text-muted-color">
        Saisissez le code RH du personnel : ses informations seront recherchées dans le LDAP.
      </p>
      <div class="flex flex-col gap-1">
        <label for="np-harpege-search" class="font-semibold text-sm">Code RH</label>
        <InputNumber id="np-harpege-search" v-model="form.numeroHarpege" :useGrouping="false" autofocus />
      </div>
      <div class="flex justify-between gap-2 mt-2">
        <Button label="Saisie manuelle" severity="secondary" text type="button" @click="manualMode" />
        <div class="flex gap-2">
          <Button label="Annuler" severity="secondary" text type="button" @click="closeDialog" />
          <Button label="Rechercher" icon="pi pi-search" type="submit" :loading="isSearching" :disabled="!form.numeroHarpege" />
        </div>
      </div>
    </form>

    <form v-else class="flex flex-col gap-4" @submit.prevent="submit">
      <Message v-if="infoMessage" :severity="fromLdap ? 'success' : 'warn'" :closable="false">{{ infoMessage }}</Message>
      <Message v-if="errorMessage" severity="error" :closable="false">{{ errorMessage }}</Message>

      <div class="flex flex-col gap-1">
        <label for="np-nom" class="font-semibold text-sm">Nom *</label>
        <InputText id="np-nom" v-model="form.nom" required />
      </div>
      <div class="flex flex-col gap-1">
        <label for="np-prenom" class="font-semibold text-sm">Prénom *</label>
        <InputText id="np-prenom" v-model="form.prenom" required />
      </div>
      <div class="flex flex-col gap-1">
        <label for="np-username" class="font-semibold text-sm">Login *</label>
        <InputText id="np-username" v-model="form.username" required />
      </div>
      <div class="flex flex-col gap-1">
        <label for="np-mail" class="font-semibold text-sm">Mail Univ *</label>
        <InputText id="np-mail" v-model="form.mailUniv" type="email" required />
      </div>
      <div class="flex flex-col gap-1">
        <label for="np-harpege" class="font-semibold text-sm">Code RH</label>
        <InputNumber id="np-harpege" v-model="form.numeroHarpege" :useGrouping="false" :disabled="fromLdap" />
      </div>

      <div class="flex justify-end gap-2 mt-2">
        <Button label="Retour" severity="secondary" text type="button" @click="step = 'recherche'" />
        <Button label="Annuler" severity="secondary" text type="button" @click="closeDialog" />
        <Button label="Créer" icon="pi pi-plus" type="submit" :loading="isSaving" :disabled="!isValid" />
      </div>
    </form>
  </Dialog>
</template>
