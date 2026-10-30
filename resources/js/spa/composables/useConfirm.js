import { ref } from 'vue'

// One app-wide dialog (AppConfirm in App.vue) so callers can simply
// `if (!(await confirm({ ... }))) return` instead of wiring up their own.
const dialog = ref({ show: false })
let resolver = null

export function useConfirm() {
  function confirm(options = {}) {
    resolver?.(false)
    dialog.value = { ...options, show: true }
    return new Promise(resolve => { resolver = resolve })
  }

  function settle(result) {
    dialog.value.show = false
    resolver?.(result)
    resolver = null
  }

  return { dialog, confirm, settle }
}
