<script setup>
// requête 3 cours à venir
const events = [
    {
        debut: '10/12/2024 08:00',
        fin: '10/12/2024 09:30',
        text: 'WS301D - Développer des parcours utilisateur au sein d\'un système d\'information',
        color: '#FBE4EE',
        colorFocus: '#fbc6e3',
        semestre: 'S3 Strat-UX Alt',
        groupe: 'CM',
        groupeColor: '#1f4ea6',
        salle: 'H018'
    },
    {
        debut: '10/12/2024 09:30',
        fin: '10/12/2024 11:00',
        text: 'WS301D - Développer des parcours utilisateur au sein d\'un système d\'information',
        color: '#FBE4EE',
        colorFocus: '#fbc6e3',
        semestre: 'S3 Strat-UX Alt',
        groupe: 'CM',
        groupeColor: '#1f4ea6',
        salle: 'H018'
    }
]
</script>

<template>
    <div class="flex flex-row justify-between gap-4">
        <div v-for="(event, index) in events" class="w-full">
            <div class="flex flex-row justify-between items-center p-4 bg-white rounded-md">
                <div class="flex flex-col">
                    <div class="text-sm text-gray-500">{{ event.debut }} - {{ event.fin }}</div>
                    <div class="text-lg font-semibold">{{ event.text }}</div>
                    <div class="text-sm text-gray-500">{{ event.semestre }}</div>
                </div>
                <div class="flex flex-col items-end">
                    <div class="flex flex-row items-center">
                        <div class="w-4 h-4 rounded-full bg-gray-200"></div>
                        <div class="text-sm text-gray-500 ml-2">{{ event.groupe }}</div>
                    </div>
                    <div class="flex flex-row items-center">
                        <div class="w-4 h-4 rounded-full bg-gray-200"></div>
                        <div class="text-sm text-gray-500 ml-2">{{ event.salle }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
<style scoped>

</style>
