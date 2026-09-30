<script setup>
import { ref, computed, onMounted } from 'vue'
import { PhX } from '@phosphor-icons/vue'
import { useApi } from '@/composables/useApi'
import { useToast } from '@/composables/useToast'
import BaseSelect from '@/components/ui/BaseSelect.vue'
import BaseInput from '@/components/ui/BaseInput.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import ConfirmDialog from '@/components/ui/ConfirmDialog.vue'

const props = defineProps({
  ids: {
    type: Array,
    default: () => []
  }
})

const emit = defineEmits(['updated', 'clear'])

const { get, post } = useApi()
const { success, error } = useToast()

const today = () => new Date().toISOString().split('T')[0]

const states = ref([])
const stateId = ref('')
const datePaid = ref(today())
const saving = ref(false)
const confirmCancel = ref(false)

const stateOptions = computed(() =>
  states.value.map(s => ({
    value: s.id,
    label: s.description.charAt(0).toUpperCase() + s.description.slice(1)
  }))
)

const selectedState = computed(() => states.value.find(s => String(s.id) === String(stateId.value)))
const isPaid = computed(() => selectedState.value?.description === 'paid')
const isCancel = computed(() => selectedState.value?.description === 'cancelled')

async function fetchStates() {
  try {
    const data = await get('/api/invoice/states')
    states.value = data.data || []
  } catch (e) {
    error('Failed to load states')
  }
}

// Cancelling zeroes the invoice amounts, so it gets a second look.
function apply() {
  if (!stateId.value || props.ids.length === 0) return
  if (isCancel.value) {
    confirmCancel.value = true
    return
  }
  submit()
}

async function submit() {
  saving.value = true
  try {
    const data = await post('/api/invoices/update/state', {
      state_id: stateId.value,
      date_paid: isPaid.value ? datePaid.value : null,
      invoice_ids: props.ids
    })
    const updated = data.updated?.length ?? 0
    success(`${updated} ${updated === 1 ? 'invoice' : 'invoices'} updated`)
    stateId.value = ''
    confirmCancel.value = false
    emit('updated')
  } catch (e) {
    error(e?.response?.data?.message || 'Failed to update invoices')
  } finally {
    saving.value = false
  }
}

onMounted(fetchStates)
</script>

<template>
  <div class="flex items-center gap-3">
    <button
      @click="emit('clear')"
      class="p-1.5 text-gray-400 hover:text-gray-600 hover:bg-gray-100 cursor-pointer rounded-sm transition-colors"
      title="Clear selection"
    >
      <PhX class="w-4 h-4" />
    </button>
    <span class="shrink-0 text-sm font-medium tabular-nums">{{ ids.length }} selected</span>
    <div class="w-44 shrink-0">
      <BaseSelect
        v-model="stateId"
        :options="stateOptions"
        placeholder="Set status…"
        compact
      />
    </div>
    <div v-if="isPaid" class="w-40 shrink-0">
      <BaseInput v-model="datePaid" type="date" title="Date paid" compact />
    </div>
    <BaseButton :disabled="!stateId" :loading="saving" @click="apply">Apply</BaseButton>

    <ConfirmDialog
      :show="confirmCancel"
      title="Cancel Invoices"
      confirm-label="Cancel Invoices"
      :message="`Cancel ${ids.length} ${ids.length === 1 ? 'invoice' : 'invoices'}? Their amounts will be set to zero.`"
      :loading="saving"
      @confirm="submit"
      @cancel="confirmCancel = false"
    />
  </div>
</template>
