<script setup>
import { computed } from 'vue';
import { urlFichier } from '../../requests/traceService';
import { couleurCompetence, texteSurCompetence, urlIntegrable } from '../traces/options';

/** Une trace dans un portfolio : carte compacte ou vue détaillée avec ses preuves. */
const props = defineProps({
  trace: { type: Object, required: true },
  detail: { type: Boolean, default: false },
});

const vignette = computed(() => urlFichier(props.trace.vignette));
const date = computed(() => (props.trace.dateRealisation ? new Date(props.trace.dateRealisation).toLocaleDateString('fr-FR', { month: 'long', year: 'numeric' }) : null));
const integre = (m) => (m.type === 'embed' ? urlIntegrable(m.url) : null);
</script>

<template>
  <article class="rounded-xl border border-surface-200 overflow-hidden flex" :class="detail ? 'flex-col' : 'flex-col h-full'">
    <div v-if="vignette && !detail" class="h-32 bg-surface-100 overflow-hidden">
      <img :src="vignette" alt="" class="w-full h-full object-cover" loading="lazy" />
    </div>
    <div class="p-3 flex flex-col gap-2 flex-1">
      <h4 class="m-0 text-base font-semibold">{{ trace.titre }}</h4>
      <p class="m-0 text-xs text-muted-color">{{ [trace.typeActivite?.libelle, date].filter(Boolean).join(' · ') }}</p>
      <div v-if="trace.competences.length" class="flex flex-wrap gap-1">
        <span
          v-for="c in trace.competences"
          :key="c.id"
          class="text-xs font-medium px-2 py-0.5 rounded-full"
          :style="{ background: couleurCompetence(c.couleur), color: texteSurCompetence(c.couleur) }"
        >{{ c.nomCourt }}</span>
      </div>

      <template v-if="detail">
        <p v-if="trace.contexte" class="m-0 text-sm whitespace-pre-line">{{ trace.contexte }}</p>
        <div v-for="m in trace.medias" :key="m.id" class="flex flex-col gap-1">
          <img v-if="m.type === 'image' && m.fichier" :src="urlFichier(m.fichier.url)" :alt="m.legende || m.fichier.nom" class="rounded-lg max-w-full" loading="lazy" />
          <iframe v-else-if="integre(m)" :src="integre(m)" :title="m.legende || 'Contenu intégré'" class="w-full aspect-video rounded-lg border-0" sandbox="allow-scripts allow-same-origin allow-presentation allow-popups" allowfullscreen referrerpolicy="no-referrer" />
          <a v-else-if="m.fichier" :href="urlFichier(m.fichier.url, true)" class="text-sm text-primary"><i :class="m.icone" /> {{ m.fichier.nom }}</a>
          <a v-else-if="m.url" :href="m.url" target="_blank" rel="noopener noreferrer nofollow" class="text-sm text-primary break-all"><i :class="m.icone" /> {{ m.url }}</a>
          <small v-if="m.legende" class="text-muted-color">{{ m.legende }}</small>
        </div>
      </template>
    </div>
  </article>
</template>
