<script setup>
import {ValidatedInput, validationRules} from "@components";
import {getServicesService} from "@requests";
import {getCategoriesService} from "@requests";
import {ref,onMounted} from "vue";

const createTicket=async()=>{
  console.log('test')
}

const services=ref([])
const selectedService=ref(null)
const categories=ref([])
const selectedCategorie=ref(null)

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

const getCategories= async()=>{
  try{
    categories.value=await getCategoriesService({},'/form_ticket')
  }
  catch(error){
    console.error('Erreur dans getCategories',error);
  }
  finally {
    console.log(categories.value)
  }
}


onMounted(async()=>{
  await getServices()
  await getCategories()
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
        :options="(services.map(service=>({label:service.libelle,value:service.id})))"
        name="services"
        type="select"
        label="Services"
        :rules="[]"
        class=""
        :show-clear="false"
        ></ValidatedInput>

    </div>
    <div class="flex flex-col gap-2 pb-6">
      <ValidatedInput
          v-model="selectedCategorie"
          :options="(categories.map(service=>({label:categorie.libelle,value:categorie.id})))"
          name="categories"
          type="select"
          label="Categories"
          :rules="[]"
          class=""
          :show-clear="false"
      ></ValidatedInput>
    </div>
    <div class="flex flex-col gap-2 pb-6">
      <FloatLabel variant="in">
        <Textarea id="in_label" v-model="value2" rows="1" cols="150" style="resize: none" />
        <label for="in_label">Sujet</label>
      </FloatLabel>
    </div>
    <div class="flex flex-col gap-2 pb-6">
      <FloatLabel variant="in">
        <Textarea id="in_label" v-model="value2" rows="5" cols="150" style="resize: none" />
        <label for="in_label">Message</label>
      </FloatLabel>
    </div>
    <div class="pb-6">

    <FileUpload ref="fileupload"  name="demo[]" url="/api/upload" accept="image/*,video/*,text/plain,.txt,.odt,.pdf" :maxFileSize="1000000" @upload="onUpload" >
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