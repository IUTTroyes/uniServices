<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '@helpers/axios'
import { useToast } from 'primevue/usetoast'

const route = useRoute()
const router = useRouter()
const toast = useToast()

// Récupère la clé depuis l'URL (encodée)
const emailKey = computed(() => decodeURIComponent(route.params.key))
const departementId = computed(() => route.query.departement ? Number(route.query.departement) : null)

const loading = ref(true)
const saving = ref(false)
const deleting = ref(false)
const showDeleteConfirm = ref(false)

// Données du formulaire
const templateId = ref(null)
const subject = ref('')
const bodyHtml = ref('')
const defaultSubject = ref('')
const isCustomized = ref(false)
const availableVariables = ref({})
const departementLibelle = ref('')
const emailLabel = ref('')

// Chargement initial
onMounted(async () => {
  try {
    const params = departementId.value ? { departement: departementId.value } : {}
    const res = await api.get(`/api/email/templates/${encodeURIComponent(emailKey.value)}`, { params })
    const data = res.data

    templateId.value = data.id
    subject.value = data.subject
    bodyHtml.value = data.bodyHtml ?? getDefaultBodyHint()
    defaultSubject.value = data.defaultSubject ?? data.subject
    isCustomized.value = data.isCustomized
    availableVariables.value = data.availableVariables ?? {}
    emailLabel.value = data.emailKey

    if (departementId.value) {
      const deptRes = await api.get('/api/email/departements')
      const dept = deptRes.data.find(d => d.id === departementId.value)
      departementLibelle.value = dept?.libelle ?? `Département #${departementId.value}`
    }
  } catch (e) {
    toast.add({ severity: 'error', summary: 'Erreur', detail: 'Impossible de charger le modèle.', life: 3000 })
    console.error(e)
  } finally {
    loading.value = false
  }
})

const getDefaultBodyHint = () => {
  return `<p>Bonjour,</p>\n<p><!-- Corps de votre message ici --></p>`
}

// Sauvegarde
const save = async () => {
  if (!subject.value.trim() || !bodyHtml.value.trim()) {
    toast.add({ severity: 'warn', summary: 'Champs requis', detail: 'L\'objet et le corps sont obligatoires.', life: 3000 })
    return
  }
  saving.value = true
  try {
    const payload = {
      emailKey: emailKey.value,
      subject: subject.value,
      bodyHtml: bodyHtml.value,
      ...(departementId.value ? { departement: departementId.value } : {})
    }
    const res = await api.post('/api/email/templates', payload)
    templateId.value = res.data.id
    isCustomized.value = true
    toast.add({ severity: 'success', summary: 'Sauvegardé', detail: 'Le modèle a été enregistré.', life: 3000 })
  } catch (e) {
    toast.add({ severity: 'error', summary: 'Erreur', detail: 'La sauvegarde a échoué.', life: 3000 })
    console.error(e)
  } finally {
    saving.value = false
  }
}

// Réinitialisation (suppression de la personnalisation)
const resetToDefault = async () => {
  if (!templateId.value) return
  deleting.value = true
  try {
    await api.delete(`/api/email/templates/${templateId.value}`)
    templateId.value = null
    isCustomized.value = false
    subject.value = defaultSubject.value
    bodyHtml.value = ''
    showDeleteConfirm.value = false
    toast.add({ severity: 'info', summary: 'Réinitialisé', detail: 'Le modèle par défaut sera utilisé.', life: 3000 })
  } catch (e) {
    toast.add({ severity: 'error', summary: 'Erreur', detail: 'La suppression a échoué.', life: 3000 })
    console.error(e)
  } finally {
    deleting.value = false
  }
}

// Copie une variable dans le presse-papier
const copyVariable = (varName) => {
  const twig = `{{ ${varName} }}`
  navigator.clipboard.writeText(twig)
  toast.add({ severity: 'info', summary: 'Copié', detail: `{{ ${varName} }} copié dans le presse-papier`, life: 2000 })
}

const goBack = () => {
  const query = departementId.value ? { departement: departementId.value } : {}
  router.push({ name: 'emails-configuration', query })
}

