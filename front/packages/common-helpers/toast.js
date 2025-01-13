import { useToast } from "primevue/usetoast";
const toast = useToast();

export function showInfo(message, detail, life = 3000) {
  toast.add({ severity: 'info', summary: message, detail: detail, life: life });
}

export function showDanger(message, detail, life = 3000) {
  toast.add({ severity: 'danger', summary: message, detail: detail, life: life });
}

export function showWarn(message, detail, life = 3000) {
  toast.add({ severity: 'warn', summary: message, detail: detail, life: life });
}

export function showSuccess(message, detail, life = 3000) {
  toast.add({ severity: 'success', summary: message, detail: detail, life: life });
}
