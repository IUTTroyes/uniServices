<script setup>
import {ref} from "vue";

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

const tabs = ref([
    { title: 'Personnel', content: 'Ici mon emploi du temps personnel', value: '0' },
    { title: 'Département', content: 'Ici l\'emploi du temps du département', value: '1' },
]);
</script>

<template>
    <div class="grid grid-cols-1 gap-4">
        <div class="card h-full">
            <div class="font-semibold text-xl mb-4">Dernières actualités du département</div>
            <Stepper value="1">
                <StepItem value="1">
                    <Step>Header I</Step>
                    <StepPanel v-slot="{ activateCallback }">
                        <div class="flex flex-col h-48">
                            <div class="border-2 border-dashed border-surface-200 dark:border-surface-700 rounded bg-surface-50 dark:bg-surface-950 flex-auto flex justify-center items-center font-medium">Content I</div>
                        </div>
                        <div class="py-6">
                            <Button label="Next" @click="activateCallback('2')" />
                        </div>
                    </StepPanel>
                </StepItem>
                <StepItem value="2">
                    <Step>Header II</Step>
                    <StepPanel v-slot="{ activateCallback }">
                        <div class="flex flex-col h-48">
                            <div class="border-2 border-dashed border-surface-200 dark:border-surface-700 rounded bg-surface-50 dark:bg-surface-950 flex-auto flex justify-center items-center font-medium">Content II</div>
                        </div>
                        <div class="flex py-6 gap-2">
                            <Button label="Back" severity="secondary" @click="activateCallback('1')" />
                            <Button label="Next" @click="activateCallback('3')" />
                        </div>
                    </StepPanel>
                </StepItem>
                <StepItem value="3">
                    <Step>Header III</Step>
                    <StepPanel v-slot="{ activateCallback }">
                        <div class="flex flex-col h-48">
                            <div class="border-2 border-dashed border-surface-200 dark:border-surface-700 rounded bg-surface-50 dark:bg-surface-950 flex-auto flex justify-center items-center font-medium">Content III</div>
                        </div>
                        <div class="py-6">
                            <Button label="Back" severity="secondary" @click="activateCallback('2')" />
                        </div>
                    </StepPanel>
                </StepItem>
            </Stepper>
        </div>

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
                            <p class="m-0">{{ tab.content }}</p>
                        </TabPanel>
                    </TabPanels>
                </Tabs>
            </div>
        </div>
    </div>
</template>

<style scoped lang="scss">

</style>
