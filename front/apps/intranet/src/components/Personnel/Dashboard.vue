<script setup>
import {ref} from "vue";

import Edt from "@/components/Edt.vue";

const menu = ref();
const items = ref([
    {
        label: 'Options',
        items: [
            {
                label: 'Lien synchronisation ICAL',
                icon: 'pi pi-calendar'
            },
            {
                label: 'Chronologique',
                icon: 'pi pi-clock'
            }
        ]
    }
]);

const toggle = (event) => {
    menu.value.toggle(event);
};

const tabs = [
    { title: 'Personnel', content: Edt, value: '0', data: { type: 'personnel', info: 'Données pour le personnel' } },
    { title: 'Département', content: Edt, value: '1', data: { type: 'departement', info: 'Données pour le département' } },
];
</script>

<template>
    <div class="grid grid-cols-1 gap-4">

        <div class="card h-full">
            <div class="card-header flex justify-between items-start">
                <div class="font-semibold text-xl mb-4">Emploi du temps</div>
                <div class="flex gap-2">
                    <Button label="Vue Liste" />
                    <Button type="button" severity="secondary" icon="pi pi-ellipsis-v" @click="toggle" aria-haspopup="true" aria-controls="overlay_menu" />
                    <Menu ref="menu" id="overlay_menu" :model="items" :popup="true" />
                </div>
            </div>
            <div class="card-body">
                <div class="w-full flex justify-center mt-5 gap-5">
                    <Button icon="pi pi-arrow-left" severity="secondary" aria-label="previousWeek" />
                    <div class="flex flex-col">
                        <Tag value="Semaine 49"></Tag>
                        <small>Semaine de formation 15</small>
                    </div>
                    <Button icon="pi pi-arrow-right" severity="secondary" aria-label="nextWeek" />
                </div>

                <Tabs value="0">
                    <TabList>
                        <Tab v-for="tab in tabs" :key="tab.title" :value="tab.value">{{ tab.title }}</Tab>
                    </TabList>
                    <TabPanels>
                        <TabPanel v-for="tab in tabs" :key="tab.content" :value="tab.value">
                            <component :is="tab.content" :data="tab.data" />
                        </TabPanel>
                    </TabPanels>
                </Tabs>
            </div>
        </div>
    </div>
</template>

<style scoped lang="scss">

</style>
