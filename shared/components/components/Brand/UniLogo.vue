<script setup>
import { computed } from 'vue'
import { UNI_BRAND, U_LEFT, U_RIGHT } from './uniBrand.js'

const props = defineProps({
  service: { type: String, default: 'service', validator: (v) => v in UNI_BRAND },
  variant: { type: String, default: 'lettre', validator: (v) => ['lettre', 'icone'].includes(v) },
  tagline: { type: Boolean, default: false },
  dark: { type: Boolean, default: false },
  /** true si le nom est déjà écrit à côté : le logo est alors ignoré des lecteurs d'écran */
  decorative: { type: Boolean, default: false },
})

const brand = computed(() => UNI_BRAND[props.service])
const ink = computed(() => (props.dark ? brand.value.onDark : brand.value.primary))
const right = computed(() => (props.dark ? brand.value.accentOnDark : brand.value.accent))
const label = computed(() =>
  props.tagline && props.variant === 'lettre' ? `${brand.value.name} – ${brand.value.tagline}` : brand.value.name,
)
const viewBox = computed(() =>
  props.variant === 'icone' ? '0 0 100 100' : brand.value.lettre.viewBox[props.tagline ? 'tagline' : 'simple'],
)
</script>

<template>
  <svg
    class="uni-logo"
    :class="{ 'uni-logo--tagline': tagline && variant === 'lettre' }"
    :viewBox="viewBox"
    :role="decorative ? undefined : 'img'"
    :aria-label="decorative ? undefined : label"
    :aria-hidden="decorative ? 'true' : undefined"
    focusable="false"
  >
    <template v-if="variant === 'lettre'">
      <g :transform="brand.lettre.transform">
        <path :d="U_LEFT" :fill="ink" />
        <path :d="U_RIGHT" :fill="right" />
      </g>
      <path :d="brand.lettre.ni + brand.lettre.suffix" :fill="ink" />
      <path v-if="tagline" :d="brand.lettre.tag" :fill="dark ? '#D9D2E8' : '#54486E'" />
    </template>

    <template v-else-if="service === 'service'">
      <g transform="translate(4 4)">
        <rect width="92" height="92" rx="22" :fill="brand.primary" />
        <g transform="translate(13 12.5) scale(0.66)">
          <path :d="U_LEFT" fill="#FFFFFF" />
          <path :d="U_RIGHT" fill="#F9B000" />
        </g>
      </g>
    </template>

    <template v-else>
      <rect width="92" height="92" rx="22" :fill="brand.primary" />
      <path
        transform="translate(18 18) scale(2.333)"
        :d="brand.icon"
        fill="none"
        stroke="#FFFFFF"
        stroke-width="1.9"
        stroke-linecap="round"
        stroke-linejoin="round"
      />
      <rect x="65" y="65" width="34" height="34" rx="10" fill="#FFFFFF" :stroke="brand.primary" stroke-width="2" />
      <g transform="translate(70.5 70.1) scale(0.23)">
        <path :d="U_LEFT" :fill="brand.primary" />
        <path :d="U_RIGHT" :fill="brand.accent" />
      </g>
    </template>
  </svg>
</template>

<style scoped>
.uni-logo {
  display: inline-block;
  height: var(--uni-logo-height, 2.5rem);
  width: auto;
}
.uni-logo--tagline {
  height: var(--uni-logo-height, 4rem);
}
</style>
