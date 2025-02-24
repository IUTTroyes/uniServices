import { useToast } from "primevue/usetoast";
let toast

export function initializeToast() {
  toast = useToast()
}
// const toast = useToast();

export function showInfo(detail, message = 'Information', life = 3000) {
  toast.add({ severity: 'info', summary: message, detail: detail, life: life });
}

export function showDanger(detail, message = 'Erreur', life = 3000) {
  toast.add({ severity: 'error', summary: message, detail: detail, life: life });
}

export function showWarn(detail, message = 'Attention', life = 3000) {
  toast.add({ severity: 'warn', summary: message, detail: detail, life: life });
}

export function showSuccess(detail, message = 'Succès', life = 3000) {
  toast.add({ severity: 'success', summary: message, detail: detail, life: life });
}
