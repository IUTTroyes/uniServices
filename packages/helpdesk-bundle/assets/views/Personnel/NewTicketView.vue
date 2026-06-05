<script setup>
import {ValidatedInput, validationRules} from "@components";
import {getServicesService} from "@requests";
import {createTicketService} from '@requests'
import {ref,onMounted,computed} from "vue";
import {useRouter} from "vue-router";
import CascadeSelect from 'primevue/cascadeselect';
import {useUsersStore} from '@stores';

const router=useRouter()

const services=ref([])
const selectedService=ref(null)
const selectedCategorie=ref(null)
const filesNames=ref([])
const selectedSujet=ref("")
const selectedMessage=ref("")
const userStore=useUsersStore();
const user=computed(()=>userStore.user)


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
    helpdeskCategorie: categoryId ? `/api/helpdesk_categories/${categoryId}` : null,
    files: filesNames.value,
    auteur: user.value ? `/api/personnels/${user.value.id}` : null,
  };

  const formData = new FormData();

  formData.append('subject', payload.subject);
  formData.append('description', payload.description);

  if (payload.helpdeskCategorie) {
    formData.append('helpdeskCategorie', payload.helpdeskCategorie);
  }
  if (payload.auteur) {
    formData.append('author', payload.author);  }

  // 👇 Ajout des fichiers
  if (payload.files && payload.files.length > 0) {
    payload.files.forEach((file) => {
      formData.append('files[]', file); // ou 'files' selon votre config
    });
  }

  try {
    console.log(filesNames.value)
    await createTicketService(formData, true);
    router.push({ name: 'Dashboard'});
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
        />

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
          :disabled="!selectedService"/>
    </div>
    <div class="flex flex-col gap-2 pb-6">
        <ValidatedInput
          v-model="selectedSujet"
          name="sujet"
          type="text"
          :rules="[]"
          label="Sujet"
        />
    </div>
    <div class="flex flex-col gap-2 pb-6">
        <ValidatedInput
            v-model="selectedMessage"
            name="message"
            type="textarea"
            :rules="[]"
            label="Message"
        />
    </div>
    <div class="pb-6">
      <ValidatedInput
          v-model="filesNames"
          name="files"
          label="Fichier"
          type="file"
          :rules="[]"
          help-text="Ajouter un fichier"
          :multiple="true"
          accept=".pdf,.doc,.docx,.jpd,.png,.jpeg,.txt"
          class="w-full"
      />
    </div>
    <div class="flex justify-end">
      <Button class="w-60 !bg-blue-400" type="submit" severity="info" label="Envoyer" />
    </div>
    </form>
  </div>

</template>

<style scoped>

</style>
