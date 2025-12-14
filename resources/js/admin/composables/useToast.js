import { ref } from 'vue'

const toasts = ref([])
let toastId = 0

export function useToast() {
  function addToast(message, type = 'success', duration = 3000) {
    const id = ++toastId
    toasts.value.push({ id, message, type })

    if (duration > 0) {
      setTimeout(() => {
        removeToast(id)
      }, duration)
    }
  }

  function removeToast(id) {
    const index = toasts.value.findIndex(t => t.id === id)
    if (index > -1) {
      toasts.value.splice(index, 1)
    }
  }

  function success(message, duration = 3000) {
    addToast(message, 'success', duration)
  }

  function error(message, duration = 5000) {
    addToast(message, 'error', duration)
  }

  function info(message, duration = 3000) {
    addToast(message, 'info', duration)
  }

  function warning(message, duration = 4000) {
    addToast(message, 'warning', duration)
  }

  return {
    toasts,
    success,
    error,
    info,
    warning,
    removeToast
  }
}
