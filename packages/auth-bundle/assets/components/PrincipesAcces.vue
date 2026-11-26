<script setup>
import { Card } from '@components';
import { PRINCIPES } from '@utils/aideAcces';
</script>

<template>
    <Card title="Comment ça marche" icon="pi pi-info-circle" color="blue">
        <ul class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <li v-for="p in PRINCIPES" :key="p.titre" class="flex gap-3">
                <i :class="p.icone" class="text-blue-600 dark:text-blue-400 mt-1" aria-hidden="true"></i>
                <div>
                    <p class="font-semibold text-sm">{{ p.titre }}</p>
                    <p class="text-sm text-muted-color">{{ p.texte }}</p>
                </div>
            </li>
        </ul>
    </Card>
</template>
