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
      <label for="subject">Sujet</label>
      <InputText type="text" v-model="value" />
    </div>
    <div class="flex flex-col gap-2 pb-6">
      <label for="message">Message</label>
      <Textarea id="message" v-model="value" rows="5" cols="30" />
    </div>
    <div class="pb-6">
    <FileUpload ref="fileupload" mode="basic" name="demo[]" url="/api/upload" accept="image/*" :maxFileSize="1000000" @upload="onUpload" />
    </div>
    <div class="flex justify-end">
      <Button class="w-60 !bg-blue-400" type="submit" severity="info" label="Envoyer" />
    </div>
    </form>
  </div>

</template>

<style scoped>

</style>