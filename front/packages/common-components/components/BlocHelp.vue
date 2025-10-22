<script setup lang="ts">

const props = defineProps({
  message: {
    type: String,
    required: true
  },
})

</script>

<template>
  <div class="information-bloc" role="status" aria-live="polite">
    <i class="picto pi pi-info-circle" aria-hidden="true"></i>
    <div class="content">
      <strong>Aide contextuelle</strong>
      <p>{{ message }}</p>
    </div>
  </div>
</template>

<style scoped>
.information-bloc {
  background-color: var(--p-violet-100);
  border: 1px solid rgba(0, 0, 0, 0.06); /* fine border */
  border-left: 6px solid var(--primary-color); /* forte bordure à gauche */
  padding: 1rem;
  margin: 1rem 0;
  display: flex;
  gap: 0.75rem;
  align-items: flex-start;
  border-radius: 6px;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03);
  color: #222;
}

/* picto d'aide (utilise primeicons) */
.information-bloc .picto,
.information-bloc .pi {
  font-size: 1.4rem;
  color: var(--primary-color);
  line-height: 1;
  margin-top: 2px;
}

/* contenu du bloc */
.information-bloc .content {
  flex: 1;
  font-size: 0.98rem;
}
</style>
