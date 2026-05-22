<script setup>
import {ValidatedInput, validationRules} from "@components";
import {getServicesService} from "@requests";
import {createTicketService} from '@requests'
import {ref,onMounted,computed} from "vue";
import {useRouter} from "vue-router";
import CascadeSelect from 'primevue/cascadeselect';

const router=useRouter()

const services=ref([])
const selectedService=ref(null)
const selectedCategorie=ref(null)
const fileName=ref(null)
const selectedSujet=ref("")
const selectedMessage=ref("")

const getServices= async()=>{
  try{
    services.value=await getServicesService({},'/form_ticket')
  }
  catch(error){
    console.error('Erreur dans getServices',error);
  }
  finally {
    console.log(services.value)
  }
}

const onUpload = (event) =>{
  const response = JSON.parse(event.xhr.response)
  fileName.value = response.filename
}
const rootCategories = computed(() => {
  if (!selectedService.value?.helpdeskCategories) return [];
  return selectedService.value.helpdeskCategories.filter(cat => !cat.parent);
});

const createTicket = async () => {
  let categoryId = null;
  if (selectedCategorie.value) {
    categoryId = typeof selectedCategorie.value === 'object'
        ? selectedCategorie.value.value
        : selectedCategorie.value;
  }
  const payload = {
    subject: selectedSujet.value,
    description: selectedMessage.value,
    service: selectedService.value ? selectedService.value['@id'] : null,
    category: categoryId ? `/api/helpdesk_categories/${categoryId}` : null,
    helpdeskCategorie: categoryId ? `/api/helpdesk_categories/${categoryId}` : null,
    file: fileName.value
  };

  console.log('Payload final envoyé:', payload);
  console.log(payload.description)

  try {
    await createTicketService(payload, true);
    router.push({ name: 'Dashboard' });
  }
  catch (error) {
    console.error('Erreur lors de la création du ticket', error);
  }
};

onMounted(async()=>{
  await getServices()
})
</script>

<template>
  <div class="card">
    <Message severity="info" icon="pi pi-info-circle" class="mt-2 mb-10">Nous traitons actuellement un volume élevé de tickets. Merci de limiter vos ouvertures de tickets aux besoins essentiels afin de nous aider à réduire les délais de réponse.
    </Message>
    <div>
      <div class="font-semibold text-xl mb-6">Créer un ticket</div>
    </div>
    <form @submit.prevent="createTicket()">
    <div class="flex flex-col gap-2 pb-6">
      <ValidatedInput
        v-model="selectedService"
        :options="(services.map(service=>({label:service.libelle,value:service})))"
        name="services"
        type="select"
        label="Services"
        :rules="[]"
        class=""
        :show-clear="true"
        ></ValidatedInput>

    </div>
    <div class="flex flex-col gap-2 pb-6 w-100">

      <CascadeSelect
          v-model="selectedCategorie"
          :options="rootCategories"
          optionLabel="libelle"
          optionGroupLabel="libelle"
          :optionGroupChildren="['enfants']"
          optionValue="id"
          label="Catégorie"
          class="w-full"
          placeholder="Sélectionnez une catégorie"
          disabled:!selectedService/>
    </div>
    <div class="flex flex-col gap-2 pb-6">
        <ValidatedInput
          v-model="selectedSujet"
          name="sujet"
          type="text"
          :rules="[]"
          label="Sujet"
        ></ValidatedInput>
    </div>
    <div class="flex flex-col gap-2 pb-6">
        <ValidatedInput
            v-model="selectedMessage"
            name="message"
            type="text"
            :rules="[]"
            label="Message"
        ></ValidatedInput>
    </div>
    <div class="pb-6">

    <FileUpload ref="fileupload"  name="demo[]" url="/api/upload" accept="image/*,video/*,text/plain,.txt,.pdf" :maxFileSize="1000000" @upload="onUpload" >
      <template #empty>
        Joindre un fichier
      </template>
    </FileUpload>
    </div>
    <div class="flex justify-end">
      <Button class="w-60 !bg-blue-400" type="submit" severity="info" label="Envoyer" />
    </div>
    </form>
  </div>

</template>

<style scoped>

</style>