<script setup>
import {ValidatedInput, validationRules} from "@components";
import {getServicesService} from "@requests";
import {ref,onMounted} from "vue";

const createTicket=async()=>{
  console.log('test')
}

const services=ref([])

const getServices= async()=>{
  try{
    services.value=await getServicesService()
  }
  catch(error){
    console.error('Erreur dans getServices',error);
  }
  finally {
    console.log(services.value)
  }
}

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
      <label for="service">Sélectionner un service</label>
      <Select id="service" v-model="selectedService" :options="a" optionLabel="name" placeholder="Selectionnez un service" class="w-full md:w-70" />
    </div>
    <div class="flex flex-col gap-2 pb-6">
      <label for="category">Sélectionner une catégorie</label>
      <Select id="category" v-model="selectedCategory" :options="a" optionLabel="name" placeholder="Selectionnez une catégorie" class="w-full md:w-70" />
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