import { defineStore } from 'pinia'
import { ref } from 'vue'

export const useAppStore = defineStore('app', () => {

  const isLoading = ref(true);

  return {
    isLoading,
  };
})
