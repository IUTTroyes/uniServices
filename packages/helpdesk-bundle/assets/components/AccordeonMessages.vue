<script setup>
import { useRouter } from 'vue-router';
import TicketMessageCard from "@/components/TicketMessageCard.vue";

const props = defineProps({
  tickets: {
    type: Array,
    required: true
  }
});

const router = useRouter();

const goToTicket = (id) => {
  router.push({ name: 'TicketView', params: { id: id } });
};
</script>

<template>
  <div class="mb-8">
    <Accordion :pt="{ root: 'rounded-xl overflow-hidden' }">
      <AccordionPanel value="0">
        <AccordionHeader
            :pt="{
          root: '!bg-violet-600 transition-colors',
          toggleicon: '!text-violet-100',
        }"
        >

          <span class="font-bold text-white">
            {{ tickets.length }} Nouveaux messages
          </span>
        </AccordionHeader>

        <AccordionContent :pt="{ content: '!bg-white dark:!bg-zinc-900 !p-10' }">
          <div v-for="singleTicket in tickets" :key="singleTicket.id">
            <TicketMessageCard :ticket="singleTicket" @click="goToTicket(singleTicket.id)" />
          </div>
        </AccordionContent>
      </AccordionPanel>
    </Accordion>
  </div>
</template>