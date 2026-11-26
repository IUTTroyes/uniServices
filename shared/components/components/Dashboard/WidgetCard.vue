<script setup>
import { computed, defineProps, ref } from 'vue';
import { resolveWidgetComponent } from './widgets/widgetRegistry';
import ApplicationBadge from '../Application/ApplicationBadge.vue';
import { getApplicationMeta } from '@utils/applicationColors';

const isEditing = ref(false);

const props = defineProps({
    widget: {
        type: Object,
        required: true
    },
    data: {
        type: Object,
        required: true
    },
    first: {
        type: Boolean,
        default: false
    },
    last: {
        type: Boolean,
        default: false
    },
    isPortail: {
        type: Boolean,
        default: false
    }
});

const emit = defineEmits(['move', 'updateSpan', 'toggle']);

const gridStyle = computed(() => ({
    gridColumn: `span ${props.widget.colSpan || 1}`,
    gridRow: `span ${props.widget.rowSpan || 1}`,
}));

const resolvedComponent = computed(() => resolveWidgetComponent(props.widget?.component));

const moveWidget = (direction) => {
    emit('move', props.widget, direction);
};

const increaseColSpan = () => {
    const newColSpan = Math.min(4, (props.widget.colSpan || 1) + 1);
    emit('updateSpan', props.widget, 'colSpan', newColSpan);
};

const decreaseColSpan = () => {
    const newColSpan = Math.max(1, (props.widget.colSpan || 1) - 1);
    emit('updateSpan', props.widget, 'colSpan', newColSpan);
};

const increaseRowSpan = () => {
    emit('updateSpan', props.widget, 'rowSpan', (props.widget.rowSpan || 1) + 1);
};

const decreaseRowSpan = () => {
    const newRowSpan = Math.max(1, (props.widget.rowSpan || 1) - 1);
    emit('updateSpan', props.widget, 'rowSpan', newRowSpan);
};

const toggleWidget = () => {
    emit('toggle', props.widget);
};

// Application d'origine du widget : les codes du serveur (« portfolio », « document ») sont résolus par les alias.
const application = computed(() => (props.widget?.bundle ? getApplicationMeta(props.widget.bundle) : null));

const showBundleBadge = computed(() => {
    if (!application.value) return false;
    // Sur le portail, on n'affiche pas le badge pour les widgets natifs du portail ;
    // sur un tableau de bord dédié (ex : intranet), seulement pour les widgets venus d'une autre application.
    return application.value.slug !== (props.isPortail ? 'auth' : 'intranet');
});
</script>

<template>
    <article
    :key="widget.code"
    :style="gridStyle"
    class="card m-0! lg:p-6! p-4!"
    >
    <div v-if="isEditing">
        <div class="flex justify-between">
            <div class="flex items-center gap-1">
                <Button icon="pi pi-plus" size="small" text rounded title="Augmenter largeur" @click="increaseColSpan"/>
                <Button icon="pi pi-minus" size="small" text rounded title="Diminuer largeur" @click="decreaseColSpan"/>
                <Button icon="pi pi-chevron-down" size="small" text rounded title="Augmenter hauteur" @click="increaseRowSpan"/>
                <Button icon="pi pi-chevron-up" size="small" text rounded title="Diminuer hauteur" @click="decreaseRowSpan"/>
                <Button v-if="!first" icon="pi pi-arrow-left" size="small" title="Déplacer sur la gauche" text rounded severity="secondary" @click="moveWidget(-1)"/>
                <Button v-if="!last" icon="pi pi-arrow-right" size="small" title="Déplacer sur la droite" text rounded severity="secondary" @click="moveWidget(1)"/>
                <Button icon="pi pi-trash" size="small" title="Retirer le widget" text rounded severity="danger" @click="toggleWidget()"/>
            </div>
            <Button icon="pi pi-times" size="small" text rounded title="Fermer le panneau de configuration" @click="isEditing = !isEditing"/>
        </div>
        <Divider/>
    </div>
    <div class="mb-3 flex items-start justify-between gap-2">
        <div class="flex flex-col">
            <div class="font-semibold flex items-start gap-2">
                <i :class="[widget.icon, application?.textClass ?? 'text-primary-500']" aria-hidden="true"/>
                <span>{{ widget.label }}</span>
            </div>
            <div v-if="showBundleBadge" class="mt-1">
                <ApplicationBadge :app="widget.bundle" size="xs" />
            </div>
        </div>
        <Button v-if="!isEditing" icon="pi pi-cog" size="small" text rounded title="Configurer le widget" @click="isEditing = !isEditing"/>
    </div>
    <component :is="resolvedComponent" :data="data" :widget="widget" />
</article>
</template>
