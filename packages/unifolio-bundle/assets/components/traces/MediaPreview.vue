<script setup>
import { computed } from 'vue';
import { urlFichier } from '../../requests/traceService';
import { formatTaille, urlIntegrable } from './options';

const props = defineProps({
  media: { type: Object, required: true },
  compact: { type: Boolean, default: false },
});

const src = computed(() => urlFichier(props.media.fichier?.url));
const telechargement = computed(() => urlFichier(props.media.fichier?.url, true));
const embed = computed(() => (props.media.type === 'embed' ? urlIntegrable(props.media.url) : null));
</script>

<template>
  <figure class="flex flex-col gap-2 m-0">
    <img
      v-if="media.type === 'image' && src"
      :src="src"
      :alt="media.legende || media.fichier?.nom || 'Image'"
      class="rounded-lg max-w-full object-contain bg-surface-100"
      :class="compact ? 'max-h-40' : 'max-h-[32rem]'"
      loading="lazy"
    />
    <video v-else-if="media.type === 'video' && src" :src="src" controls preload="metadata" class="rounded-lg max-w-full" :class="compact ? 'max-h-40' : 'max-h-[32rem]'" />
    <iframe
      v-else-if="media.type === 'pdf' && src && !compact"
      :src="src"
      :title="media.legende || media.fichier?.nom || 'PDF'"
      class="w-full h-[32rem] rounded-lg border border-surface-200"
    />
    <iframe
      v-else-if="embed && !compact"
      :src="embed"
      :title="media.legende || 'Contenu intégré'"
      class="w-full aspect-video rounded-lg border-0"
      sandbox="allow-scripts allow-same-origin allow-presentation allow-popups"
      allowfullscreen
      referrerpolicy="no-referrer"
    />
    <pre
      v-else-if="media.type === 'code'"
      class="rounded-lg bg-surface-900 text-surface-0 p-3 text-sm overflow-auto"
      :class="compact ? 'max-h-40' : 'max-h-[32rem]'"
    ><code>{{ media.contenu }}</code></pre>
    <p v-else-if="media.type === 'texte'" class="whitespace-pre-line m-0" :class="compact ? 'line-clamp-4' : ''">{{ media.contenu }}</p>
    <a
      v-else-if="media.url"
      :href="media.url"
      target="_blank"
      rel="noopener noreferrer nofollow"
      class="inline-flex items-center gap-2 text-primary break-all"
    >
      <i :class="media.icone" /> {{ media.url }}
    </a>
    <a v-else-if="telechargement" :href="telechargement" class="inline-flex items-center gap-2 text-primary">
      <i :class="media.icone" /> {{ media.fichier?.nom }}
      <span class="text-muted-color text-sm">({{ formatTaille(media.fichier?.taille) }})</span>
    </a>

    <figcaption v-if="media.legende" class="text-sm text-muted-color">{{ media.legende }}</figcaption>
    <a
      v-if="!compact && telechargement && media.type !== 'fichier'"
      :href="telechargement"
      class="text-sm text-primary inline-flex items-center gap-1"
    >
      <i class="pi pi-download" /> Télécharger {{ media.fichier?.nom }}
    </a>
  </figure>
</template>
