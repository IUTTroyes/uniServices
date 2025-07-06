// `front/apps/intranet/src/stores/layoutStore.js`
import { defineStore } from 'pinia'
import { ref } from 'vue'

export const useLayoutStore = defineStore('layout', () => {
  const rightOfBreadcrumb = ref(null)
  return { rightOfBreadcrumb }
})