// Insertion de snippets HTML dans le corps
const insertTag = (tag) => {
  bodyHtml.value += '<' + tag + '></' + tag + '>'
}
const insertParagraph = () => {
  bodyHtml.value += '\n<p></p>'
}
const insertButton = () => {
  bodyHtml.value += '\n<a href="#" class="btn">Texte du bouton</a>'
}
const insertAlert = () => {
  bodyHtml.value += '\n<div class="alert">Message d\'alerte</div>'
}
const insertDivider = () => {
  bodyHtml.value += '\n<hr class="divider">'
}

// Constantes pour afficher la syntaxe Twig sans que Vue ne l'interprète
const twigOpen = '{{ '
const twigClose = ' }}'
const twigVar = (name) => twigOpen + name + twigClose
</script>

<template>
  <Toast />
  <ConfirmDialog />

  <div class="mx-auto space-y-6">

    <!-- Header -->
    <div class="flex items-start justify-between border-b border-slate-100 dark:border-slate-800 pb-4">
      <div class="flex items-center gap-3">
        <button
          @click="goBack"
          class="w-8 h-8 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-500 hover:bg-slate-200 dark:hover:bg-slate-700 flex items-center justify-center transition-all"
        >
          <i class="pi pi-arrow-left text-xs"/>
        </button>
        <div>
          <h1 class="text-xl font-black text-slate-900 dark:text-white flex items-center gap-2">
            <i class="pi pi-envelope text-teal-600"/>
            <span>{{ emailLabel }}</span>
          </h1>
          <p class="text-xs text-slate-500 mt-0.5">
            <span v-if="departementLibelle">
              Personnalisation pour <strong class="text-slate-700 dark:text-slate-300">{{ departementLibelle }}</strong>
            </span>
            <span v-else>Personnalisation globale (tous départements)</span>
            <span class="mx-1.5">·</span>
            <code class="text-[10px] bg-slate-100 dark:bg-slate-800 px-1.5 py-0.5 rounded">{{ emailKey }}</code>
          </p>
        </div>
      </div>

      <!-- Status badge -->
      <div
        :class="[
          'px-3 py-1.5 rounded-full text-xs font-bold flex items-center gap-1.5',
          isCustomized
            ? 'bg-teal-100 dark:bg-teal-900/30 text-teal-700 dark:text-teal-400'
            : 'bg-slate-100 dark:bg-slate-800 text-slate-500'
        ]"
      >
        <i :class="isCustomized ? 'pi pi-check' : 'pi pi-file'"/>
        {{ isCustomized ? 'Personnalisé' : 'Par défaut' }}
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="flex justify-center py-20">
      <ProgressSpinner strokeWidth="3" class="w-12 h-12" />
    </div>

    <div v-else class="grid grid-cols-1 xl:grid-cols-3 gap-6">

      <!-- ── Left col: Editor ── -->
      <div class="xl:col-span-2 space-y-5">

        <!-- Default subject info -->
        <div
          v-if="!isCustomized"
          class="flex items-start gap-2 px-4 py-3 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800/40 rounded-xl text-xs text-amber-700 dark:text-amber-300"
        >
          <i class="pi pi-info-circle mt-0.5 shrink-0"/>
          <span>
            Aucune personnalisation enregistrée.
            <strong>Le template par défaut du module</strong> sera utilisé.
            Vous pouvez saisir ci-dessous pour créer une personnalisation.
          </span>
        </div>

        <!-- Subject field -->
        <div class="space-y-2">
          <label class="block text-xs font-bold text-slate-600 dark:text-slate-400">
            Objet du mail
            <span class="text-slate-400 font-normal ml-1">(supports {{ twigOpen }}variable{{ twigClose }})</span>
          </label>
          <InputText
            v-model="subject"
            class="w-full font-medium"
            :placeholder="defaultSubject"
          />
          <p class="text-[10px] text-slate-400">
            Valeur par défaut :
            <em class="text-slate-500 dark:text-slate-400">{{ defaultSubject }}</em>
          </p>
        </div>

        <!-- Body HTML field -->
        <div class="space-y-2">
          <label class="block text-xs font-bold text-slate-600 dark:text-slate-400">
            Corps du message
            <span class="text-slate-400 font-normal ml-1">
              (HTML + variables Twig — sera intégré dans le layout commun)
            </span>
          </label>

          <div class="border border-slate-200 dark:border-slate-700 rounded-xl overflow-hidden">
            <!-- Barre d'outils rapide -->
            <div class="flex items-center gap-1 px-3 py-2 bg-slate-50 dark:bg-slate-900 border-b border-slate-100 dark:border-slate-800">
              <span class="text-[10px] font-bold text-slate-400 mr-2">MISE EN FORME RAPIDE</span>
              <button
                v-for="tag in [['b', 'pi pi-bold'], ['i', 'pi pi-italic'], ['u', 'pi pi-underline']]"
                :key="tag[0]"
                @click="insertTag(tag[0])"
                class="w-6 h-6 rounded text-slate-500 hover:bg-slate-200 dark:hover:bg-slate-700 flex items-center justify-center transition-all"
                :title="tag[0].toUpperCase()"
              >
                <i :class="[tag[1], 'text-[10px]']"/>
              </button>
              <div class="h-4 w-px bg-slate-200 dark:bg-slate-700 mx-1"/>
              <button
                @click="insertParagraph"
                class="px-2 h-6 rounded text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700 text-[9px] font-mono transition-all"
              >&lt;p&gt;
              </button>
              <button
                @click="insertButton"
                class="px-2 h-6 rounded text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700 text-[9px] font-mono transition-all"
              >Bouton
              </button>
              <button
                @click="insertAlert"
                class="px-2 h-6 rounded text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700 text-[9px] font-mono transition-all"
              >Alerte
              </button>
              <button
                @click="insertDivider"
                class="px-2 h-6 rounded text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700 text-[9px] font-mono transition-all"
              >Séparateur
              </button>
            </div>

            <Textarea
              v-model="bodyHtml"
              :rows="18"
              class="w-full font-mono text-xs rounded-none border-0 !ring-0 resize-y bg-white dark:bg-slate-950"
              placeholder="<p>Bonjour {{ user.prenom }},</p>&#10;<p>...</p>"
            />
          </div>
          <p class="text-[10px] text-slate-400">
            Saisissez du HTML. Utilisez les variables disponibles avec la syntaxe
            <code class="bg-slate-100 dark:bg-slate-800 px-1 py-0.5 rounded">{{ twigOpen }}variable{{ twigClose }}</code>.
            Ce contenu sera automatiquement intégré dans le layout email commun (header, footer).
          </p>
        </div>

        <!-- Preview panel (static) -->
        <div class="space-y-2">
          <div class="text-xs font-bold text-slate-600 dark:text-slate-400 flex items-center gap-2">
            <i class="pi pi-eye"/>
            Aperçu brut (HTML non rendu)
          </div>
          <div
            class="border border-slate-100 dark:border-slate-800 rounded-xl p-4 bg-slate-50/50 dark:bg-slate-900/50 max-h-48 overflow-y-auto"
            v-html="bodyHtml || '<em class=\'text-slate-400\'>Aucun contenu saisi</em>'"
          />
        </div>

        <!-- Actions -->
        <div class="flex items-center justify-between pt-2 border-t border-slate-100 dark:border-slate-800">
          <button
            v-if="isCustomized && templateId"
            @click="showDeleteConfirm = true"
            class="text-xs text-rose-500 hover:text-rose-700 hover:bg-rose-50 dark:hover:bg-rose-950/20 px-3 py-2 rounded-lg transition-all flex items-center gap-1.5"
          >
            <i class="pi pi-undo"/>
            Réinitialiser au modèle par défaut
          </button>
          <div v-else/>

          <Button
            label="Enregistrer"
            icon="pi pi-save"
            class="bg-teal-600 border-none rounded-xl text-white hover:bg-teal-700"
            :loading="saving"
            @click="save"
          />
        </div>
      </div>

      <!-- ── Right col: Variable helper ── -->
      <div class="space-y-4">
        <div class="bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800 rounded-2xl p-4 sticky top-4">
          <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100 mb-1 flex items-center gap-2">
            <i class="pi pi-code text-violet-500"/>
            Variables disponibles
          </h3>
          <p class="text-[10px] text-slate-400 mb-4">
            Cliquez sur une variable pour la copier dans le presse-papier sous forme
            <code class="bg-slate-100 dark:bg-slate-800 px-1 rounded">{{ twigOpen }} … {{ twigClose }}</code>.
          </p>

          <div v-if="Object.keys(availableVariables).length === 0" class="text-[11px] text-slate-400 italic py-3 text-center">
            Aucune variable déclarée pour ce modèle.
          </div>

          <div v-else class="space-y-2">
            <div
              v-for="(desc, varName) in availableVariables"
              :key="varName"
              @click="copyVariable(varName)"
              class="group flex flex-col gap-0.5 p-2.5 bg-slate-50 dark:bg-slate-800/50 hover:bg-violet-50 dark:hover:bg-violet-900/20 border border-slate-100 dark:border-slate-700 hover:border-violet-200 dark:hover:border-violet-800/40 rounded-xl cursor-pointer transition-all"
            >
              <div class="flex items-center justify-between">
                <code class="text-[11px] font-bold text-violet-600 dark:text-violet-400" v-text="twigVar(varName)"/>
                <i class="pi pi-copy text-[9px] text-slate-300 group-hover:text-violet-500 transition-all"/>
              </div>
              <span class="text-[10px] text-slate-500 dark:text-slate-400 leading-relaxed">{{ desc }}</span>
            </div>
          </div>

          <!-- Aide sur les filtres Twig autorisés -->
          <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-800">
            <div class="text-[10px] font-bold text-slate-400 mb-2">FILTRES TWIG AUTORISÉS</div>
            <div class="flex flex-wrap gap-1.5">
              <code
                v-for="f in ['|upper', '|lower', '|date(\'d/m/Y\')', '|trim', '|length', '|default(\'…\')', '|nl2br']"
                :key="f"
                @click="copyVariable(f.slice(1))"
                class="text-[9px] bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 px-1.5 py-0.5 rounded cursor-pointer hover:bg-violet-100 dark:hover:bg-violet-900/30 hover:text-violet-600 transition-all"
              >{{ f }}</code>
            </div>
          </div>

          <!-- Aide conditions Twig -->
          <div class="mt-3 pt-3 border-t border-slate-100 dark:border-slate-800">
            <div class="text-[10px] font-bold text-slate-400 mb-2">CONDITIONS</div>
            <pre v-pre class="text-[9px] text-slate-500 dark:text-slate-400 bg-slate-50 dark:bg-slate-800 p-2 rounded-lg overflow-x-auto">{% if variable %}
  …
{% endif %}</pre>
          </div>

          <!-- Aide boucles Twig -->
          <div class="mt-3 pt-3 border-t border-slate-100 dark:border-slate-800">
            <div class="text-[10px] font-bold text-slate-400 mb-2">BOUCLES</div>
            <pre v-pre class="text-[9px] text-slate-500 dark:text-slate-400 bg-slate-50 dark:bg-slate-800 p-2 rounded-lg overflow-x-auto">{% for item in liste %}
  {{ item.nom }}
{% endfor %}</pre>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Confirm dialog réinitialisation -->
  <Dialog
    v-model:visible="showDeleteConfirm"
    modal
    header="Réinitialiser au modèle par défaut ?"
    :style="{ width: '420px' }"
  >
    <div class="space-y-3 py-2 text-sm">
      <p class="text-slate-600 dark:text-slate-400">
        Cette action supprimera votre personnalisation pour
        <strong>{{ departementLibelle || 'tous les départements' }}</strong>.
        Le template par défaut de l'application sera à nouveau utilisé.
      </p>
      <div class="flex items-center gap-2 p-3 bg-amber-50 dark:bg-amber-900/20 border border-amber-200/60 rounded-xl text-xs text-amber-700">
        <i class="pi pi-exclamation-triangle"/>
        Cette action est irréversible (vous pouvez toutefois créer une nouvelle personnalisation à tout moment).
      </div>
    </div>
    <template #footer>
      <Button label="Annuler" class="p-button-text" @click="showDeleteConfirm = false" />
      <Button
        label="Réinitialiser"
        icon="pi pi-undo"
        severity="danger"
        :loading="deleting"
        @click="resetToDefault"
      />
    </template>
  </Dialog>
</template>

<style scoped>
:deep(.p-textarea) {
  border-radius: 0;
  border: none;
}
</style>
