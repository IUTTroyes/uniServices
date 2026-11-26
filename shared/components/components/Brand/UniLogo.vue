<script setup>
import { computed } from 'vue';
import { UNI_BRAND, U_LEFT, U_RIGHT } from './uniBrand.js';

const props = defineProps({
  service: { type: String, default: 'service', validator: (v) => v in UNI_BRAND },
  variant: { type: String, default: 'lettre', validator: (v) => ['lettre', 'icone'].includes(v) },
  tagline: { type: Boolean, default: false },
  dark: { type: Boolean, default: false },
  /** true si le nom est déjà écrit à côté : le logo est alors ignoré des lecteurs d'écran */
  decorative: { type: Boolean, default: false },
});

const brand = computed(() => UNI_BRAND[props.service] ?? UNI_BRAND.service);
// Le « U » dessiné remplace la capitale des noms « Uni… » ; un autre nom est écrit en entier après la marque.
const startsWithUni = computed(() => brand.value.name.startsWith('Uni'));
const suffix = computed(() => (startsWithUni.value ? brand.value.name.slice(3) : brand.value.name));
const colors = computed(() => ({
  '--uni-ink': props.dark ? brand.value.colorOnDark : brand.value.color,
  '--uni-accent': props.dark ? brand.value.accentOnDark : brand.value.accent,
  '--uni-tagline': props.dark ? '#D9D2E8' : '#54486E',
}));
const label = computed(() =>
  props.tagline && props.variant === 'lettre' ? `${brand.value.name} – ${brand.value.tagline}` : brand.value.name,
);
const a11y = computed(() =>
  props.decorative ? { 'aria-hidden': 'true' } : { role: 'img', 'aria-label': label.value },
);
</script>

<template>
  <span
    v-if="variant === 'lettre'"
    class="uni-logo uni-logo--lettre"
    :class="{ 'uni-logo--tagline': tagline }"
    :style="colors"
    v-bind="a11y"
  >
    <span class="uni-logo__word" aria-hidden="true"><svg class="uni-logo__mark" viewBox="11 7 78 88" focusable="false"><path class="uni-logo__ink" :d="U_LEFT" /><path class="uni-logo__accent" :d="U_RIGHT" /></svg><template v-if="startsWithUni">ni</template><b>{{ suffix }}</b></span>
    <span v-if="tagline" class="uni-logo__tagline" aria-hidden="true">{{ brand.tagline }}</span>
  </span>

  <svg v-else class="uni-logo uni-logo--icone" viewBox="0 0 100 100" focusable="false" v-bind="a11y">
    <g v-if="!brand.pictogram" transform="translate(4 4)">
      <rect width="92" height="92" rx="22" :fill="brand.color" />
      <g transform="translate(13 12.5) scale(0.66)">
        <path :d="U_LEFT" fill="#FFFFFF" />
        <path :d="U_RIGHT" :fill="brand.accent" />
      </g>
    </g>
    <template v-else>
      <rect width="92" height="92" rx="22" :fill="brand.color" />
      <path
        transform="translate(18 18) scale(2.333)"
        :d="brand.pictogram"
        fill="none"
        stroke="#FFFFFF"
        stroke-width="1.9"
        stroke-linecap="round"
        stroke-linejoin="round"
      />
      <rect x="65" y="65" width="34" height="34" rx="10" fill="#FFFFFF" :stroke="brand.color" stroke-width="2" />
      <g transform="translate(70.5 70.1) scale(0.23)">
        <path :d="U_LEFT" :fill="brand.color" />
        <path :d="U_RIGHT" :fill="brand.accent" />
      </g>
    </template>
  </svg>
</template>

<style>
@font-face {
  font-family: 'Lexend Uni';
  font-weight: 400;
  font-display: swap;
  src: url('../../../fonts/lexend/lexend-latin-400-normal.woff2') format('woff2');
}
@font-face {
  font-family: 'Lexend Uni';
  font-weight: 700;
  font-display: swap;
  src: url('../../../fonts/lexend/lexend-latin-700-normal.woff2') format('woff2');
}
</style>

<style scoped>
.uni-logo--icone {
  display: inline-block;
  height: var(--uni-logo-height, 2.5rem);
  width: auto;
}
/* --uni-logo-height fixe la hauteur totale ; la taille du texte en découle. */
.uni-logo--lettre {
  display: inline-flex;
  flex-direction: column;
  align-items: flex-start;
  font-family: 'Lexend Uni', sans-serif;
  font-size: calc(var(--uni-logo-height, 2.5rem) * 0.86);
  line-height: 1.16;
  white-space: nowrap;
}
.uni-logo--tagline {
  font-size: calc(var(--uni-logo-height, 4rem) * 0.61);
}
.uni-logo__word {
  color: var(--uni-ink);
  font-size: 1em;
  font-weight: 400;
  text-transform: none;
}
.uni-logo__word b {
  font-weight: 700;
}
.uni-logo__mark {
  display: inline-block;
  width: 0.647em;
  height: 0.73em;
  margin-right: 0.05em;
  overflow: visible;
  vertical-align: -0.015em;
}
.uni-logo__ink {
  fill: var(--uni-ink);
}
.uni-logo__accent {
  fill: var(--uni-accent);
}
.uni-logo__tagline {
  color: var(--uni-tagline);
  font-size: 0.34em;
  line-height: 1.4;
  text-transform: none;
}
</style>
