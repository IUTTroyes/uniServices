<script setup>
import { computed, ref } from 'vue';
import { ValidatedInput, validateField, validationRules } from '@components';

const props = defineProps({
  champs: Array,
  erreur: String,
  sauvegarde: Boolean,
  libelleAction: { type: String, default: 'Enregistrer' },
});
const donnees = defineModel({ type: Object, required: true });
const emit = defineEmits(['submit']);
const erreurValidation = ref('');

const champsVisibles = computed(() => props.champs.filter(champ => !champ.visible || champ.visible(donnees.value)));

function regles(champ) {
  const resultat = [];
  if (champ.requis || champ.requisSi?.(donnees.value)) {
    resultat.push(validationRules.required, {
      validate: valeur => typeof valeur !== 'string' || valeur.trim() !== '',
      message: validationRules.required.message,
    });
  }
  if (champ.max && champ.type !== 'number') resultat.push(validationRules.maxLength(champ.max));
  if (champ.type === 'number') {
    const minimum = champ.min ?? 0;
    if (!champ.decimales) resultat.push(validationRules.numeric);
    resultat.push({
      validate: valeur => Number(valeur) >= minimum,
      message: `La valeur doit être supérieure ou égale à ${minimum}`,
    }, validationRules.maxValue(champ.max ?? 2147483647));
  }

  return resultat;
}

function soumettre() {
  for (const champ of champsVisibles.value) {
    if (champ.lecture) continue;
    const validation = validateField(donnees.value[champ.nom], regles(champ));
    if (!validation.isValid) {
      erreurValidation.value = champ.label + ' · ' + validation.errorMessage;
      return;
    }
  }

  erreurValidation.value = '';
  emit('submit');
}
</script>

<template>
  <form class="grid gap-4" @submit.prevent="soumettre">
    <Message v-if="erreurValidation || erreur" severity="error">{{ erreurValidation || erreur }}</Message>
    <slot />
    <template v-for="champ in champsVisibles" :key="champ.nom">
      <label v-if="champ.type === 'boolean'" :for="'parcauto-' + champ.nom" class="grid gap-1 text-sm">
        {{ champ.label }}
        <ToggleSwitch v-model="donnees[champ.nom]" :input-id="'parcauto-' + champ.nom" :disabled="champ.lecture" />
      </label>
      <label v-else-if="champ.type === 'multiple'" :for="'parcauto-' + champ.nom" class="grid gap-1 text-sm">
        {{ champ.label }}
        <MultiSelect v-model="donnees[champ.nom]" :input-id="'parcauto-' + champ.nom" :options="champ.options" option-label="label"
                     option-value="value" :option-disabled="champ.optionDisabled" placeholder="Aucun équipement" display="chip" />
      </label>
      <ValidatedInput v-else v-model="donnees[champ.nom]"
                      :name="'parcauto-' + champ.nom" :label="champ.label"
                      :type="champ.options ? 'select' : champ.type ?? 'text'"
                      :options="champ.options" :option-disabled="champ.optionDisabled"
                      :rules="champ.lecture ? [] : regles(champ)" :disabled="champ.lecture"
                      :min="champ.min ?? 0" :max="champ.type === 'number' ? champ.max ?? 2147483647 : null"
                      :maxfractiondigits="champ.decimales ?? 0" :show-clear="!(champ.requis || champ.requisSi?.(donnees))"
                      @update:model-value="erreurValidation = ''" />
    </template>
    <Button type="submit" :label="libelleAction" :loading="sauvegarde" />
  </form>
</template>
